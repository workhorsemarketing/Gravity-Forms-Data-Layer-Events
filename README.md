# Gravity Forms Data Layer Events for WordPress
A very simple WordPress Plugin to Fire off data layer events for Gravity Forms.

Fires off a Google Tag Manager data layer event `gf_form_submission` and includes parameters: 

*  `gf_form_id`  : Form ID in Gravity Forms
*  `gf_form_name` : form name as it exists in Gravity Forms
*  `emailX` : plain text email. If there are multiple email fields on the form it will increment X and add a new key/value for each
* `emailX_hashed` : SHA-256 hashed version of the email

Works with all confirmation types (AJAX, text, Redirect, new page). However we recommend AJAX as it's best for accessibility and user experience.