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

use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Translation\Translator;
use Illuminate\Container\Container;

class SettingsPage
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_menu', [$this, 'addAdminMenu']);

        add_option('didgeridoo_subdomain', '');
        add_option('didgeridoo_main_did', '');
        add_option('didgeridoo_did_list', '');

        add_action('rest_api_init', [$this, 'registerApiRoutes']);
    }

    // Action function for the above hook
    function addAdminMenu()
    {
        add_options_page(
            'DIDgeridoo Plugin',    // Page title
            'DIDgeridoo',           // Menu title
            'manage_options',       // Capability
            'didgeridoo',           // Menu slug
            [$this, 'display']      // Function to display the page
        );
    }

    public static function enqueueScripts()
    {
        wp_enqueue_script(
            'didgeridoo-script',
            plugin_dir_url(__DIR__) . 'build/script.js',
            ['wp-element', 'wp-api-fetch'],
            null,
            true
        );

        add_filter('script_loader_tag', function ($tag, $handle) {
            if ('didgeridoo-script' === $handle) {
                return str_replace(' src', ' type="module" src', $tag);
            }
            return $tag;
        }, 10, 2);
    }

    public static function enqueueStyles()
    {
        wp_enqueue_style(
            'didgeridoo-style',
            plugin_dir_url(__DIR__) . 'build/script.css',
            [],
            null
        );
    }

    public function display()
    {
?>
        <div class="wrap">
            <div id="didgeridoo-settings"></div>
        </div>
<?php
    }

    function registerApiRoutes()
    {
        register_rest_route(
            'didgeridoo/v1',
            '/options',
            [
                'methods'  => 'GET',
                'callback' => [$this, 'settingsPageReadOptionsCallback'],
                'permission_callback' => '__return_true'
            ]
        );

        register_rest_route(
            'didgeridoo/v1',
            '/options',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'settingsPageUpdateOptionsCallback'],
                'permission_callback' => '__return_true'
            ]
        );

        register_rest_route(
            'didgeridoo/v1',
            '/test-dns',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'settingsPageTestDNSCallback'],
                'permission_callback' => '__return_true'
            ]
        );
    }

    function settingsPageReadOptionsCallback($data)
    {
        // Check the capability
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_read_error',
                __('Sorry, you are not allowed to update the DIDgeridoo options.', 'didgeridoo'),
                ['status' => 403]
            );
        }

        $siteUrl = get_site_url();
        $urlParts = parse_url($siteUrl);
        $siteDomain = $urlParts['host'];

        //Generate the response
        $response = [];
        $response['site_domain'] = $siteDomain;
        $response['didgeridoo_subdomain'] = get_option('didgeridoo_subdomain');
        $response['didgeridoo_main_did'] = get_option('didgeridoo_main_did');
        $response['didgeridoo_did_list'] = get_option('didgeridoo_did_list');
        $response['didgeridoo_enable_org_mode'] = get_option('didgeridoo_enable_org_mode');

        //Prepare the response
        $response = new \WP_REST_Response($response);

        return $response;
    }

    function settingsPageUpdateOptionsCallback($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_update_error',
                __('Sorry, you are not allowed to update the DIDgeridoo options.', 'didgeridoo'),
                ['status' => 403]
            );
        }

        $translator = new Translator(new \Illuminate\Translation\ArrayLoader(), 'en');
        $container = new Container();
        $validator = (new ValidatorFactory($translator, $container))->make(
            $request->get_params(),
            [
                'didgeridoo_main_did' => ['regex:/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/i'],
                'didgeridoo_enable_org_mode' => ['boolean'],
                'didgeridoo_subdomain' => ['exclude_if:didgeridoo_enable_org_mode,false', 'regex:/^(?![-.])[a-zA-Z0-9.-]+(?<![-.])$/i'],
            ],
            [
                'didgeridoo_subdomain.regex' => __('The subdomain may only contain letters, numbers, dashes, and periods, and may not start or end with a dash or period.', 'didgeridoo'),
                'didgeridoo_enable_org_mode.boolean' => __('The organization mode setting must be a boolean.', 'didgeridoo'),
                'didgeridoo_main_did.regex' => __('DID invalid.', 'didgeridoo'),
            ]
        );

        if ($validator->fails()) {
            $response = new \WP_REST_Response($validator->errors()->toArray(), '400');
            return $response;
        }

        $validatedData = $validator->validated();

        //Get the data and sanitize
        $didgeridoo_main_did = $validatedData['didgeridoo_main_did'];
        $didgeridoo_enable_org_mode = $validatedData['didgeridoo_enable_org_mode'];
        $didgeridoo_subdomain = $validatedData['didgeridoo_subdomain'];

        update_option('didgeridoo_main_did', $didgeridoo_main_did);
        update_option('didgeridoo_enable_org_mode', $didgeridoo_enable_org_mode);
        update_option('didgeridoo_subdomain', $didgeridoo_subdomain);

        $response = new \WP_REST_Response(__('Data successfully added.', 'didgeridoo'), '200');

        return $response;
    }

    function settingsPageTestDNSCallback($request)
    {
        // Check the capability
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_read_error',
                __('Sorry, you are not allowed to update the DIDgeridoo options.', 'didgeridoo'),
                ['status' => 403]
            );
        }

        $translator = new Translator(new \Illuminate\Translation\ArrayLoader(), 'en');
        $container = new Container();
        $validator = (new ValidatorFactory($translator, $container))->make(
            $request->get_params(),
            [
                'didgeridoo_subdomain' => ['exclude_if:didgeridoo_enable_org_mode,false', 'regex:/^(?![-.])[a-zA-Z0-9.-]+(?<![-.])$/i'],
            ],
            [
                'didgeridoo_subdomain.regex' => __('The subdomain may only contain letters, numbers, dashes, and periods, and may not start or end with a dash or period.', 'didgeridoo'),
            ]
        );

        if ($validator->fails()) {
            $response = new \WP_REST_Response($validator->errors()->toArray(), '400');
            return $response;
        }

        $didgeridooSubdomain = $validator->validated()['didgeridoo_subdomain'];

        $siteUrl = get_site_url();
        $urlParts = parse_url($siteUrl);
        $siteDomain = $urlParts['host'];

        $urlToCheck = 'didgeridoo-test' . $didgeridooSubdomain . '.' . $siteDomain . '/.well-known/atproto-did';

        $response = wp_remote_get($urlToCheck);

        if (is_wp_error($response)) {
            return new \WP_REST_Response(['didgeridoo_subdomain' => [
                __('The URL is not reachable.', 'didgeridoo')
            ]], '400');
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == 200) {
            return new \WP_REST_Response(__('Subdomain is reachable.', 'didgeridoo'), '200');
        } else {
            return new \WP_REST_Response(['didgeridoo_subdomain' => [
                __('The URL is not reachable.', 'didgeridoo')
            ]], '400');
        }
    }
}
