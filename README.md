# boxbilling-sumup-payment-plugin
Payment module for processing payments using the SumUp API

I recently wanted clients to be able to manage their own webhosting invoices etc.. - my current hosting provider whom I have a reseller plan with offered WHMCS and their own code but it just didn't work so I looked around for something else.

I found boxbilling - their website will tell you that a license is required for the pro-version but their site simply hasn't been updated in a while and the boxbilling code is now available for free in its entirety. It's a great piece of software but lacking support. The documentation is a bit patchy but I've managed to create a couple of modules, one of which is this one.

A payment processor using SumUp whom I already use for my credit and debit card processing elsewhere.

Drop the file in your box billing installation, bb-library/payment/adaptor folder, then enable it in the configuration of boxbilling.

Once logged in to your sumup account you will first of all need to go to the 'For Developers' section which you'll find under profile. You will need to create some OAuth credentials for Web App but first fill in the section consent screen (the plugin doesn't actually require this but SumUp do before you can create the OAuth stuff). Create client credentials - give the client a name (something like Your company name SumUp payments), web app, and redirect URL (get the redirect URL from the plugins config screen).
Once you've done this download the file as you will need the client_id and client_secret to configure the plugin. You will also need your Merchant ID which you will find under your SumUp profile - profile details, business information.
Now you'll need to ask SumUp to enable the scope 'payments' for you - I started off with test credentials whilst I developed the plugin. It seems to work.
It will probably take a day or 3 for SumUp to come back to you on this. The plugin won't work until 'payments' is enabled. No idea why it isn't enabled by default!

If you find the plugin useful, please consider buying me a beer, you can donate here: 

https://www.paypal.com/donate?hosted_button_id=EPJLLK8V84GFC

If you haven't signed up for SumUp yet, use my referral link and we both get rewarded (at the time of writing, 15 euros each, so that gives you money off your terminal device, well worth having) - http://r.sumup.com/referrals/quskP

## Changelog ##
09/04/21 - CURL_SETOPT_VERIFYHOST was using 1 and needs to be 2 in later versions of PHP
09/04/21 - defined constant names didn't have quotes around them - threw a warning, now fixed
09/04/21 - missed some quotes around $data['totaltopay'] - fixed
09/04/21 - the generateform function provided by the original template expects a url parameter - I hadn't define that variable as I didn't actually use it so I've just set it to the same as the constant CHECKOUT_URL
09/04/21 - had an instance of 'email' which should have been 'pay_to_email' - fixed.
