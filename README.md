# Gravity Forms Data Layer Events for WordPress
A very simple WordPress Plugin to Fire off data layer events for Gravity Forms.

Fires off a Google Tag Manager data layer event `gf_form_submission` and includes parameters: 

*  `gf_form_id`  : Form ID in Gravity Forms
*  `gf_form_name` : form name as it exists in Gravity Forms
*  `email` and `emailX` : plain text email. If there are multiple email fields on the form it will increment X and add a new key/value for each. Allows easy deployment to multiple forms as most conversion forms have a single email field; so you can use a simple data layer variable for `email` to capture.
* `email_hashed` and `emailX_hashed` : SHA-256 hashed version of the email

## Features
* Works with all confirmation types (AJAX, Text, Page, Redirect). However we recommend AJAX and Text confirmation as it's best for accessibility, UX, and enhances reliability that any tags will fire correctly
* If Gravity Forms flags a submission as spam, it does not fire


For example:

```
<script>
if(window.self === window.top){
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                "event": "gf_form_submission",
                "gf_form_id": 2,
                "gf_form_name": "simple",
                "email": "testing1@builtbyworkhorse.com",
                "email_hashed": "499039d0728c90994ac99e6ea50355450676bd434a11ec6f86d1f5477429b8c2",
                "email2": "testing2@builtbyworkhorse.com",
                "email2_hashed": "ad0f329c326e80765127fd3019336aac74092fc32fe41e435d38f96cfbacc277",                
            });
        }
</script>
```

Works with all confirmation types (AJAX, text, redirect, new page). However we recommend AJAX as it's best for accessibility and user experience.

## To Do
Fire off data for address and other fields to data layer
