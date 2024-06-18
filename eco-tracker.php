<?php

/**
 * @package EcoTracker
 */

use EcoTracker\Lib\Menu\AdminMenu;
use EcoTracker\Lib\Service\InitService;

/*
Plugin Name: EcoTracker
Plugin URI: https://github.com/trungro12/
Description: Plugin tracks specific events and pushes data to the dataLayer for use with Google Tag Manager (GTM)
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 5.6.20
Author: Trung Pham
Author URI: https://github.com/trungro12/
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo 'Can not run Plugin!';
	exit;
}

define("ECOTRACKER_NAME", 'EcoTracker');
define("ECOTRACKER_NAMESPACE", 'EcoTracker');
define("ECOTRACKER_PLUGIN_DIR", rtrim(plugin_dir_path(__FILE__), DIRECTORY_SEPARATOR));
require_once 'autoload.php';

add_action('init', [InitService::class, 'init']);
add_action('admin_init', [InitService::class, 'adminInit']);

// init menu 
AdminMenu::init();