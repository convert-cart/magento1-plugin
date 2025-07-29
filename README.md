# Convert Cart Magento 1 Plugin

![Magento 1](https://img.shields.io/badge/Magento-1.x-orange.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
  - [Composer Installation](#composer-installation)
  - [Manual Installation](#manual-installation)
- [Configure Domain ID](#configure-domain-id)
- [Troubleshooting](#troubleshooting)
- [Uninstall](#uninstall)
- [Development](#development)
- [Contact](#contact)

## Introduction

Welcome to the Magento 1 Plugin by Convert Cart. This plugin integrates seamlessly with Magento 1.x ecommerce websites, enabling the tracking of user behavior. Additionally, it synchronizes crucial data such as product catalogs, order histories, customer profiles, and category information to our servers on a regular basis. This synchronization powers our recommendation engine, providing personalized and data-driven insights to enhance your ecommerce operations.

## Features

- Script injection on the frontend for user behavior tracking
- Synchronization of product, order, customer, and category data to Convert Cart servers for recommendations
- Product deletion tracking to avoid recommending deleted products to store visitors
- Comprehensive event tracking system
- Support for both guest and logged-in customer tracking
- Configurable data synchronization intervals
- Robust error handling and logging

## Installation

### Manual Installation

1. **Backup your store** (recommended).
2. **Download** the latest version of the plugin from your Convert Cart dashboard or from your account manager.
3. **Extract** the downloaded archive.
4. **Upload** the contents to your Magento root directory, merging folders when prompted.
5. **Clear Cache:** In your Magento admin panel, go to `System > Cache Management` and refresh/flush all caches.
6. **Logout & Login:** Log out and log back in to your Magento admin panel.
7. **Enable Module:**
   - Go to `System > Configuration > Advanced > Advanced` and ensure `Convertcart` is enabled (the unified module replaces the old Analytics and Sync modules).

### Composer Installation (Beta)

If you use Composer with Magento 1, you can install the plugin by adding our repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/convert-cart/magento1-plugin"
        }
    ],
    "require": {
        "convertcart/magento1-plugin": "dev-main"
    }
}
```

Then run:
```bash
composer update convertcart/magento1-plugin
```

## Configure Domain ID

Please reach out to your Customer Support Manager to configure your domain with Convert Cart. You'll need to provide them with your store's base URL and any other required information.

## Troubleshooting

If you encounter issues, try the following steps:

1. **Check Module Status**:
   - Go to `System > Configuration > Advanced > Advanced`
   - Ensure `Convertcart` is enabled
   - If not enabled, enable it and clear the cache

2. **Clear Cache**:
   - Go to `System > Cache Management`
   - Select all cache types
   - From the **Actions** dropdown, select **Refresh**
   - Click **Submit**

3. **Check Logs**:
   - Check the Magento `var/log` directory for any error messages
   - Look for files like `system.log`, `exception.log`, and `convertcart.log`

4. **File Permissions**:
   Ensure the following directories and files have the correct permissions (usually 755 for directories and 644 for files):
   ```
   app/code/community/Convertcart/
   app/etc/modules/Convertcart_All.xml
   var/convertcart/
   ```

5. **Server Requirements**:
   - PHP 5.6 or later
   - cURL extension enabled
   - allow_url_fopen enabled

## Uninstall

### Manual Uninstallation

1. **Disable the Module**:
   - Go to `System > Configuration > Advanced > Advanced`
   - Find `Convertcart` in the modules list
   - Set it to "Disabled"
   - Save the configuration

2. **Remove Files**:
   Remove the following files and directories:
   ```
   app/code/community/Convertcart/
   app/etc/modules/Convertcart_All.xml
   app/design/frontend/base/default/layout/convertcart.xml
   app/design/frontend/base/default/template/convertcart/
   js/convertcart/
   var/convertcart/
   ```

3. **Clear Cache**:
   - Go to `System > Cache Management`
   - Flush all caches

4. **Remove Database Tables (Optional)**:
   If you want to completely remove all traces, you'll need to drop the following tables (backup first!):
   ```sql
   DROP TABLE IF EXISTS convertcart_analytics;
   DROP TABLE IF EXISTS convertcart_sync;
   ```
   And remove configuration paths:
   ```sql
   DELETE FROM core_config_data WHERE path LIKE 'convertcart/%';
   ```

## Versioning & Migration

### Current Version: 1.5.0+

### Migration from Older Versions

#### From v1.4.x and below:
- The Analytics and Sync modules have been unified into a single Convertcart module
- If you're upgrading from an older version:
  1. Backup your database and files
  2. Uninstall the old modules:
     - Disable `Convertcart_Analytics` and `Convertcart_Sync` in `System > Configuration > Advanced`
     - Remove the old module directories:
       ```
       app/code/community/Convertcart/Analytics/
       app/code/community/Convertcart/Sync/
       app/etc/modules/Convertcart_Analytics.xml
       app/etc/modules/Convertcart_Sync.xml
       ```
  3. Install the new version following the installation instructions above

#### Configuration Changes
- All configuration is now centralized under `System > Configuration > Convertcart`
- The new version maintains backward compatibility with existing data
- Some configuration paths have been updated for consistency

## Contact
Please contact sales@convertcart.com if any issues occur during the integration process.

## License
This plugin is proprietary and provided by Convert Cart. All rights reserved.
