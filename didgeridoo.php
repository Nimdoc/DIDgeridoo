<?php

/**
 * ******************************************************************************
 * Copyright (c) 2025 Tom Busby
 *
 * This program and the accompanying materials are made available under the
 * terms of the Eclipse Public License 2.0 which is available at
 * http://www.eclipse.org/legal/epl-2.0.
 *
 * This Source Code may also be made available under the following Secondary
 * Licenses when the conditions for such availability set forth in the Eclipse
 * Public License, v. 2.0 are satisfied: GNU General Public License, version 2
 * only which is available at 
 * https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html.
 *
 * SPDX-License-Identifier: EPL-2.0 OR GPL-2.0-only
 * ******************************************************************************
 */

/*
Plugin Name: DIDgeridoo
Plugin URI: https://github.com/Nimdoc/DIDgeridoo
Description: Manage ATProto DIDs for your domain.
Version: 1.0.1
Author: Tom Busby
Author URI: https://nimdok.io
Text Domain: didgeridoo
License: EPL-2.0 OR GPL-2.0-only
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include the Composer autoload file
require_once __DIR__ . '/vendor/autoload.php';

use DIDgeridoo\SettingsPage;
use DIDgeridoo\WellKnownRouteResolver;
use DIDgeridoo\UserProfile;

$settingsPage = new SettingsPage();
$wellKnownRouteResolver = new WellKnownRouteResolver();
$userProfile = new UserProfile();
