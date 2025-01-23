<?php
/*
Plugin Name: Gravity Forms Data Layer Events
Plugin URI: https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
Description: Fires off a Google Tag Manager datalayer event <code>gf_form_submission</code> and includes event parameters when a Gravity Form is submitted. Works with all confirmation types (AJAX, text, redirect, new page). See README.md for technical details.
Author: Workhorse
Version: 1.4
Author URI: https://www.builtbyworkhorse.com/
*/

add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry, $ajax ) {

    // Ensure we only proceed for active entries
    if ($entry['status'] !== 'active') {
        return $confirmation;
    }

    $form_title = $form['title'];
    $email_js_array = '';
    $email_count = 1;

    // Loop through fields to extract email data
    foreach ( $form['fields'] as $field ) {
        if ( $field->type === 'email' ) {
            $email_field_id = $field->id;
            $email_value = rgar( $entry, $email_field_id );

            if ( !empty($email_value) ) {
                $hashed_email = hash('sha256', $email_value);

                if ($email_count === 1) {
                    $email_js_array .= '"email": "' . esc_js($email_value) . '",';
                    $email_js_array .= '"email_hashed": "' . esc_js($hashed_email) . '",';
                } else {
                    $email_js_array .= '"email' . $email_count . '": "' . esc_js($email_value) . '",';
                    $email_js_array .= '"email' . $email_count . '_hashed": "' . esc_js($hashed_email) . '",';
                }
                $email_count++;
            }
        }
    }

    // Handle redirection if set
    $redirect_js = '';
    if (!empty($confirmation['redirect'])) {
        $redirect_js = '
            setTimeout(function() {
                window.location.replace("' . esc_url_raw($confirmation['redirect']) . '");
            }, 500); // Delay to ensure dataLayer.push executes
        ';
    }

    // Build JavaScript for dataLayer push
    $js = '
        if (window.self === window.top) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                "event": "gf_form_submission",
                "gf_form_id": ' . $form['id'] . ',
                "gf_form_name": "' . esc_js($form_title) . '",
                ' . $email_js_array . '
            });
            ' . $redirect_js . '
        }
    ';

    // Add the JavaScript to the confirmation
    $newConfirmation = is_string($confirmation) ? $confirmation : '';
    $newConfirmation .= GFCommon::get_inline_script_tag($js);

    return $newConfirmation;
}, 10, 4 );