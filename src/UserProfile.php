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

namespace DIDgeridoo;

use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Translation\Translator;
use Illuminate\Container\Container;
use Closure;

class UserProfile
{
    public function __construct()
    {
        $enableOrgMode = get_option('didgeridoo_enable_org_mode');

        if ($enableOrgMode) {
            add_action('show_user_profile', [$this, 'userProfileFields']);
            add_action('edit_user_profile', [$this, 'userProfileFields']);
            add_action('personal_options_update', [$this, 'saveUserProfileFields']);
            add_action('edit_user_profile_update', [$this, 'saveUserProfileFields']);

            add_action('user_profile_update_errors', [$this, 'validateUserProfileFields'], 10, 3);
        }
    }

    public function validateUserProfileFields(\WP_Error $errors, $update, $user)
    {
        if (!current_user_can('edit_user', $user->ID) || !check_admin_referer('update-user_' . $user->ID)) {
            return false;
        }

        $translator = new Translator(new \Illuminate\Translation\ArrayLoader(), 'en');
        $container = new Container();
        $validator = (new ValidatorFactory($translator, $container))->make(
            $_POST,
            [
                'didgeridoo_user_label' => [
                    'max:63',
                    'not_in:didgeridoo-test',
                    'regex:/^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/i',
                    function (string $attribute, mixed $value, Closure $fail) use ($user) {
                        $usersWithLabel = get_users([
                            'meta_key' => 'didgeridoo_user_label',
                            'meta_value' => $value,
                            'exclude' => $user->ID,
                        ]);

                        if (count($usersWithLabel) > 0) {
                            $fail(__('The user handle is already taken.', 'didgeridoo'));
                        }
                    },
                ],
                'didgeridoo_user_did' => [
                    'max:127',
                    'regex:/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/i'
                ],
            ],
            [
                'didgeridoo_user_label.max' =>          __('The user handle may not be more than 63 characters.', 'didgeridoo'),
                'didgeridoo_user_label.not_in' =>       __('The user handle is reserved.', 'didgeridoo'),
                'didgeridoo_user_label.distinct' =>     __('The user handle must be unique.', 'didgeridoo'),
                'didgeridoo_user_label.regex' =>        __('The user handle may only contain letters, numbers, and dashes, and may not start or end with a dash.', 'didgeridoo'),
                'didgeridoo_user_did.max' =>            __('The DID may not be more than 127 characters.', 'didgeridoo'),
                'didgeridoo_user_did.regex' =>          __('The DID is invalid.', 'didgeridoo'),
            ]
        );

        foreach ($validator->errors()->toArray() as $field => $errorList) {
            foreach ($errorList as $error) {
                $errors->add($field, $error);
            }
        }

        return $errors;
    }

    public function userProfileFields($user)
    {
        $siteUrl = get_site_url();
        $urlParts = wp_parse_url($siteUrl);
        $siteDomain = $urlParts['host'];

        $didgeridooSubdomain = get_option('didgeridoo_subdomain');

        $userSubdomain = ($didgeridooSubdomain ? $didgeridooSubdomain . '.' : '') . $siteDomain;

?>
        <h3><?php esc_html_e("ATProto DID Settings", "didgeridoo"); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="didgeridoo_user_label"><?php esc_html_e("User Handle", "didgeridoo"); ?></label></th>
                <td>
                    <input
                        type="text"
                        name="didgeridoo_user_label"
                        id="didgeridoo_user_label"
                        value="<?php echo esc_attr(get_the_author_meta('didgeridoo_user_label', $user->ID)); ?>"
                        class="regular-text" />
                    .<?php echo esc_attr($userSubdomain) ?>
                    <br />
                    <span class="description"><?php esc_html_e("Please enter user handle", "didgeridoo"); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="didgeridoo_user_did"><?php esc_html_e("DID", "didgeridoo"); ?></label></th>
                <td>
                    <input
                        type="text"
                        name="didgeridoo_user_did"
                        id="didgeridoo_user_did"
                        value="<?php echo esc_attr(get_the_author_meta('didgeridoo_user_did', $user->ID)); ?>"
                        class="regular-text" />
                    <br />
                    <span class="description"><?php esc_html_e("Please enter your DID", "didgeridoo"); ?></span>
                </td>
            </tr>
            <tr>
        </table>
<?php
    }


    public function saveUserProfileFields($user_id)
    {
        if (!current_user_can('edit_user', $user_id) || !check_admin_referer('update-user_' . $user_id)) {
            return false;
        }

        $userLabel = !empty($_POST['didgeridoo_user_label']) ? sanitize_text_field(wp_unslash($_POST['didgeridoo_user_label'])) : '';
        $userDID = !empty($_POST['didgeridoo_user_did']) ? sanitize_text_field(wp_unslash($_POST['didgeridoo_user_did'])) : '';

        update_user_meta($user_id, 'didgeridoo_user_label', $userLabel);
        update_user_meta($user_id, 'didgeridoo_user_did', $userDID);
    }
}
