# WP Fusion #

WP Fusion connects your WordPress website to your CRM or marketing automation system.


### For end users

All new users who register on your site will be synced to your CRM of choice, with support for any number of custom fields. Tags and/or lists can also be assigned at time of registration.

After registration, future profile updates are kept in sync with their CRM contact records.

### For developers

WP Fusion provides an extensible framework for connecting WordPress to leading CRMs and marketing automation tools.

WP Fusion handles authentication, sanitization of data, and error reporting.

WP Fusion also standardizes many common API operations. For example:

```php
$args = array(
	'user_email'	=> 'newuser@example.com',
	'first_name'	=> 'First Name'
);

$contact_id = wp_fusion()->crm->add_contact( $args );
```

This code will add a new contact to the active CRM and return the contact ID.

For more information see [the WP Fusion User Class](https://wpfusion.com/documentation/advanced-developer-tutorials/wp-fusion-user-class/), the [CRM Class](https://wpfusion.com/documentation/advanced-developer-tutorials/how-wp-fusion-interfaces-with-multiple-crms/) and the [Developer Reference](https://wpfusion.com/documentation/#developer).

### Supported CRMs

* ActiveCampaign
* AgileCRM
* Autonami
* Autopilot
* BirdSend
* Bento
* Capsule
* Constant Contact
* ConvertKit
* Copper
* Customerly
* Customer.io
* Drift
* Drip
* EmailOctopus
* Emercury
* Encharge
* Engage
* EngageBay
* Flexie
* FluentCRM (Same site or REST API)
* GetResponse
* Gist
* Groundhogg (Same site or REST API)
* Growmatik
* HighLevel
* HubSpot
* Infusionsoft
* Intercom
* Jetpack CRM
* Kartra
* Klaviyo
* Klick-Tipp
* Loopify
* MailChimp
* MailEngine
* MailerLite
* Mailjet
* MailPoet
* Maropost
* Microsoft Dynamics 365
* Moosend
* Mautic
* NationBuilder
* Omnisend
* Ontraport
* Ortto
* Pipedrive
* Platform.ly
* PulseTechnologyCRM
* Quentn
* Salesflare
* Salesforce
* SendFox
* SendinBlue
* Sendlane
* Tubular
* UserEngage
* WP ERP
* Zoho

## Installation ##

For detailed setup instructions for each CRM, visit the official [Documentation](https://wpfusion.com/documentation/getting-started/installation-guide/) page.

1. You can clone the GitHub repository: `https://github.com/verygoodplugins/wp-fusion-lite.git`
2. Or download it directly as a ZIP file: `https://github.com/verygoodplugins/wp-fusion-lite/archive/master.zip`

This will download the latest copy of WP Fusion Lite.

## Development ##

### Running Tests ###

WP Fusion uses PHPUnit for testing. To set up the test environment:

1. Make sure you have Composer installed
2. Run `composer install` to install dependencies
3. Configure a local WordPress installation (we recommend Local by Flywheel)
4. The tests will use your local WordPress installation's database

To run the tests:
```bash
# Run all tests
./vendor/bin/phpunit

# Run a specific test file
./vendor/bin/phpunit tests/includes/functions/Test_Phone_Number.php

# Run a specific test method
./vendor/bin/phpunit --filter test_us_number_formatting
```

#### VS Code Integration ####

If you're using VS Code, install the "PHP Test Explorer" extension to run tests directly from the editor. The test configuration is already included in the repository.

## Changelog ##

See readme.txt.

## Bugs ##
If you find an issue, let us know [here](https://github.com/verygoodplugins/wp-fusion-lite/issues?state=open)!

## How can I report security bugs? ##

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/wp-fusion-lite)

## Support ##
This is a developer's portal for WP Fusion Lite and should _not_ be used for support. Please visit the [support page](https://wpfusion.com/support/contact) if you need to submit a support request.
