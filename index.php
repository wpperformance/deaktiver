<?php

/**
 * @author            infos@goodmotion.fr
 * @copyright         2023 Goodmotion
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Deaktiver
 * Plugin URI:        false
 * Description:       Disable native features of WordPress that you don't need.
 * Version:           0.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Faramaz patrick
 * Author URI:        https://goodmotion.fr
 * Text Domain:       deaktiver
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        false
 */

use Deaktiver\Dependencies\PressWind\PWVite;
use Deaktiver\Options\OptionsPage;
use Deaktiver\Options\OptionsServices;

require_once __DIR__ . '/vendor/autoload.php';

if (! defined('WP_ENV')) {
    define('WP_ENV', 'development');
}
define('WPPERFORMANCE_DEAKTIVER', 'deaktiver');
define('WPPERFORMANCE_DEAKTIVER_URI', plugin_dir_url(__DIR__) . WPPERFORMANCE_DEAKTIVER);

/**
 * admin options page
 */
new OptionsPage;
new OptionsServices;

/**
 * init all deaktive activated
 */
Deaktiver\Deaktive\Deaktiver::init();

// if page is deaktiver load assets
if (isset($_GET['page']) && $_GET['page'] === WPPERFORMANCE_DEAKTIVER) {
    PWVite::init(port: 9980, path: '/admin-app', position: 'admin',
        plugin_path: __FILE__, slug: WPPERFORMANCE_DEAKTIVER);
}

/**
 * Load the plugin text domain for translation.
 */ function deaktiver_load_textdomain()
{
    load_plugin_textdomain(
        WPPERFORMANCE_DEAKTIVER,
        false,
        basename(dirname(__FILE__)) . '/languages'
    );
}

add_action('init', 'deaktiver_load_textdomain');
