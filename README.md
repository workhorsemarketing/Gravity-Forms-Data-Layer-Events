# Gravity Forms Data Layer Events for WordPress

A very simple WordPress Plugin to Fire off data layer events for Gravity Forms.

## Features

Fires off a Google Tag Manager data layer event `gf_form_submission` and includes parameters: 

*  `gf_form_id`  : Form ID in Gravity Forms
*  `gf_form_name` : form name as it exists in Gravity Forms
*  `emailX` : plain text email. If there are multiple email fields on the form it will increment X and add a new key/value for each
* `emailX_hashed` : SHA-256 hashed version of the email
* `gf_total` : Form total if form collects $ / is selling a product

### Customizable Redirect delay

When using a redirect confirmation, there is a 1 second delay to give time for tags to fire in GTM. if you'd like to delay the submission longer you can add this filter to your theme:

```
add_filter( 'gfdle_redirect_delay', function() {
    return 1500; // 1.5 seconds
} );
```

## Example dataLayer push

```
<script>
if(window.self === window.top){
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                "event": "gf_form_submission",
                "gf_form_id": 2,
                "gf_form_name": "Form Name Here",
                "email": "testing1@builtbyworkhorse.com",
                "email_hashed": "499039d0728c90994ac99e6ea50355450676bd434a11ec6f86d1f5477429b8c2",
                "email2": "testing2@builtbyworkhorse.com",
                "email2_hashed": "ad0f329c326e80765127fd3019336aac74092fc32fe41e435d38f96cfbacc277",
                "gf_total": 10                
            });
        }
</script>
```

## Email Normalization

In addition email addresses are normalized before hashing to ensure accurate matching:

* Converts to lowercase
* Plus addressing removed (`myemail+something@domain.com` becomes `myemail@domain.com`)
* Gmail addresses also have dots remove from local part (`my.email@gmail.com` becomes `myemail@gmail.com`

## Compatibility

* WordPress 5.0+
* Gravity Forms 2.4+
* Gravity Forms Stripe Add-on
* PHP 7.4+
* Common caching and optimization plugins
* Works with AJAX submission (recommended)
* All Confirmation Types:  Text, Page, and Redirect

## License

This plugin is released under the MIT License.

See the `LICENSE` file for the full license text, including the warranty disclaimer and limitation of liability.

## PII Disclaimer

Do not send PII to third-party services unless you know what you're doing and have consent. Email address should be hashed first in GTM via a User-Provided Data variable or other method. [Review Google Analytics policies](https://support.google.com/analytics/answer/6366371?hl=en#zippy=%2Cin-this-article), for example.