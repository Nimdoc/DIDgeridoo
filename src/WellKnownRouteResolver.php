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
 * with the GNU Classpath Exception which is
 * available at https://www.gnu.org/software/classpath/license.html.
 *
 * SPDX-License-Identifier: EPL-2.0 OR GPL-2.0 WITH Classpath-exception-2.0
 * ******************************************************************************
 */

namespace Didgeridoo;

class WellKnownRouteResolver
{
    public function __construct()
    {
        add_action('init', [$this, 'addRewriteRules']);
        add_action('template_redirect', [$this, 'handleWellKnownRequest']);
    }

    function addRewriteRules()
    {
        add_rewrite_rule('^.well-known/atproto-did?$', 'index.php?well_known_atproto_did=1', 'top');
        add_rewrite_tag('%well_known_atproto_did%', '([^&]+)');
    }

    public function handleWellKnownRequest()
    {
        if (!get_query_var('well_known_atproto_did')) {
            return;
        }

        $httpHost = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';

        // Check if the handle is valid https://atproto.com/specs/handle#handle-identifier-syntax
        // Don't even bother handling invalid handles
        if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/i', $httpHost)) {
            status_header(400);
            header('Content-Type: text/plain');
            echo "Invalid handle\n";
            exit;
        }

        $handleParts = explode('.', $httpHost);
        $startingLabel = $handleParts[0];
        $domain = implode('.', array_slice($handleParts, 1));
        $didgeridooSubdomain = get_option('didgeridoo_subdomain');

        $siteUrl = get_site_url();
        $urlParts = parse_url($siteUrl);
        $siteDomain = $urlParts['host'];

        $userSubdomain = ($didgeridooSubdomain ? $didgeridooSubdomain . '.' : '') . $siteDomain;

        if ($startingLabel === 'didgeridoo-test') {
            // This is for verifying the DIDgeridoo subdomain settings.
            // The front label is `didgeridoo-test`, 200 needs to be returned
            // because the subdomain might not yet be saved in the settings.
            status_header(200);
            header('Content-Type: text/plain');
            echo "Success\n";
            exit;
        } else if ($httpHost === $siteDomain) {
            $did = get_option('didgeridoo_main_did');
        } else if ($domain === $userSubdomain) {

            $users = get_users([
                'meta_key' => 'didgeridoo_user_label',
                'meta_value' => $startingLabel,
            ]);

            if ($users) {
                $user = $users[0];
                $did = get_user_meta($user->ID, 'didgeridoo_user_did', true);
            }
        }

        if (empty($did)) {
            status_header(404);
            header('Content-Type: text/plain');
            echo "Not found\n";
            exit;
        }

        // Output the response as plain text
        header('Content-Type: text/plain');
        echo $did;
        echo "\n";
        exit;
    }
}
