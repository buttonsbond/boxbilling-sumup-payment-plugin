# boxbilling-sumup-payment-plugin
Payment module for processing payments using the SumUp API

I recently wanted clients to be able to manage their own webhosting invoices etc.. - my current hosting provider whom I have a reseller plan with offered WHMCS and their own code but it just didn't work so I looked around for something else.

I found boxbilling - their website will tell you that a license is required for the pro-version but their site simply hasn't been updated in a while and the boxbilling code is now available for free in its entirety. It's a great piece of software but lacking support. The documentation is a bit patchy but I've managed to create a couple of modules, one of which is this one.

A payment processor using SumUp whom I already use for my credit and debit card processing elsewhere.

Drop the file in your box billing installation, bb-library/payment/adaptor folder, then enable it in the configuration of boxbilling.

You'll need to ask SumUp to give you the API credentials you need as well as the scope 'payments' - I started off with test credentials whilst I developed the plugin. It seems to work.

If you find the plugin useful, please consider buying me a beer, you can donate here: 

<form action="https://www.paypal.com/donate" method="post" target="_top">
<input type="hidden" name="hosted_button_id" value="EPJLLK8V84GFC" />
<input type="image" src="https://www.paypalobjects.com/en_US/ES/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
<img alt="" border="0" src="https://www.paypal.com/en_ES/i/scr/pixel.gif" width="1" height="1" />
</form>
