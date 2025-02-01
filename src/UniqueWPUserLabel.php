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

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueWPUserLabel implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currentUser = wp_get_current_user();

        $users = get_users([
            'meta_key' => 'didgeridoo_user_label',
            'meta_value' => $value,
            'exclude' => $currentUser->ID,
        ]);

        if (count($users) > 0) {
            $fail(__('The user handle is already taken.', 'didgeridoo'));
        }
    }
}
