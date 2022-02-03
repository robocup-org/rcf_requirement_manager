<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/modaresimr
 * @since             1.0.2
 * @package           Rcf_requirement_manager
 *
 * @wordpress-plugin
 * Plugin Name:       RCF Requirement Manager
 * Plugin URI:        https://github.com/modaresimr/rcf_requirement_manager
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.2
 * Author:            Ali Modaresi 
 * Author URI:        https://github.com/modaresimr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rcf_requirement_manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('RCF_REQUIREMENT_MANAGER_VERSION', '1.0.0');


defined('ABSPATH') or die('Hey, what are you doing here? You silly human!');

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}
require_once dirname(__FILE__) . '/inc/Utils.php';

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));

if (class_exists('Inc\\Init')) {
    Inc\Init::register_services();
}

function default_comments_on($data)
{
    if ($data['post_type'] == 'requirement') {
        $data['comment_status'] = 1;
    }

    return $data;
}
add_filter('wp_insert_post_data', 'default_comments_on');
