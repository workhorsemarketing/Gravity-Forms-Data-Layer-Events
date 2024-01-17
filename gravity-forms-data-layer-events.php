<?php
/*
Plugin Name: Gravity Forms Data Layer Events
Plugin URI: https://github.com/workhorsemarketing/Gravity-Forms-Data-Layer-Events
Description: Fires off a Google Tag Manager data layer event <code>gf_form_submission</code> and includes parameters <code>gf_form_id</code> and <code>gf_form_name</code> when a Gravity Form is submitted. Works with all confirmation types (Ajax, text, Redirect, new page).
Author: Workhorse Marketing
Version: 1.1
Author URI: https://www.workhorsemkt.com/
*/
add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry, $ajax ) {

	if ($entry['status'] !== 'active') {
		return $confirmation;
	}

	$form_title = $form['title'];
	$redirect = "";
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
				"gf_form_name": "' . esc_js($form_title) . '"
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
