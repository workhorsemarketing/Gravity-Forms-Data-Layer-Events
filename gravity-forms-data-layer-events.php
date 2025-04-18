<?php
/*
Plugin Name:           Gravity Forms Data Layer Events
Plugin URI:            https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
Description:           Fires off a Google Tag Manager dataLayer event on Gravity Forms confirmations.
Version:               2.0
Requires at least:     6.7.2
Requires PHP:          7.4
Author:                Workhorse
Author URI:            https://www.builtbyworkhorse.com/
Text Domain:           gravity-forms-data-layer-events
Domain Path:           /languages
License:               MIT
License URI:           https://mit-license.org/
GitHub Plugin URI:     https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function gfdle_load_textdomain() {
    load_plugin_textdomain(
        'gravity-forms-data-layer-events',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'init', 'gfdle_load_textdomain' );

if ( ! class_exists( 'GFCommon' ) ) {
    return;
}

if ( ! function_exists( 'gfdle_normalizeEmail' ) ) {
    /**
     * Normalize an email address for enhanced conversions.
     *
     * @param string $email The email to normalize.
     * @return string The normalized, lowercase email.
     */
    function gfdle_normalizeEmail( $email ) {
        list( $localPart, $domainPart ) = explode( '@', $email, 2 );
        if ( strpos( $localPart, '+' ) !== false ) {
            $localPart = substr( $localPart, 0, strpos( $localPart, '+' ) );
        }
        if ( strcasecmp( $domainPart, 'gmail.com' ) === 0 ) {
            $localPart = str_replace( '.', '', $localPart );
        }
        $normalizedEmail = $localPart . '@' . $domainPart;
        return strtolower( $normalizedEmail );
    }
}

if ( ! wp_doing_cron() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) ) {
    add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry, $ajax ) {
        // Only target active entries
        if ( $entry['status'] !== 'active' ) {
            return $confirmation;
        }

        $form_title     = $form['title'];
        $email_js_array = '';
        $email_count    = 1;

        // Collect and hash all email fields
        foreach ( $form['fields'] as $field ) {
            if ( $field->type === 'email' ) {
                $email_value = rgar( $entry, $field->id );
                if ( ! empty( $email_value ) ) {
                    $normalized    = gfdle_normalizeEmail( $email_value );
                    $hashed_email  = hash( 'sha256', $normalized );

                    $key_prefix = ( 1 === $email_count ) ? 'email' : 'email' . $email_count;
                    $email_js_array .= sprintf(
                        '"%1$s": "%2$s",',
                        esc_js( $key_prefix ),
                        esc_js( $normalized )
                    );
                    $email_js_array .= sprintf(
                        '"%1$s_hashed": "%2$s",',
                        esc_js( $key_prefix ),
                        esc_js( $hashed_email )
                    );

                    $email_count++;
                }
            }
        }

        // Get order total if available
        $gf_total_value   = GFCommon::get_order_total( $form, $entry );
        $gf_total_snippet = '';
        if ( ! empty( $gf_total_value ) && floatval( $gf_total_value ) > 0 ) {
            $gf_total_snippet = '"gf_total": ' . floatval( $gf_total_value ) . ',';
        }

        // Prepare optional redirect URL
        $redirect_url = '';
        if ( ! empty( $confirmation['redirect'] ) ) {
            $redirect_url = esc_url_raw( $confirmation['redirect'] );
        }

        // Build JavaScript for dataLayer and optional redirect
        $js = <<<JS
if (window.self === window.top) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        "event": "gf_form_submission",
        "gf_form_id": {$form['id']},
        "gf_form_name": "{$form_title}",
        {$email_js_array}
        {$gf_total_snippet}
    });
}
JS;
    if ( $redirect_url ) {
        $delay = apply_filters( 'gfdle_redirect_delay', 500 );
        $js .= "\nsetTimeout(function() { window.location.replace(\"{$redirect_url}\"); }, {$delay});";
    }


        // Append the inline script
        $newConfirmation  = is_string( $confirmation ) ? $confirmation : '';
        $newConfirmation .= GFCommon::get_inline_script_tag( $js );

        return $newConfirmation;
    }, 10, 4 );
}