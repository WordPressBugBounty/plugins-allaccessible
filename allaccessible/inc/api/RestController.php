<?php
/**
 * AllAccessible REST API Controller
 *
 * Provides WordPress REST API endpoints for accessibility data.
 * Used by Gutenberg editor and other frontend features.
 *
 * @package AllAccessible
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AllAccessible_RestController {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * API namespace
     */
    const NAMESPACE = 'allaccessible/v1';

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register routes
     */
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get page-specific accessibility score
        register_rest_route(self::NAMESPACE, '/page-score/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_page_score'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        // Trigger rescan for specific page
        register_rest_route(self::NAMESPACE, '/page-score/(?P<id>\d+)/rescan', array(
            'methods' => 'POST',
            'callback' => array($this, 'rescan_page'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        // Get all issues for a page
        register_rest_route(self::NAMESPACE, '/page-issues/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_page_issues'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        // Get site-wide issues breakdown
        register_rest_route(self::NAMESPACE, '/issues-breakdown', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_issues_breakdown'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Permission callback - check if user can edit posts
     */
    public function check_permissions() {
        return current_user_can('edit_posts');
    }

    /**
     * Get accessibility score for specific page
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_page_score($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'allaccessible'),
                array('status' => 404)
            );
        }

        $page_url = get_permalink($post_id);

        if (!$page_url) {
            return new WP_Error(
                'invalid_permalink',
                __('Could not generate permalink for this post', 'allaccessible'),
                array('status' => 400)
            );
        }

        // Get API client
        $api_client = AllAccessible_ApiClient::get_instance();

        // Fetch page-specific score from Symfony API
        $score_data = $this->fetch_page_score_from_api($page_url);

        if (is_wp_error($score_data)) {
            return $score_data;
        }

        // Format response
        $response = array(
            'post_id' => $post_id,
            'post_title' => get_the_title($post_id),
            'post_url' => $page_url,
            'overall_score' => $score_data['overall_score'] ?? 0,
            'wcag_level' => $score_data['wcag_level'] ?? 'None',
            'issues' => array(
                'critical' => $score_data['issues']['critical'] ?? 0,
                'serious' => $score_data['issues']['serious'] ?? 0,
                'moderate' => $score_data['issues']['moderate'] ?? 0,
                'minor' => $score_data['issues']['minor'] ?? 0,
            ),
            'total_issues' => ($score_data['issues']['critical'] ?? 0) +
                            ($score_data['issues']['serious'] ?? 0) +
                            ($score_data['issues']['moderate'] ?? 0) +
                            ($score_data['issues']['minor'] ?? 0),
            'last_scan' => $score_data['last_scan'] ?? null,
            'grade' => $this->calculate_grade($score_data['overall_score'] ?? 0),
        );

        return rest_ensure_response($response);
    }

    /**
     * Trigger rescan for specific page
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function rescan_page($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'allaccessible'),
                array('status' => 404)
            );
        }

        $page_url = get_permalink($post_id);

        // Trigger audit via API
        $api_client = AllAccessible_ApiClient::get_instance();
        $result = $api_client->trigger_audit($page_url);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Page scan initiated. Results will be available shortly.', 'allaccessible'),
            'post_id' => $post_id,
        ));
    }

    /**
     * Get detailed issues for specific page
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_page_issues($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'allaccessible'),
                array('status' => 404)
            );
        }

        $page_url = get_permalink($post_id);

        // Fetch detailed issues from API
        $issues_data = $this->fetch_page_issues_from_api($page_url);

        if (is_wp_error($issues_data)) {
            return $issues_data;
        }

        return rest_ensure_response($issues_data);
    }

    /**
     * Get site-wide issues breakdown
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_issues_breakdown($request) {
        $api_client = AllAccessible_ApiClient::get_instance();
        $scores = $api_client->get_audit_scores();

        if (is_wp_error($scores)) {
            return $scores;
        }

        // Fetch detailed breakdown from API
        $breakdown = $this->fetch_issues_breakdown_from_api();

        if (is_wp_error($breakdown)) {
            return $breakdown;
        }

        return rest_ensure_response($breakdown);
    }

    /**
     * Fetch page score from Symfony API
     *
     * @param string $page_url
     * @return array|WP_Error
     */
    private function fetch_page_score_from_api($page_url) {
        $account_id = get_option('aacb_accountID');
        $site_id = get_option('aacb_siteID');

        if (!$account_id || !$site_id) {
            // Return mock data for free users or users without API access
            return array(
                'overall_score' => 0,
                'wcag_level' => 'None',
                'issues' => array(
                    'critical' => 0,
                    'serious' => 0,
                    'moderate' => 0,
                    'minor' => 0,
                ),
                'last_scan' => null,
            );
        }

        // Check cache first
        $cache_key = 'aacb_page_score_' . md5($page_url);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Make API request
        $response = wp_remote_request(
            'https://app.allaccessible.org/api/page-audit',
            array(
                'method' => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'AllAccessible-WP-Plugin/' . AACB_VERSION,
                ),
                'body' => json_encode(array(
                    'siteId' => $site_id,
                    'accountId' => $account_id,
                    'pageUrl' => $page_url,
                )),
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('API returned status code %d', 'allaccessible'), $status_code)
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_decode_error',
                __('Failed to parse API response', 'allaccessible')
            );
        }

        // Cache for 1 hour
        set_transient($cache_key, $data, 3600);

        return $data;
    }

    /**
     * Fetch detailed issues from Symfony API
     *
     * @param string $page_url
     * @return array|WP_Error
     */
    private function fetch_page_issues_from_api($page_url) {
        $account_id = get_option('aacb_accountID');
        $site_id = get_option('aacb_siteID');

        if (!$account_id || !$site_id) {
            return array('issues' => array());
        }

        // Check cache first
        $cache_key = 'aacb_page_issues_' . md5($page_url);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Make API request
        $response = wp_remote_request(
            'https://app.allaccessible.org/api/page-issues',
            array(
                'method' => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'AllAccessible-WP-Plugin/' . AACB_VERSION,
                ),
                'body' => json_encode(array(
                    'siteId' => $site_id,
                    'accountId' => $account_id,
                    'pageUrl' => $page_url,
                )),
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_decode_error',
                __('Failed to parse API response', 'allaccessible')
            );
        }

        // Cache for 1 hour
        set_transient($cache_key, $data, 3600);

        return $data;
    }

    /**
     * Fetch issues breakdown from Symfony API
     *
     * @return array|WP_Error
     */
    private function fetch_issues_breakdown_from_api() {
        $account_id = get_option('aacb_accountID');
        $site_id = get_option('aacb_siteID');

        if (!$account_id || !$site_id) {
            return array('issues' => array());
        }

        // Check cache first
        $cache_key = 'aacb_issues_breakdown';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Make API request
        $response = wp_remote_request(
            'https://app.allaccessible.org/api/issues-breakdown',
            array(
                'method' => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'AllAccessible-WP-Plugin/' . AACB_VERSION,
                ),
                'body' => json_encode(array(
                    'siteId' => $site_id,
                    'accountId' => $account_id,
                )),
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_decode_error',
                __('Failed to parse API response', 'allaccessible')
            );
        }

        // Cache for 1 hour
        set_transient($cache_key, $data, 3600);

        return $data;
    }

    /**
     * Calculate letter grade from score
     *
     * @param int $score
     * @return string
     */
    private function calculate_grade($score) {
        if ($score >= 90) {
            return 'A';
        } elseif ($score >= 80) {
            return 'B';
        } elseif ($score >= 70) {
            return 'C';
        } elseif ($score >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }
}

// Initialize REST controller
AllAccessible_RestController::get_instance();
