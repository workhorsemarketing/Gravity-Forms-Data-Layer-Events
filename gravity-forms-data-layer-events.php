<?php
/*
Plugin Name: Gravity Forms Data Layer Events
Plugin URI: https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
Description: Fires off a Google Tag Manager datalayer event <code>gf_form_submission</code> and includes event parameters when a Gravity Form is submitted. Works with all confirmation types (AJAX, text, redirect, new page). See README.md for technical details.
Author: Workhorse
Version: 1.3
Author URI: https://www.builtbyworkhorse.com/
*/

add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry, $ajax ) {

    if ($entry['status'] !== 'active') {
        return $confirmation;
    }

    $form_title = $form['title'];
    $redirect = "";

    $email_js_array = '';
    $email_count = 1;

    foreach ( $form['fields'] as $field ) {
        if ( $field->type === 'email' ) {
            $email_field_id = $field->id;
            $email_value = rgar( $entry, $email_field_id );

            if ( !empty($email_value) ) {
                $hashed_email = hash('sha256', $email_value);

                // Set the first email key as "email" and "email_hashed"
                if ($email_count === 1) {
                    $email_js_array .= '"email": "' . esc_js($email_value) . '",';
                    $email_js_array .= '"email_hashed": "' . esc_js($hashed_email) . '",';
                } else {
                    // Subsequent emails follow "email2", "email2_hashed", etc.
                    $email_js_array .= '"email' . $email_count . '": "' . esc_js($email_value) . '",';
                    $email_js_array .= '"email' . $email_count . '_hashed": "' . esc_js($hashed_email) . '",';
                }
                $email_count++;
            }
        }
    }

    if(!empty($confirmation['redirect'])){
        $redirect .= ',
            "eventCallback": () => {
                window.location.replace("'.esc_url_raw($confirmation['redirect']).'");
            }
        ';
    }
    
    $js = '
        if(window.self === window.top){
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                "event": "gf_form_submission",
                "gf_form_id": ' . $form['id'] . ',
                "gf_form_name": "' . esc_js($form_title) . '",
                ' . $email_js_array . '
                ' . $redirect . '
            });
        }
    ';

    $newConfirmation = is_string($confirmation) ? $confirmation : '';

    if(!empty($confirmation['redirect'])){
        // $js .= "setTimeout(() => window.location.replace('".esc_url_raw($confirmation['redirect'])."'), 10000);";
    }

    $newConfirmation .= GFCommon::get_inline_script_tag( $js );

    return $newConfirmation;
}, 10, 4 );