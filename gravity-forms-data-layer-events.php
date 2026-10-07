<?php
/*
Plugin Name:           Gravity Forms Data Layer Events
Plugin URI:            https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
Description:           Fires off a Google Tag Manager datalayer event `gf_form_submission` and includes event parameters when a Gravity Form is submitted. Works with all confirmation types (AJAX, Text, Redirect, Page). See `README.md` for technical details.
Version:               2.1
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

if ( ! function_exists( 'gfdle_normalizeEmail' ) ) {
    /**
     * Normalize an email address for enhanced conversions.
     *
     * @param string $email The email to normalize.
     * @return string The normalized, lowercase email.
     */
    function gfdle_normalizeEmail( $email ) {
        $email = trim( (string) $email );

        $at = strrpos( $email, '@' );
        if ( false === $at ) {
            return strtolower( $email );
        }

        $localPart  = substr( $email, 0, $at );
        $domainPart = substr( $email, $at + 1 );

        $plus = strpos( $localPart, '+' );
        if ( false !== $plus ) {
            $localPart = substr( $localPart, 0, $plus );
        }

        if ( 0 === strcasecmp( $domainPart, 'gmail.com' ) || 0 === strcasecmp( $domainPart, 'googlemail.com' ) ) {
            $localPart = str_replace( '.', '', $localPart );
        }

        return strtolower( $localPart . '@' . $domainPart );
    }
}

/**
 * Append the dataLayer push (and, for redirect confirmations, the redirect) to
 * the Gravity Forms confirmation output.
 *
 * Registered unconditionally: `gform_confirmation` cannot fire unless Gravity
 * Forms is loaded, so this does not depend on plugin load order.
 *
 * @param string|array $confirmation The confirmation message or redirect array.
 * @param array        $form         The form object.
 * @param array        $entry        The entry object.
 * @param bool         $ajax         Whether the form was submitted via AJAX.
 * @return string|array
 */
function gfdle_append_datalayer_event( $confirmation, $form, $entry, $ajax ) {
    // Background / programmatic requests never render a confirmation to a visitor.
    if ( wp_doing_cron() || wp_doing_ajax() ) {
        return $confirmation;
    }

    if ( ! class_exists( 'GFCommon' ) ) {
        return $confirmation;
    }

    // Only target active entries. Forms using gform_disable_entry_creation have
    // no status at all, and those are still genuine visitor submissions.
    $status = isset( $entry['status'] ) ? $entry['status'] : 'active';
    if ( 'active' !== $status ) {
        return $confirmation;
    }

    $data = array(
        'event'        => 'gf_form_submission',
        'gf_form_id'   => (int) rgar( $form, 'id' ),
        'gf_form_name' => (string) rgar( $form, 'title' ),
    );

    // The entry is saved before gform_confirmation fires, so its ID is known
    // here. Skipped when gform_disable_entry_creation leaves no saved entry.
    $entry_id = absint( rgar( $entry, 'id' ) );
    if ( $entry_id > 0 ) {
        $data['gf_entry_id'] = $entry_id;
    }

    // Collect and hash all email fields
    $email_count = 1;
    foreach ( (array) rgar( $form, 'fields' ) as $field ) {
        if ( 'email' !== $field->type ) {
            continue;
        }

        $email_value = rgar( $entry, $field->id );
        if ( '' === trim( (string) $email_value ) ) {
            continue;
        }

        $normalized = gfdle_normalizeEmail( $email_value );
        $key_prefix = ( 1 === $email_count ) ? 'email' : 'email' . $email_count;

        $data[ $key_prefix ]              = $normalized;
        $data[ $key_prefix . '_hashed' ]  = hash( 'sha256', $normalized );

        $email_count++;
    }

    // Get order total if available
    $gf_total_value = GFCommon::get_order_total( $form, $entry );
    if ( ! empty( $gf_total_value ) && (float) $gf_total_value > 0 ) {
        $data['gf_total'] = (float) $gf_total_value;
    }

    // wp_json_encode escapes forward slashes, so a value containing "</script>"
    // cannot break out of the inline script tag.
    $payload = wp_json_encode( $data );
    if ( false === $payload ) {
        return $confirmation;
    }

    // Prepare optional redirect URL
    $redirect_url = '';
    if ( is_array( $confirmation ) && ! empty( $confirmation['redirect'] ) ) {
        $redirect_url = esc_url_raw( $confirmation['redirect'] );
    }

    // Build JavaScript for dataLayer and optional redirect.
    //
    // Under Gravity Forms' legacy AJAX mode this markup is first rendered inside
    // the hidden gform_ajax_frame and then copied into the parent document, so
    // the script can run twice. Resolving `w` to the top window keeps the push
    // and the redirect pointed at the real page in both passes, and the
    // __gfdleRedirected latch on that window makes the redirect fire only once.
    $js  = '(function(){';
    $js .= 'var w;try{w=window.top||window;}catch(e){w=window;}';
    $js .= 'if(window.self===w){w.dataLayer=w.dataLayer||[];w.dataLayer.push(' . $payload . ');}';

    if ( $redirect_url ) {
        $delay = (int) apply_filters( 'gfdle_redirect_delay', 500 );
        $delay = max( 0, $delay );

        $js .= 'if(!w.__gfdleRedirected){w.__gfdleRedirected=true;';
        $js .= 'setTimeout(function(){w.location.replace(' . wp_json_encode( $redirect_url ) . ');},' . $delay . ');}';
    }

    $js .= '})();';

    if ( method_exists( 'GFCommon', 'get_inline_script_tag' ) ) {
        $script_tag = GFCommon::get_inline_script_tag( $js );
    } else {
        $script_tag = '<script>' . $js . '</script>';
    }

    // Append the inline script
    $newConfirmation  = is_string( $confirmation ) ? $confirmation : '';
    $newConfirmation .= $script_tag;

    // Without JavaScript the dataLayer push is moot, but the visitor still needs
    // to reach the redirect target rather than dead-ending on a blank page.
    if ( $redirect_url ) {
        $newConfirmation .= '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
    }

    return $newConfirmation;
}
add_filter( 'gform_confirmation', 'gfdle_append_datalayer_event', 10, 4 );
