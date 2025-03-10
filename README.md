# Enhanced WooCommerce Role-Based Pricing

A comprehensive solution for WooCommerce stores that need to display different prices based on user roles.

## Description

Enhanced WooCommerce Role-Based Pricing allows you to set different pricing structures for various user groups in your WooCommerce store. This plugin is ideal for businesses that need to show different prices to:

* Guest visitors (not logged in)
* Regular registered customers
* Dealers or wholesale customers
* Any other custom user roles defined in your WordPress site

### Key Features

* **Multi-Tiered Pricing System**: Set different regular and sale prices for each user role
* **Guest Pricing**: Special pricing options for non-logged in visitors
* **User Role Integration**: Seamlessly works with existing WordPress roles and the Members plugin
* **Product-Level Control**: Configure pricing on a per-product basis
* **Admin Column View**: Quickly see which products have role-based pricing from your product list
* **Settings Page**: Configure global plugin settings
* **Variable Product Support**: Role-based pricing works with variable products and their variations

### Use Cases

* **Wholesale/Retail Split**: Show retail prices to regular customers and discounted prices to wholesale accounts
* **Member Benefits**: Offer special pricing to registered members only
* **Dealer Networks**: Provide dealer-specific pricing to approved business partners
* **Guest Incentives**: Encourage account creation by showing different prices to guests

## Installation

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under WooCommerce → Role-Based Pricing

## Usage

### Setting Product Prices

1. Edit any product in WooCommerce
2. Navigate to the "Role Pricing" tab in the Product Data section
3. Set prices for each user role:
   * Guest (non-logged in) pricing
   * Pricing for each defined user role (customer, subscriber, dealer, etc.)
4. For each role, you can set both regular and sale prices

### Plugin Settings

1. Go to WooCommerce → Role-Based Pricing
2. Configure global settings:
   * Enable/disable guest pricing
   * Enable/disable role-based pricing
   * Set price display format preferences
3. View a list of available user roles that can have custom pricing

## Requirements

* WordPress 5.0 or higher
* WooCommerce 3.0 or higher
* PHP 7.0 or higher

## Integration with Members Plugin

This plugin is designed to work seamlessly with the "Members – Membership & User Role Editor Plugin". It will automatically detect and use any custom roles created with the Members plugin.

## Technical Notes

* Role prices are stored as product meta data using WooCommerce's standard pricing format
* The plugin uses WordPress and WooCommerce hooks to modify price display and calculations
* Custom user roles are fully supported without any additional configuration

## Support

For help with this plugin, please contact the plugin author or submit an issue on the plugin repository.

## License

This plugin is released under the GPL v2 or later license.
