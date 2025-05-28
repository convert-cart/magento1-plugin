# Convert Cart Magento 1 Plugin

## Table of Contents
- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Uninstall](#uninstall)
- [Contact](#contact)

## Introduction
Welcome to the Magento 1 Plugin by Convert Cart. This plugin integrates seamlessly with Magento 1 stores, enabling the tracking of user behavior and synchronizing crucial data such as product catalogs, order histories, customer profiles, and category information to Convert Cart servers. This synchronization powers our recommendation engine, providing personalized and data-driven insights to enhance your ecommerce operations.

## Features
- Script injection on the frontend for user behavior tracking.
- Synchronization of product, order, customer, and category data to Convert Cart servers for recommendations.
- Product deletion tracking to avoid recommending deleted products to store visitors.

## Installation
1. **Backup your store** (recommended).
2. **Download** the latest version of the plugin from your Convert Cart dashboard or from your account manager.
3. **Extract** the downloaded archive.
4. **Upload** the contents to your Magento root directory, merging folders when prompted.
5. **Clear Cache:** In your Magento admin panel, go to `System > Cache Management` and refresh/flush all caches.
6. **Logout & Login:** Log out and log back in to your Magento admin panel.
7. **Enable Modules:**
   - Go to `System > Configuration > Advanced > Advanced` and ensure `Convertcart_Analytics` and `Convertcart_Sync` are enabled.

## Configuration
1. In your Magento admin, go to `System > Configuration > Convertcart` (usually in the left menu).
2. Enter your Convert Cart API credentials (provided by Convert Cart support or your dashboard).
3. Adjust any additional settings as needed.
4. Save the configuration.

## Troubleshooting
If you encounter issues, try the following steps:
1. Ensure the modules are enabled: Go to `System > Configuration > Advanced > Advanced` and check for `Convertcart_Analytics` and `Convertcart_Sync`.
2. Clear Magento cache: `System > Cache Management`.
3. Check the Magento `var/log` directory for any error messages.
4. If the issue persists, contact Convert Cart support.

## Uninstall
To uninstall the plugin:
1. Disable the modules in `System > Configuration > Advanced > Advanced`.
2. Remove the `Convertcart` folders from `app/code/community/`, and the related XML files from `app/etc/modules/`.
3. Clear Magento cache.

## Contact
Please contact sales@convertcart.com if any issues occur during the integration process.

## License
This plugin is proprietary and provided by Convert Cart. All rights reserved.
