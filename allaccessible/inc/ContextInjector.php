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

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_head', array($this, 'emit_context'), 2);
    }

    /**
     * Emit the context block on the frontend only.
     */
    public function emit_context() {
        if (is_admin()) {
            return;
        }

        $context = $this->build_context();

        // wp_json_encode is unicode-safe + handles slashing for HTML embed.
        $json = wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
                    foreach ($matches[1] as $src) {
                        if (count($map) >= self::MAX_ATTACHMENTS) break;
                        $normalized = $this->normalize_url($src);
                        if (isset($map[$normalized])) continue;
                        $att_id = attachment_url_to_postid($src);
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
