# Gravity Forms Data Layer Events for WordPress
A very simple WordPress Plugin to Fire off data layer events for Gravity Forms.

Fires off a Google Tag Manager data layer event `gf_form_submission` and includes parameters: 

*  `gf_form_id`  : Form ID in Gravity Forms
*  `gf_form_name` : form name as it exists in Gravity Forms
*  `emailX` : plain text email. If there are multiple email fields on the form it will increment X and add a new key/value for each
* `emailX_hashed` : SHA-256 hashed version of the email

For example:

```
<script>
if(window.self === window.top){
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                "event": "gf_form_submission",
                "gf_form_id": 2,
                "gf_form_name": "simple",
                "email1": "testing1@test.com",
                "email1_hashed": "852b59fb6bccb19c3801eebe0386b9988fe93c38fd21e527b1a282099378c673",
                "email2": "testing2@test.com",
                "email2_hashed": "e936dea8682a1810c2bfce78b4c355cbb4baac6ff494f05ab6ee87b8761cb071",                
            });
        }
</script>
```


Works with all confirmation types (AJAX, text, Redirect, new page). However we recommend AJAX as it's best for accessibility and user experience.