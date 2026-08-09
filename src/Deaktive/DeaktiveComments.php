<?php

declare(strict_types=1);

namespace Deaktiver\Deaktive;

use Deaktiver\Deaktive\Base\DeaktiveBase;

class DeaktiveComments extends DeaktiveBase
{
    public function disable(): void
    {
        // widget
        add_action('widgets_init', [$this, 'disable_comment_widget']);

        // links
        add_action('template_redirect', [$this, 'remove_link_admin_bar']);
        add_action('admin_init', [$this, 'remove_link_admin_bar']);

        add_action('wp_loaded', [$this, 'wp_loaded_disable'], 9999);

        add_filter('get_comment_author_link', function ($return, $author,
            $comment_ID) {
            return $author;
        }, 10, 3);

        add_filter('get_comment_author_url', '__return_false');
        add_filter('comment_form_default_fields', function ($fields) {
            unset($fields['url']);

            return $fields;
        }, 9999);

        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('comments');
        }, 9999);

    }

    /**
     * Disable comment widget.
     */
    public function disable_comment_widget(): void
    {
        unregister_widget('WP_Widget_Recent_Comments');
        add_filter('show_recent_comments_widget_style', '__return_false');
    }

    /**
     * Remove link admin bar.
     */
    public function remove_link_admin_bar(): void
    {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);

            if (is_multisite()) {
                add_action('admin_bar_menu', [$this, 'remove_link_admin_bar_for_network'], 500);
            }
        }
    }

    /**
     * Remove link admin bar for network.
     */
    public function remove_link_admin_bar_for_network($wp_admin_bar): void
    {
        if (! function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }
        if (is_plugin_active_for_network('deaktiver/deaktiver.php') &&
           is_user_logged_in()) {

            // all
            foreach ($wp_admin_bar->user->blogs as $blog) {
                $wp_admin_bar->remove_menu('blog-' . $blog->userblog_id . '-c');
            }
        } else {

            // current
            $wp_admin_bar->remove_menu('blog-' . get_current_blog_id() . '-c');
        }
    }

    /**
     * remove filter, comment, discussion...
     */
    public function wp_loaded_disable(): void
    {
        // posts types
        $post_types = get_post_types(['public' => true], 'names');
        if (! empty($post_types)) {
            foreach ($post_types as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        }

        // filters
        add_filter('comments_array', '__return_empty_array', 20, 2);
        add_filter('comments_open', '__return_false', 20, 2);

        add_filter('pings_open', '__return_false', 20, 2);

        if (is_admin()) {

            // menu + disable admin
            add_action('admin_menu', function () {
                global $pagenow;

                //Remove Comment + Discussion Menu Links
                remove_menu_page('edit-comments.php');
                remove_submenu_page('options-general.php', 'options-discussion.php');

                //Disable Comments Pages
                if ($pagenow == 'comment.php' || $pagenow == 'edit-comments.php') {
                    wp_die(__('Comments are disabled.', 'wpperformance-deaktiver'), '', ['response' => 403]);
                }

                //Disable Discussion Page
                if ($pagenow == 'options-discussion.php') {
                    wp_die(__('Comments are disabled.', 'wpperformance-deaktiver'), '', ['response' => 403]);
                }
            }, 9999);

            // dashboard
            add_action('admin_print_styles-index.php', function () {
                echo '<style>
					#dashboard_right_now .comment-count, #dashboard_right_now .comment-mod-count, #latest-comments, #welcome-panel .welcome-comments {
						display: none !important;
					}
					</style>';
            });

            // profile
            add_action('admin_print_styles-profile.php', function () {
                echo '<style>
						.user-comment-shortcuts-wrap {
							display: none !important;
						}
					</style>';
            });

            //recent comments dashboard
            add_action('wp_dashboard_setup', function () {
                remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            });

            //pingback
            add_filter('pre_option_default_pingback_flag', '__return_zero');
        } else {

            //blank comments template
            add_filter('comments_template', function () {
                return dirname(__FILE__) . '/index.php';
            }, 20);

            //comments reply
            wp_deregister_script('comment-reply');

        }
    }
}
