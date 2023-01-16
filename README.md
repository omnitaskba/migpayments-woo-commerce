# Installation
1. Uninstall existing plugin if exists
2. Go to "Plugins" page, click "Add new" and upload plugin files
3. Activate the plugin after installation

# Settings

Visit "Plugins" page, and next to MigPayments WooCommerce click "Settings" to edit plugin settings or
visit WooCommerce/Settings/Payments page and next to Migpayments click "Manage"
 
1. Sandbox mode - choose weathere to use sandbox or production API (default is sandbox)
2. Migpayments API Token - enter your Migpayments Shop API token (sandbox or production)
3. Available Crypto Currencies - choose available payment currencies
4. Crypto Totals Box - Choose if payment estimate is shown on checkout page
5. Set the plugin title, description and buttons and labels texts
6. Webhook Whitelisted IPs - Required to change only if you get instructions from Migpayments support.

# Customization
To customize plugin's layout, add additional CSS referring to plugin's elements ID's and classes.
To add custom styles navigate to Appearance than Customize section of your dashboard, scroll down to the bottom of the page and click Additional CSS.

### Example CSS:
    .wc-migpayments-crypto-total{
	    font-size:20px;
    }`