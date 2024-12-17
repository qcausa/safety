=== WP Fusion - Ecommerce Addon ===
Contributors: verygoodplugins
Tags: wp fusion, ecommerce, woocommerce, easy digital downloads
Requires at least: 4.6
Tested up to: 6.5.0
Stable tag: 1.23.4.2

Connects WordPress ecommerce plugins to your CRM's ecommerce system to record transactions

== Description ==

Connects WordPress ecommerce plugins to your CRM's ecommerce system to record transactions

== Changelog ==

= 1.23.4.2 - 1/29/2024 =
* Fixed a fatal error when syncing pending orders with the CRM and a pending order was created with an empty shipping method, since 1.23.4

= 1.23.4.1 - 1/26/2024 =
* Re-added the `CustomerAuthorizedById` field that was removed from the Salesforce integration in 1.23.4

= 1.23.4 - 1/24/2024 =
* Added support for syncing HighLevel opportunities to different statuses in addition to pipelines and stages
* Added option (under Advanced) to delete the ActiveCampaign Deep Data connection without opening a new one
* Improved - Removed the `CustomerAuthorizedById` from Salesforce order data. It's not a required field and was causing validation issues on some accounts
* Improved - Synced shipping methods will now include the shipping method title instead of just "Shipping"
* Improved - The Ontraport integration will always search for a product by name before creating a new one (to avoid creating duplicate products)
* Improved - [Shipping products with Ontraport](https://wpfusion.com/documentation/ecommerce-tracking/ontraport-ecommerce/#shipping-product) will be created based on the shipping method title, instead of just "Shipping" 
* Improved - If pending orders are enabled for sync with WooCommerce, creating a pending order in the admin will create a corresponding contact record and deal
* Improved - If a pending order is edited in the admin, the deal will automatically be updated in the CRM without having to click Process WP Fusion Actions Again
* Fixed undefined index warnings when editing a Gravity Forms feed for a form that includes payments

= 1.23.3 = 11/10/2023 =
* Fixed `Error: Call to undefined function wc_get_order()` when syncing non-WooCommerce orders to Drip since 1.23.2
* Added support for syncing the checkout URL to Drip with EDD

= 1.23.2 - 11/8/2023 =
* Added support for [syncing MemberPress transaction status changes with sales pipelines](https://wpfusion.com/documentation/membership/memberpress/#sales-pipelines) in Brevo, HighLevel HubSpot, and Zoho
* Added support for syncing the checkout URL to Drip with WooCommerce, CartFlows, and FunnelKit
* Added support for updating existing deals with Brevo
* Added a warning when the Reepay payment gateway is configured in a way that will send duplicate data to the CRM
* Improved - When "Skip already processed" is un-checked during the WooCommerce Products batch operation, the cached CRM product ID will be cleared out and looked up again via API call
* Improved error handling with ActiveCampaign
* Fixed the WooCommerce Products batch operation not working unless Sync Product Edits was enabled in the Enhanced Ecommerce settings
* Fixed PHP warning `Undefined array key "sku"` in Salesforce integration when creating a new product without an SKU
* Fixed PHP warning `Unknown property: name` when syncing MemberPress transactions from the `manual` gateway
* Fixed a bug with WooCommerce Discount Rules Pro where cart-based discounts were being synced without a title
* Fixed Ontraport integration not syncing partially refunded orders where a single item's quantity was refunded to zero
* Fixed fatal error adding new WooCommerce products with Drip, MailerLite, and Zoho when Sync Product Edits was enabled in the WP Fusion settings
* Fixed PHP warning performing initial sync with HubSpot when no products existed in HubSpot

= 1.23.1 - 8/17/2023 =
* Improved - If you are running a WooCommerce Orders batch operation via the core plugin, and the orders have already been processed by Enhanced Ecommerce, they will be skipped (and a notice will be logged)
* Fixed Cancelled WooCommerce orders not updating the order total to 0 in ActiveCampaign
* Fixed HighLevel integration only adding opportunities to the first pipeline (requires Refresh Tags and Fields and selecting a pipeline again in the setup)
* Fixed Brevo integration recording orders as successfully synced even when a deal stage hadn't been selected in the settings
* Fixed MemberPress integration not syncing transactions that had already been synced, despite "Skip already processed" being un-checked
* Fixed MemberPress Transactions export operation picking up fallback and subscription_confirmation transactions
* PHP 8.2.8 compatibility

= 1.23.0 - 7/24/2023 =
* Added [Salesforce integration](https://wpfusion.com/documentation/ecommerce-tracking/salesforce-ecommerce/)
* Improved - If a deal is deleted in HubSpot, and updating the deal fails, WP Fusion will clear the cached deal ID and create a new deal
* Fixed MailerLite integration not handling errors related to looking up a shop ID or creating a new shop
* Fixed CRMs with products (AgileCRM, Infusionsoft, Ontraport, Zoho) updating the product name in the CRM based on the WordPress product name even when Sync Product Edits was disabled
* Fixed free MemberPress transactions not being synced

= 1.22.0 - 6/19/2023 =
* Added [MailerLite integration](https://wpfusion.com/documentation/ecommerce-tracking/mailerlite-ecommerce/)
* Fixed - v1.20.0 added WooCommerce HPOS support, but forgot to declare the compatibility. The plugin now shows up as compatible with HPOS

= 1.21.1 - 6/5/2023 =
* Added filter `wpf_ecommerce_nationbuilder_add_donation`
* Improved - Custom HubSpot deal properties added using the `wpf_ecommerce_hubspot_add_deal` for the v1 HubSpot API will now automatically be fixed to be compatible with the v3 API
* Fixed WooCommerce Susbcriptions signup fees getting synced twice since 1.21.0
* Removed the deprecated option to add a note to HubSpot deals, for new WP Fusion installs

= 1.21.0 - 5/24/2023 =
* Added [HighLevel integration](https://wpfusion.com/documentation/ecommerce-tracking/highlevel-ecommerce/)
* Added filter `wpf_ecommerce_brevo_add_deal` (alias of `wpf_ecommerce_sendinblue_add_deal`)
* Updated Sendinblue integration to Brevo
* Updated HubSpot integration to use new v3 API
* Fixed an issue with WooCommerce refunds whereby if you did a partial refund on an order, and then subsequently fully refunded the order, the amount of the initial partial refund would be synced twice (causing a negative balance)
* Fixed WooCommerce refunds being synced to Drip twice
* Fixed signup fees getting synced as line items with every WooCommerce subscriptions renewal order
* Fixed duplicate transaction data being sent when using the Memberpress WooCommerce Plus plugin

= 1.20.3 - 3/28/2023 =
* Fixed signup fees not being synced with WooCommerce Subscriptions
* Fixed variation names missing from product names since 1.20.0
* Fixed prices for variable products not syncing correctly at checkout since 1.20.0
* Fixed PHP warnings updating existing products with Ontraport

= 1.20.2 - 3/22/2023 =
* Improved - Calling wp_fusion_ecommerce()->woocommerce->send_order_data() will now always sync the order, regardless of its status (fixes an issue with FunnelKit and the Run on Primary Order Accepted setting)
* Improved - Infusionsoft / Keap refunds will now be [synced with a credit note](https://wpfusion.com/documentation/ecommerce-tracking/infusionsoft-ecommerce/#refunds) to prevent the order from displaying as having a balance due
* Fixed fatal error `Call to a member function get_name() on bool` when trying to export WooCommerce orders that contained deleted products, since 1.20.0
* Fixed error `Unsupported operand types: string * int` when syncing products that have no value (like Gift Cards) with ActiveCampaign

= 1.20.1 - 3/2/2023 =
* Fixed fatal error (missing second argument) running the "WooCommerce Products (Ecommerce addon)" batch operation since 1.20.0

= 1.20.0 - 2/27/2023 =
* Added - With CRMs that support products (AgileCRM, Drip, HubSpot, Keap / Infusionsoft, Ontraport, and Zoho), you can now sync products when they are created or updated in WooCommerce, instead of when the order is placed
* Added support for [WooCommerce High Performance Order Storage ](https://developer.woocommerce.com/2022/09/14/high-performance-order-storage-progress-report/)
* Improved - When looking up an Infusionsoft product, if a match isn't found by name, an additional search will be performed by SKU before creating a new product
* Improved - The HubSpot integration can now update deals created by the MakeWebBetter HubSpot for WooCommerce plugin
* Fixed broken link to Give payments in ActiveCampaign (and other CRMs with payment links)
* Fixed `Undefined variable $payment` in LifterLMS integration

= 1.19.3 - 12/6/2022 =
* Added multi-currency support to Zoho integration
* Added option to reset the ActiveCampaign Deep Data connection ID and open a new connection
* Improved - Variable WooCommerce products will now be synced to Drip with their variation ID for the `product_variant_id` parameter
* Improved - Clicking Process WP Fusion Actions Again on a WooCommerce order will now trigger the Enhanced Ecommerce addon to re-sync the invoice as well
* Fixed Completed WooCommerce orders being exported to Drip as Fulfilled, which didn't correctly set the order date
* Fixed "Invalid data passed for field" error updating deal stages with Zoho

= 1.19.2 - 10/26/2022 =
* Fixed fatal error refunding MemberPress transactions with ActiveCampaign since 1.19.0

= 1.19.1 - 10/11/2022 =
* Improved - Refunded item quantities will now be synced with Drip, and trigger an order refunded event
* Improved - Removed legacy Orders API and Events API integrations with Drip in favor of the Shopper Activity API
* Improved - New Completed WooCommerce orders will be sent to Drip as "fulfilled" instead of being synced as "placed" and then updated to "fulfilled" a moment later
* Fixed the Record a Converson Event option not working with Drip and the Shopper Activity API
* Fixed multiple partial refunds on WooCommerce orders not correctly being synced with Drip (only the most recent refund was synced)
* Fixed partial refund amounts not being rounded to two decimal places

= 1.19.0 - 8/29/2022 =
* Addded [Sendinblue integration](https://wpfusion.com/documentation/ecommerce-tracking/sendinblue-enhanced-ecommerce/)
* Added [Gravity Forms integration](https://wpfusion.com/documentation/ecommerce-tracking/ecommerce-overview/#gravity-forms)
* Added support for WooCommerce refunds and partial refunds with ActiveCampaign, AgileCRM, HubSpot, NationBuilder, and Zoho
* Added custom Drip properties for payment method and discount code
* Improved Zoho error handling for creating products and linking them with deals
* Improved / Fixed - Refunds will be synced to Drip with the date of the refund as the `occurred_at` parameter, in order to trigger the Refunded An Order workflow trigger
* Fixed Zoho integration not correctly detecting when the Products module was unavailable on the account
* Fixed MemberPress integration syncing confirmation transactions with PayPal (Standard)
* Developers: Moved EDD Recurring Payments `edd_recurring_add_subscription_payment` hook from priority 10 to 20 so it runs after the renewal meta keys have been set for the order by EDD

= 1.18.6 - 4/20/2022 =
* Improved - the WooCommerce Orders (Ecommerce Addon) batch operation no longer requires the orders to be processed by the core plugin first
* Improved - If a non-paid order status (i.e. Pending, Failed) is linked to a pipeline in Zoho or HubSpot, then it will be exported when running a WooCommerce Orders batch operation
* Fixed PHP warning loading ActiveCampaign deal stages if no deal stages exist
* Fixed fatal error in ActiveCampaign integration if someone checked out and their existing contact record had been deleted

= 1.18.5 - 3/8/2022 =
* Fixed fatal error changing WooCommerce order statuses when WooCommerce Subscriptions wasn't active, since 1.18.4

= 1.18.4 - 3/8/2022 =
* Added option to re-process already exported orders using the batch operations with WooCommerce, EDD, MemberPress, and GiveWP
* Improved - Sync Products with HubSpot will now be enabled by default
* Improved - Sync Orders with Drip will now be enabled by default
* Improved - If a WooCommerce order is marked completed, the `date_completed` will be synced for the order date instead of the `date_paid`
* Improved - If a customer checks out with a different email from their AC Deep Data customer email, and this results in an error, WP Fusion will re-send the order data with the email from their contact record
* Improved - ActiveCampaign Deep Data connection will no longer be deleted when disabling Deep Data

= 1.18.3 - 12/28/2021 =
* Added warning about syncing Pending Payment orders with HubSpot and Zoho
* Added `wpf_ecommerce_ontraport_add_product` filter
* Added SKU field to Ontraport products
* Improved support for taxes with Ontraport — Only order items that have tax will be markd `taxable`
* Improved - If WooCommerce Subscriptions is active and the site is a staging site, order status changes will not be synced to the CRM
* Improved ActiveCampaign error handling so that it now looks at the response code instead of message (some errors were not being caught properly with non-English accounts)
* Fixed incorrect totals when syncing Ontraport product prices tax-inclusive when some cart items were tax exempt
* Fixed free shipping showing up in Infusionsoft as a $0 order adjustment

= 1.18.2 - 11/1/2021 =
* Fixed coupons / discounts getting synced twice since 1.18.1

= 1.18.1 - 10/19/2021 =
* Added support for product discounts added as adjustments to individual line items for manually created WooCommerce orders
* Improved - When bulk-editing the status for more than 20 WooCommerce orders, status changes will not be synced to the CRM (to prevent a gateway timeout)
* Fixed duplicate record error syncing products with HubSpot that have no SKU
* Fixed EDD discount code not syncing with ActiveCampaign Deep Data

= 1.18.0 - 8/20/2021 =
* Added support for coupons with ActiveCampaign Deep Data connections
* Added support for exporting non-paid order statuses with HubSpot and Zoho
* Improved - If a deal ID is saved for a HubSpot deal, the existing deal will be updated rather than create a duplicate
* Improved - When a MemberPress transaction is refunded, the corresponding deal/invoice will be marked refunded in the CRM
* Added wpf_ecommerce_ontraport_add_transaction filter
* Fixed PHP warnings during initial Zoho sync if there were no Accounts in Zoho

= 1.17.10 - 7/1/2021 =
* Added third parameter $order_id to wpf_ecommerce_hubspot_add_line_item filter
* Improved - Cancelled WooCommerce orders will now update their ActiveCampaign Deep Data total to $0.00
* Updated for compatibility with Abandoned Cart Addon v1.7.0

= 1.17.9 - 5/31/2021 =
* Added "MemberPress transactions (Ecommerce addon)" batch operation
* Fixed MemberPress integration trying to sync transactions for users with no contact record
* Fixed errors when WP Fusion wasn't connected to a CRM
* Fixed Ontraport tax object options not populating in dropdowns
* Added wpf_ecommerce_activecampaign_add_deal_note filer
* Added wpf_ecommerce_hubspot_add_line_item filter
* Added wpf_ecommerce_hubspot_change_deal_stage filter

= 1.17.8 - 3/23/2021 =
* Added wpf_ecommerce_zoho_add_product filter
* Added wpf_ecommerce_zoho_add_deal filter
* Added wpf_ecommerce_activecampaign_add_deep_data
* Added note to the settings about WP Fusion-customer tag being applied with ActiveCampaign
* Added note to the logs when an EDD order was blocked from being synced due to the payment gateway not being enabled
* Fixed ActiveCampaign Deep Data not respecting Staging Mode

= 1.17.7 - 2/15/2021 =
* Improved handling for duplicate SKUs with HubSpot
* Fixed HubSpot not loading more than 100 products

= 1.17.6 - 2/10/2021 =
* Added tracking code slug setting for NationBuilder
* Fixed PHP warning loading tax rates from Ontraport when there are no tax rates in the account
* Fixed PHP warning loading available products from HubSpot

= 1.17.5 - 1/12/2021 =
* Fixed error updating WP Fusion and the Ecommerce Addon simultaneously
* Fixed error activating the Enhanced Ecommerce addon with WP Fusion Lite

= 1.17.4 - 1/8/2021 =
* Added download image URL to ecommerce data with Easy Digital Downloads
* Added download description to ecommerce data with Easy Digital Downloads and supported CRMs
* Improved - You can now prevent an order from being synced to the CRM by returning false from the wpf_ecommerce_order_args filter
* Fixed HubSpot not loading more than 10 products

= 1.17.3 - 12/31/2020 =
* Added super secret debug tool to clear _wpf_ec_complete flag from Give donations
* Added wpf_ecommerce_order_args filter
* Improved - ActiveCampaign integration will now give a registered user's email address priority over the billing email
* ActiveCampaign module will now record a warning if a transaction comes through but neither Deep Data nor Deals are enabled
* Fixed Give export operation ignoring renewal orders
* Fixed Drip invoice ID not being saved correctly after logging a new order

= 1.17.2 - 11/30/2020 =
* Fixed uncaught error when registering a new product with HubSpot failed
* Fixed PHP notices when syncing ActiveCampaign deal owners

= 1.17.1 - 11/30/2020 =
* Improved - When ActiveCampaign gives a "The ecomOrder already exists in the system." error, WP Fusion will try and update the existing order
* Improved - Disabled the Total Revenue field when ActiveCampaign Deep Data or Drip Shopper Activity was in use
* Fixed "Sync Attributes" in WooCommerce being enabled despite being un-checked

= 1.17 - 11/23/2020 =
* Added support for products and line items with HubSpot

= 1.16.2 - 11/19/2020 =
* Added support for tax objects with Ontraport
* Added option to send product prices tax-inclusive with Ontraport
* Added Fees support to Infusionsoft integration
* Added Give Donations (Ecommerce addon) batch operation
* Improved logging for deal status changes

= 1.16.1 - 8/25/2020 =
* Added Drip for WooCommerce compatibility notice
* Fixed "The ecomOrder currency was not provided" errors with ActiveCampaign and free EDD orders
* Fixed order totals not syncing correctly with Infusionsoft
* Fixed product prices not syncing correctly when prices included tax

= 1.16 - 6/24/2020 =
* Added batch operation for EDD orders
* Added compatibility notices when other CRM ecommerce plugins are detected
* Refactored ActiveCampaign integration
* Improved error handling for ActiveCampaign Deep Data and Customer IDs

= 1.15.1 - 5/2/2020 =
* Fixed "No method matching arguments" error recording payments in Infusionsoft

= 1.15 - 4/27/2020 =
* Order status changes in WooCommerce will now update order statuses in Drip
* Added wpf_ecommerce_hubspot_add_engagement filter
* Added wpf_ecommerce_hubspot_add_deal filter
* Fixed MemberPress free trials with 0 Net creating orders
* Restrict Content Pro integration bugfixes

= 1.14.3 - 3/26/2020 =
* Added support for syncing product addons with Ontraport
* ActiveCampaign integration will now mark carts as recovered if a pending cart is found
* Fixed syncing WooCommerce order fees with negative values to Drip

= 1.14.2 = 2/11/2020 =
* Fixed crash created while logging error from a failed API call to register a product in Zoho

= 1.14.1 = 2/3/2020 =
* Fixed "Invalid data for Amount" error with Zoho

= 1.14 - 1/31/2020 =
* Added MemberPress support
* Added support for Products with Zoho
* Added support for assigning a default owner to new ActiveCampaign deals
* ActiveCampaign pipelines / stages are no longer limited to 20
* WooCommerce integration will now inherit store settings regarding product prices being inclusive vs exclusive of tax

= 1.13.6 - 1/6/2020 =
* Added shipping support to Ontraport integration
* Zoho integration will now attempt to assign a new deal to the account of an existing contact
* Removed taxes, shipping, and discounts as separate products from ActiveCampaign Deep Data orders
* Fixed error caused with WooCommerce Dynamic Pricing and Drip's Shopper Activity API
* Fixed time zone offset calculation with ActiveCampaign

= 1.13.5 - 11/25/2019 =
* Fixes for line items and fees causing problems with missing product titles in Drip and ActiveCampaign

= 1.13.4 - 11/21/2019 =
* WooCommerce variation images will now be sent in detailed order data
* Added option to disable syncing product attributes with WooCommerce

= 1.13.3 - 11/18/2019 =
* Fixed bug with ActiveCampaign rejecting orders with incomplete tax data

= 1.13.2 - 10/21/2019 =
* Added support for Fees with WooCommerce
* Improved discounts and shipping support for ActiveCampaign
* Fixed conflict with Event Espresso Stripe gateway

= 1.13.1 = 10/9/2019 =
* Fixed UTC+0 timezone causing errors with Drip and NationBuilder

= 1.13 - 10/8/2019 =
* Added NationBuilder integration
* Fixed time zone calculation for occurred_at with Drip

= 1.12 - 9/18/2019 =
* Added refund support to ActiveCampaign
* Added Give (donations) support
* Added batch tool for resyncing AC customer IDs
* Increased product image size sent to Drip and ActiveCampaign
* Improved logging
* Fixed error in Drip with line item descriptions longer than 255 characters

= 1.11 - 8/26/2019 =
* Added Zoho integration
* Added product price re-calculation for Ontraport when discounts are used
* Added support for custom WooCommerce order numbers with ActiveCampaign

= 1.10 - 8/7/2019 =
* Added Event Espresso support
* Fixes for deleted products in WooCommerce

= 1.9.3 - 7/28/2019 =
* Added support for sale prices on products with Ontraport
* Fixed additional "Properties Value is not an integer" warnings with Drip Events

= 1.9.2 - 7/8/2019 =
* Added refund support for Infusionsoft
* Added option to send product prices tax-inclusive
* Added product image to ActiveCampaign order payload
* Added product categories to Drip Shopper Activity data
* Added product image to Drip order payload
* Added support for free trials in Woo Subscriptions

= 1.9.1 - 5/17/2019 =
* AgileCRM performance improvements
* Updated Drip with option for newer v3 API
* Fixed "Properties value is not an integer" error in Drip

= 1.9 - 4/12/2019 =
* Added LifterLMS support

= 1.8.2 - 4/8/2019 =
* AgileCRM bugfixes

= 1.8.1 - 4/6/2019 =
* Order date tweaks in WooCommerce
* Better date handling for orders with AgileCRM
* Fix for variation product IDs not saving

= 1.8 - 2/15/2019 =
* Added refunds support to Ontraport
* Added option to update deal stages in HubSpot when WooCommerce order status is changed

= 1.7.3 - 2/5/2019 =
* Added tax line item support with Drip
* Drip now receives proper order date (and time zone)

= 1.7.2 - 1/25/2019 =
* Error handling for WooCommerce order meta data that is not a meta object

= 1.7.1 - 1/23/2019 =
* Option for turning off Conversion tracking with Drip
* Added product ID into product dropdowns for Infusionsoft / Ontraport
* Integration classes can now be accessed via wp_fusion_ecommerce()->integrations->woocommerce (etc)

= 1.7 - 12/24/2018 =
* Hubspot integration
* Restrict Content Pro integration
* Error handling for "The integration already exists in the system." message with ActiveCampaign
* Added EDD payment gateway selector
* Added SKU to Ontraport product data

= 1.6.2 - 11/11/2018 =
* Added bulk processing tool for WooCommerce orders
* Fix for & symbols in product names causing errors with Infusionsoft
* Added support for EDD Discounts Pro

= 1.6.1 - 10/23/2018 =
* Bugfixes for addons / variations handling with Infusionsoft

= 1.6 - 9/13/2018 =
* Added support for WooCommerce Addons in ecommerce data
* Improvements to support changes in Drip's ecommerce functionality
* Amounts less than a dollar now syncing correctly with ActiveCampaign's Deep Data

= 1.5.1 - 2/26/2018 =
* AgileCRM bugfixes
* Fixed product lookup issues for Infusionsoft products with ampersands in the title

= 1.5 - 2/3/2018 = 
* Added AgileCRM ecommerce support
* Addded Ontraport referral tracking

= 1.4 =
* Added Drip ecommerce support
* Fixed GMT offset issues with Infusionsoft

= 1.3.5 =
* Order dates now use the date from the order instead of the current time
* Ontraport bugfixes

= 1.3.4 =
* Russian character encoding fixes
* Added admin tools for resetting wpf_ec_complete hooks on WooCommerce / EDD orders
* Prevent duplicate orders being sent on WooCommerce subscription auto-renewals

= 1.3.3 =
* Disabled invoices being sent by Ontraport
* Added backwards compatibility support for WC < 3.0
* Integrated WPF logging tools
* AC Deep Data integrations now triggers the "Makes A Purchase" action
* Added error handling and logging for API failures

= 1.3.2 =
* Misc. ActiveCampaign improvements
* Fixed Infusionsoft affiliate cookie expiration

= 1.3.1 =
* Bugfixes for WooCommerce 3.0.3

= 1.3 =
* Added Ontraport ecommerce integration
* Updated to support WooCommerce 3.0

= 1.2 =
* Added ActiveCampaign Deep Data Integration
* Better support for coupons using Easy Digital Downloads
* Added support for Infusionsoft referral partner links

= 1.1 =
* Added support for EDD Recurring Payments

= 1.0 =
* Further fixes to Asian character encodings

= 0.9 =
* Updates for Woo Subscriptions 2.x

= 0.8 =
* Support for Infusionsoft products with special character encodings

= 0.7 =
* Support for WooCommerce variations
* Ability to manually associate WooCommerce products with Infusionsoft products
* Speed improvements for ActiveCampaign users with no configured sales pipelines

= 0.6 =
* Pull revenue field before calculating if local record is empty
* Better handling for batch processing of old orders

= 0.5 =
* Fix for special characters in product names in Infusionsoft

= 0.4 =
* Bugfixes

= 0.3 =
* Added ActiveCampaign integration

= 0.2 =
* Added Woo Subscriptions support

= 0.1 =
* Initial release
