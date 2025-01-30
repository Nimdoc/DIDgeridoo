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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action(('admin_enqueue_scripts'), [$this, 'enqueue_styles']);
        add_action('admin_menu', [$this, 'didgeridoo_add_admin_menu']);

        add_option('didgeridoo_subdomain', '');
        add_option('didgeridoo_main_did', '');
        add_option('didgeridoo_did_list', '');

        add_action('rest_api_init', [$this, 'rest_api_register_route']);
    }

    // Action function for the above hook
    function didgeridoo_add_admin_menu()
    {
        add_options_page(
            'DIDgeridoo Plugin',    // Page title
            'DIDgeridoo',           // Menu title
            'manage_options',       // Capability
            'didgeridoo',           // Menu slug
            [$this, 'display']      // Function to display the page
        );
    }

    public static function enqueue_scripts()
    {
        wp_enqueue_script(
            'didgeridoo-script',
            plugin_dir_url(__DIR__) . 'build/script.js',
            ['wp-element', 'wp-api-fetch'],
            null,
            true
        );

        // Add type="module" attribute to the script
        add_filter('script_loader_tag', function($tag, $handle) {
            if ('didgeridoo-script' === $handle) {
                return str_replace(' src', ' type="module" src', $tag);
            }
            return $tag;
        }, 10, 2);
    }

    public static function enqueue_styles()
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
            <div id="name-id-list"></div>
        </div>
        <?php
    }

    /*
    * Add custom routes to the Rest API
    */
    function rest_api_register_route()
    {
        //Add the GET 'react-settings-page/v1/options' endpoint to the Rest API
        register_rest_route(
            'react-settings-page/v1', '/options', [
                'methods'  => 'GET',
                'callback' => [$this, 'rest_api_react_settings_page_read_options_callback'],
                'permission_callback' => '__return_true'
            ]
        );

        register_rest_route(
            'react-settings-page/v1', '/options', [
                'methods'             => 'POST',
                'callback'            => [$this, 'rest_api_react_settings_page_update_options_callback'],
                'permission_callback' => '__return_true'
            ]
        );
    }

    /*
    * Callback for the GET 'react-settings-page/v1/options' endpoint of the Rest API
    */
    function rest_api_react_settings_page_read_options_callback($data)
    {
        // Check the capability
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_read_error',
                'Sorry, you are not allowed to view the options.',
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

    function rest_api_react_settings_page_update_options_callback($request)
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
                'didgeridoo_did_list' => ['exclude_if:didgeridoo_enable_org_mode,false', 'required', 'json'],
            ],
            [
                'didgeridoo_subdomain.regex' => __('The subdomain may only contain letters, numbers, dashes, and periods, and may not start or end with a dash or period.', 'didgeridoo'),
                'didgeridoo_enable_org_mode.boolean' => __('The organization mode setting must be a boolean.', 'didgeridoo'),
                'didgeridoo_main_did.regex' => __('DID invalid.', 'didgeridoo'),
                'didgeridoo_did_list'=> __('Something went wrong with the DID list. Please refresh your page.', 'didgeridoo'),
            ]
        );

        if ($validator->fails()) {
            $response = new \WP_REST_Response($validator->errors()->toArray(), '400');
            return $response;
        }

        $enableOrgMode = $validator->safe()->only(['didgeridoo_enable_org_mode'])['didgeridoo_enable_org_mode'];

        if ($enableOrgMode) {
            $didList = json_decode($request->get_param('didgeridoo_did_list'), true);

            $didListValidator = (new ValidatorFactory($translator, $container))->make(
                $didList,
                [
                    '*.name' => ['required', 'max:63', 'distinct', 'regex:/^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/i'],
                    '*.did' => ['required', 'max:127', 'regex:/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/i'],
                    '*.last_updated' => ['required', 'date'],
                ],
                [
                    '*.name.required' => __('The user handle is required.', 'didgeridoo'),
                    '*.name.max' => __('The user handle may not be more than 63 characters.', 'didgeridoo'),
                    '*.name.distinct' => __('The user handle must be unique.', 'didgeridoo'),
                    '*.name.regex' => __('The user handle may only contain letters, numbers, and dashes, and may not start or end with a dash.', 'didgeridoo'),
                    '*.did.regex' => __('DID invalid.', 'didgeridoo'),
                    '*.did.required' => __('The DID is required.', 'didgeridoo'),
                    '*.did.max' => __('The DID may not be more than 127 characters.', 'didgeridoo'),
                ]
            );

            if ($didListValidator->fails()) {
                $response = new \WP_REST_Response($didListValidator->errors()->toArray(), '400');
                return $response;
            }
        }

        $validatedData = $validator->validated();

        //Get the data and sanitize
        $didgeridoo_main_did = $validatedData['didgeridoo_main_did'];
        $didgeridoo_enable_org_mode = $validatedData['didgeridoo_enable_org_mode'];

        update_option('didgeridoo_main_did', $didgeridoo_main_did);
        update_option('didgeridoo_enable_org_mode', $didgeridoo_enable_org_mode);

        if ($enableOrgMode) {
            $didgeridoo_subdomain = $validatedData['didgeridoo_subdomain'];
            $didgeridoo_did_list_string = $validatedData['didgeridoo_did_list'];

            $didgeridoo_did_list = json_decode($didgeridoo_did_list_string, true);

            // Sort the DID list by name ascending
            usort($didgeridoo_did_list, function($a, $b) {
                return $a['name'] <=> $b['name'];
            });

            $didgeridoo_did_list_string = json_encode($didgeridoo_did_list);

            update_option('didgeridoo_subdomain', $didgeridoo_subdomain);
            update_option('didgeridoo_did_list', $didgeridoo_did_list_string);
        }

        $response = new \WP_REST_Response(__('Data successfully added.', 'didgeridoo'), '200');

        return $response;

    }
}
