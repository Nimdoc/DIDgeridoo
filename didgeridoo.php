<?php
/*
Plugin Name: Didgeridoo
Plugin URI: http://example.com/
Description: A simple starter plugin for WordPress.
Version: 1.0
Author: Tom Busby
Author URI: https://nimdok.io
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
