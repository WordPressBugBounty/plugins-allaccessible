<?php
/**
 * Context Injector — emits `window.AllAccessibleContext` on every public page.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_ContextInjector {

    const CONTEXT_VERSION = 1;
    const PLATFORM        = 'wordpress';
    const MAX_ATTACHMENTS = 200;

    /**
     * Post-meta key caching the attachment map. Building the map costs a
     * get_children query plus one postmeta lookup per inline image — too
     * expensive to repeat on every page view for every visitor.
     */
    const MAP_META_KEY = '_aacb_context_map';

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_head', array($this, 'emit_context'), 2);

        // Invalidate the cached map when the post or its media change.
        add_action('save_post',         array($this, 'flush_map'));
        add_action('add_attachment',    array($this, 'flush_parent_map'));
        add_action('edit_attachment',   array($this, 'flush_parent_map'));
        add_action('delete_attachment', array($this, 'flush_parent_map'));
    }

    public function flush_map($post_id) {
        delete_post_meta($post_id, self::MAP_META_KEY);
    }

    public function flush_parent_map($attachment_id) {
        $parent = (int) get_post_field('post_parent', $attachment_id);
        if ($parent) {
            delete_post_meta($parent, self::MAP_META_KEY);
        }
    }

    /**
     * Emit the context block on the frontend only.
     */
    public function emit_context() {
        if (is_admin()) {
            return;
        }

        // The context map only feeds account-bound features (agentic image
        // fixes). No connected account → nothing consumes it; skip the work
        // entirely instead of taxing every visitor on unconfigured installs.
        if (!get_option('aacb_accountID')) {
            return;
        }

        $context = $this->build_context();

        // JSON_HEX_TAG/JSON_HEX_AMP keep encoded values from ever forming
        // a </script> or HTML-significant sequence inside the inline block.
        $json = wp_json_encode($context, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return; // Encoding failed — silently bail rather than emit junk.
        }
        ?>
<script id="allaccessible-context" type="text/javascript">/* <![CDATA[ */
window.AllAccessibleContext = <?php echo $json; ?>;
/* ]]> */</script>
        <?php
    }

    /**
     * Build the context array. 
     *
     * @return array<string,mixed>
     */
    public function build_context() {
        $post_id   = null;
        $post_type = null;

        if (is_singular()) {
            $queried = get_queried_object();
            if ($queried instanceof WP_Post) {
                $post_id   = (int) $queried->ID;
                $post_type = (string) $queried->post_type;
            }
        }

        return array(
            'version'     => self::CONTEXT_VERSION,
            'platform'    => self::PLATFORM,
            'postId'      => $post_id,
            'postType'    => $post_type,
            'attachments' => $this->collect_attachments($post_id),
        );
    }

    /**
     * Map normalized image URL -> WP attachment ID for attachments associated
     * with the current post. 
     * 
     * @param int|null $post_id
     * @return array<string,int>
     */
    private function collect_attachments($post_id) {
        if (!$post_id) {
            return array();
        }

        $cached = get_post_meta($post_id, self::MAP_META_KEY, true);
        if (is_array($cached)
            && isset($cached['v'], $cached['map'])
            && $cached['v'] === self::CONTEXT_VERSION
            && is_array($cached['map'])) {
            return $cached['map'];
        }

        $map = array();

        // Pass 1: attachments attached to this post.
        $attached = get_children(array(
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'numberposts'    => self::MAX_ATTACHMENTS,
            'fields'         => 'ids',
        ));

        if (is_array($attached)) {
            foreach ($attached as $att_id) {
                $url = wp_get_attachment_url($att_id);
                if (!$url) continue;
                $normalized = $this->normalize_url($url);
                $map[$normalized] = (int) $att_id;
                if (count($map) >= self::MAX_ATTACHMENTS) break;
            }
        }

        // Pass 2: inline images referenced in post_content.
        if (count($map) < self::MAX_ATTACHMENTS) {
            $post = get_post($post_id);
            if ($post && !empty($post->post_content)) {
                // Cheap regex: pull src URLs from img tags. Bounded by
                // MAX_ATTACHMENTS cap, won't run away on huge content.
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches)) {
                    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
                    foreach ($matches[1] as $src) {
                        if (count($map) >= self::MAX_ATTACHMENTS) break;
                        $normalized = $this->normalize_url($src);
                        if ($normalized === '' || isset($map[$normalized])) continue;

                        // External/CDN hosts can never resolve to a local
                        // attachment — skip the postmeta query outright.
                        $src_host = wp_parse_url($normalized, PHP_URL_HOST);
                        if ($src_host && $site_host && strcasecmp($src_host, $site_host) !== 0) {
                            continue;
                        }

                        // Look up the NORMALIZED url (size suffix stripped):
                        // attachment_url_to_postid only matches the original
                        // file, so foo-300x200.jpg would always miss.
                        $att_id = attachment_url_to_postid($normalized);
                        if ($att_id) {
                            $map[$normalized] = (int) $att_id;
                        }
                    }
                }
            }
        }

        // Featured image (if not already captured).
        if (count($map) < self::MAX_ATTACHMENTS && has_post_thumbnail($post_id)) {
            $thumb_id  = get_post_thumbnail_id($post_id);
            $thumb_url = wp_get_attachment_url($thumb_id);
            if ($thumb_url) {
                $normalized = $this->normalize_url($thumb_url);
                if (!isset($map[$normalized])) {
                    $map[$normalized] = (int) $thumb_id;
                }
            }
        }

        update_post_meta($post_id, self::MAP_META_KEY, array(
            'v'   => self::CONTEXT_VERSION,
            'map' => $map,
        ));

        return $map;
    }

    /**
     * Normalize an image URL to its base form (no query string, no size
     * suffix). 
     *
     * @param string $url
     * @return string
     */
    private function normalize_url($url) {
        if (!is_string($url) || $url === '') return '';
        $parsed = wp_parse_url($url);
        if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['path'])) {
            return $url;
        }
        $scheme = !empty($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $path   = $parsed['path'];

        // Strip WP image size suffix: foo-300x200.jpg -> foo.jpg
        $path = preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $path);

        return $scheme . '://' . $parsed['host'] . $path;
    }
}

add_action('plugins_loaded', function() {
    AllAccessible_ContextInjector::get_instance();
});
