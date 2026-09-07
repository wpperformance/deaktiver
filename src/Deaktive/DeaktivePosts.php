<?php

declare(strict_types=1);

namespace Deaktiver\Deaktive;

use Deaktiver\Deaktive\Base\DeaktiveBase;
use WP_Query;

/**
 * Disable the native WordPress blog surface (posts, categories, tags).
 */
class DeaktivePosts extends DeaktiveBase
{
    /**
     * Register hooks that hide posts on front, REST, widgets and admin.
     */
    public function disable(): void
    {
        add_action('widgets_init', [$this, 'disable_post_widgets']);
        add_action('template_redirect', [$this, 'redirect_post_routes']);
        add_action('pre_get_posts', [$this, 'exclude_posts_from_front_queries']);
        add_filter('rest_endpoints', [$this, 'disable_rest_posts']);
        add_action('wp_before_admin_bar_render', [$this, 'remove_admin_bar_new_post'], 9999);
        add_action('wp_loaded', [$this, 'wp_loaded_disable'], 9999);
    }

    /**
     * Unregister post-related widgets.
     */
    public function disable_post_widgets(): void
    {
        unregister_widget('WP_Widget_Recent_Posts');
        unregister_widget('WP_Widget_Categories');
        unregister_widget('WP_Widget_Archives');
        unregister_widget('WP_Widget_Calendar');
        unregister_widget('WP_Widget_Tag_Cloud');
    }

    /**
     * Redirect front-end post/blog routes to the home page.
     */
    public function redirect_post_routes(): void
    {
        if ($this->should_redirect_request()) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    /**
     * Exclude the post type from front queries (search, Query Loop, etc.).
     */
    public function exclude_posts_from_front_queries(WP_Query $query): void
    {
        if (is_admin()) {
            return;
        }

        $post_type = $query->get('post_type');

        if ($post_type === 'post') {
            $query->set('post__in', [0]);
            $query->set('post_type', 'post');

            return;
        }

        if (is_array($post_type) && in_array('post', $post_type, true)) {
            $remaining = array_values(array_diff($post_type, ['post']));
            if ($remaining === []) {
                $query->set('post__in', [0]);
            } else {
                $query->set('post_type', $remaining);
            }

            return;
        }

        // Empty post_type defaults to "post" for many secondary queries (blocks, widgets).
        if (($post_type === '' || $post_type === null) && ! $query->is_main_query()) {
            $query->set('post__in', [0]);
        }

        if ($query->is_main_query() && $query->is_search() && ($post_type === '' || $post_type === null)) {
            $types = get_post_types(['exclude_from_search' => false], 'names');
            unset($types['post']);
            $query->set('post_type', array_values($types));
        }
    }

    /**
     * Remove posts, categories and tags REST endpoints (including sub-routes).
     */
    public function disable_rest_posts(array $endpoints): array
    {
        foreach (array_keys($endpoints) as $route) {
            if (preg_match('#^/wp/v2/(posts|categories|tags)(/|$)#', $route) === 1) {
                unset($endpoints[$route]);
            }
        }

        return $endpoints;
    }

    /**
     * Remove "New Post" from the admin bar.
     */
    public function remove_admin_bar_new_post(): void
    {
        global $wp_admin_bar;

        if (! $wp_admin_bar) {
            return;
        }

        $wp_admin_bar->remove_node('new-post');
    }

    /**
     * Disable post admin menus, screens and dashboard widgets.
     */
    public function wp_loaded_disable(): void
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_menu', [$this, 'disable_admin_menus'], 9999);
        add_action('wp_dashboard_setup', [$this, 'disable_dashboard_widgets']);
        add_action('admin_print_styles-index.php', [$this, 'hide_dashboard_post_counts']);
    }

    /**
     * Remove Posts menu and block related admin screens.
     */
    public function disable_admin_menus(): void
    {
        global $pagenow;

        remove_menu_page('edit.php');

        $post_type = $this->resolve_admin_post_type();
        $taxonomy = isset($_GET['taxonomy']) ? sanitize_key(wp_unslash($_GET['taxonomy'])) : '';

        $post_screens = ['edit.php', 'post-new.php', 'post.php'];
        if (in_array($pagenow, $post_screens, true) && ($post_type === '' || $post_type === 'post')) {
            wp_die(__('Posts are disabled.', 'deaktiver'), '', ['response' => 403]);
        }

        $taxonomy_screens = ['edit-tags.php', 'term.php'];
        if (in_array($pagenow, $taxonomy_screens, true) && in_array($taxonomy, ['category', 'post_tag'], true)) {
            wp_die(__('Posts are disabled.', 'deaktiver'), '', ['response' => 403]);
        }
    }

    /**
     * Resolve the post type for the current admin posts screen.
     *
     * Classic editor POSTs to post.php with no query string, so GET post_type
     * is missing on save. Prefer GET, then POST, then get_post_type() of the
     * edited ID (GET post, POST post_ID, REQUEST post).
     */
    private function resolve_admin_post_type(): string
    {
        global $pagenow;

        $post_type = $this->request_post_type();

        if (($post_type === '' || $post_type === 'post') && $pagenow === 'post.php') {
            $edited_id = $this->resolve_edited_post_id();
            if ($edited_id > 0) {
                $edited_type = get_post_type($edited_id);
                if (is_string($edited_type) && $edited_type !== '') {
                    return $edited_type;
                }
            }
        }

        return $post_type;
    }

    /**
     * Post type from GET, then POST. Defaults to "post" when both are absent.
     */
    private function request_post_type(): string
    {
        if (isset($_GET['post_type']) && is_string($_GET['post_type'])) {
            return sanitize_key(wp_unslash($_GET['post_type']));
        }

        if (isset($_POST['post_type']) && is_string($_POST['post_type'])) {
            return sanitize_key(wp_unslash($_POST['post_type']));
        }

        return 'post';
    }

    /**
     * Edited post ID on post.php: query string, classic-save body, then REQUEST.
     */
    private function resolve_edited_post_id(): int
    {
        foreach ([
            $_GET['post'] ?? null,
            $_POST['post_ID'] ?? null,
            $_REQUEST['post'] ?? null,
        ] as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Remove Quick Draft from the dashboard.
     */
    public function disable_dashboard_widgets(): void
    {
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    }

    /**
     * Hide post counts on the At a Glance dashboard widget.
     */
    public function hide_dashboard_post_counts(): void
    {
        echo '<style>
			#dashboard_right_now .post-count {
				display: none !important;
			}
		</style>';
    }

    /**
     * Whether the current front request should redirect to home.
     */
    private function should_redirect_request(): bool
    {
        if (is_singular('post')) {
            return true;
        }

        // Built-in post taxonomies only (plan: disable categories/tags).
        if (is_category() || is_tag()) {
            return true;
        }

        if (is_home() && ! is_front_page()) {
            return true;
        }

        if ((is_author() || is_date()) && $this->is_post_type_var_posts()) {
            return true;
        }

        if (is_search() && $this->is_post_type_var_posts()) {
            return true;
        }

        // Post feeds: /feed/, post type feed, and archive feeds already covered above.
        if (is_feed() && ! is_comment_feed() && $this->is_post_type_var_posts()) {
            return true;
        }

        return false;
    }

    /**
     * Whether query post_type is empty (defaults to post) or explicitly post-only.
     */
    private function is_post_type_var_posts(): bool
    {
        $post_type = get_query_var('post_type');

        if ($post_type === '' || $post_type === 'post') {
            return true;
        }

        if (is_array($post_type)) {
            $post_type = array_values(array_filter($post_type));

            return $post_type === ['post'] || $post_type === [];
        }

        return false;
    }
}
