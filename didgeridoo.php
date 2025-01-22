<?php
/*
Plugin Name: Didgeridoo
Plugin URI: http://example.com/
Description: A simple starter plugin for WordPress.
Version: 1.0
Author: Tom Busby
Author URI: https://nimdok.io
Text Domain: didgeridoo
License: EPL-2.0
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include the Composer autoload file
require_once __DIR__ . '/vendor/autoload.php';

use Didgeridoo\SettingsPage;
use Didgeridoo\WellKnownRouteResolver;

$settingsPage = new SettingsPage();
$wellKnownRouteResolver = new WellKnownRouteResolver();

function loadDidgeridooTextdomain() {
    $pluginRelativePath = basename(dirname(__FILE__)) . '/languages';
	load_plugin_textdomain('didgeridoo', false, $pluginRelativePath);
}
add_action('plugins_loaded', 'loadDidgeridooTextdomain');
