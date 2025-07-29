# Development Setup for ConvertCart Magento 1 Plugin

This document provides instructions for setting up the development environment and running code quality tools.

## Prerequisites

- PHP 5.6 - 7.4 (Magento 1.x compatible)
- Composer
- PHP_CodeSniffer
- PHPMD (PHP Mess Detector)
- PHP-CS-Fixer (optional, for auto-fixing code style)

## Installation

1. Install dependencies:
   ```bash
   composer install
   ```

2. The project comes with pre-configured coding standards for Magento 1 compatibility. No additional standards need to be installed globally.

## Available Commands

### Linting and Code Quality

- **Check for syntax errors**:
  ```bash
  composer run lint
  ```

- **Run PHP_CodeSniffer**:
  ```bash
  composer run cs-check
  ```

- **Fix code style issues automatically**:
  ```bash
  composer run cs-fix
  ```

- **Run PHP Mess Detector** (minimal ruleset):
  ```bash
  composer run phpmd
  ```

- **Run PHP Mess Detector** (full ruleset - may show warnings):
  ```bash
  composer run phpmd-full
  ```

- **Run all checks**:
  ```bash
  composer run check
  ```

## Code Quality Standards

### PHP_CodeSniffer (PHPCS)

The project uses PSR-2 coding standards with some exceptions for Magento 1 compatibility. The configuration is in `phpcs.xml`.

Key exceptions for Magento 1:
- Class names follow Magento 1 naming conventions (not PSR-2)
- Method names follow Magento 1 naming conventions (camelCase)

### PHP Mess Detector (PHPMD)

Two configurations are provided:

1. **Minimal** (`phpmd-minimal.xml`): Used by default, focuses on critical issues only
   - Excludes MissingImport warnings (not applicable for Magento 1's non-namespaced code)
   - Excludes ElseExpression warnings (common in Magento 1 code)
   - Excludes StaticAccess warnings (necessary for Magento 1)
   - Excludes unused code warnings that are common in Magento 1

2. **Full** (`phpmd.xml`): More comprehensive but may show warnings for Magento 1 patterns

### Code Organization

The codebase follows these organizational principles:

1. **Helper Classes**: Specialized helper classes are used to group related functionality:
   - `Convertcart_Helper_Analytics_ViewTracking`: Page view tracking
   - `Convertcart_Helper_Analytics_WishlistTracking`: Wishlist operations
   - `Convertcart_Helper_Analytics_CompareTracking`: Compare operations
   - `Convertcart_Helper_Analytics_CartTracking`: Cart operations
   - `Convertcart_Helper_Analytics_CheckoutTracking`: Checkout operations
   - `Convertcart_Helper_Analytics_ReviewTracking`: Review operations
   - `Convertcart_Helper_Analytics_CustomerTracking`: Customer operations
   - `Convertcart_Helper_Analytics_NewsletterTracking`: Newsletter operations
   - `Convertcart_Helper_Analytics_ProductTracking`: Product operations
   - `Convertcart_Helper_Analytics_Initialization`: Initialization operations

2. **Observer Pattern**: The main `Analytics/Observer.php` class delegates to specialized helpers

## Common Issues and Solutions

### PHP Warnings about Use Statements

Magento 1 doesn't use PHP namespaces, so `use` statements will cause warnings. Instead of using `use` statements, use fully qualified class names.

### PHPMD MissingImport Warnings

PHPMD may warn about missing imports for classes like `Exception`, `Zend_Controller_Request_Http`, etc. These warnings are excluded in the minimal configuration since they don't apply to Magento 1's non-namespaced code.

### Long Class Names

Magento 1 often has long class names due to its naming conventions. Use aliases in `config.xml` when possible:

```xml
<helpers>
    <convertcart>
        <class>Convertcart_Helper</class>
        <cc_analytics>Convertcart_Helper_Analytics</cc_analytics>
    </convertcart>
</helpers>
```

Then reference as `Mage::helper('cc_analytics/viewTracking')` instead of `Mage::helper('convertcart/analytics_viewTracking')`.

### IDE Integration

#### PHPStorm/IntelliJ
1. Install PHP Annotations, PHP Toolbox, and PHP Advanced AutoComplete plugins
2. Enable PHP_CodeSniffer:
   - Go to Settings > PHP > Code Sniffer
   - Set PHP Code Sniffer path to `vendor/bin/phpcs`
   - Click "Validate" to verify setup
3. Enable PHP_CodeSniffer inspection:
   - Go to Settings > Editor > Inspections > PHP > PHP Code Sniffer validation
   - Check "Show warnings as" and select your preferred warning level
   - Set Coding standard to "Custom" and point to the `phpcs.xml` file

#### VS Code
1. Install PHP Intelephense or PHP IntelliSense extension
2. Install PHP CS Fixer extension
3. Add the following to your VS Code settings:
   ```json
   "php.validate.executablePath": "path/to/php",
   "phpcs.standard": "phpcs.xml",
   "phpmd.ruleset": "phpmd.xml",
   "php-cs-fixer.autoFixOnSave": true,
   "php-cs-fixer.config": ".php-cs-fixer.php"
   ```

## Code Style

This project follows Magento 1 coding conventions with modern code quality tools. The configuration balances Magento 1 compatibility with modern best practices:

### Magento 1 Conventions (Preserved)

- Class names with underscores (e.g., `Convertcart_Model_Sync`)
- No namespaces (Magento 1 doesn't support them)
- Methods with underscore prefixes (e.g., `_construct()`, `_init()`)
- Magento 1 factory pattern (`Mage::getModel()`, `Mage::helper()`)

### Modern Standards (Applied where possible)

- Line length: 120 characters
- Consistent indentation (4 spaces)
- Use single quotes for strings when possible
- Use strict type comparisons when possible
- Add PHPDoc blocks for all classes, methods, and properties

### Tool Configurations

- **PHPCS**: Configured to use PSR-2 but excludes rules that conflict with Magento 1 conventions
- **PHPMD**: Customized to ignore Magento 1 patterns like static access and naming conventions
- **PHP-CS-Fixer**: Configured for basic formatting without breaking Magento 1 compatibility

## Common Code Quality Issues

When working with Magento 1 code, be aware of these common issues that our tools are configured to handle:

### 1. Undefined Variables

Magento 1 code often has issues with undefined variables. Always initialize variables before use:

```php
// Bad
$parentIds = array_merge($groupParentIds, $configParentIds);

// Good
if (!is_array($groupParentIds)) {
    $groupParentIds = array();
}
if (!is_array($configParentIds)) {
    $configParentIds = array();
}
$parentIds = array_merge($groupParentIds, $configParentIds);
```

### 2. Array Initialization

Always check if a variable is an array before using array functions:

```php
// Bad
foreach ($collection as $item) { /* ... */ }

// Good
if (is_array($collection) || $collection instanceof Traversable) {
    foreach ($collection as $item) { /* ... */ }
}
```

### 3. PHPDoc Comments

Add PHPDoc blocks to all classes and methods for better IDE support and documentation:

```php
/**
 * Handle product deletion events
 *
 * @param Varien_Event_Observer $observer Event observer
 * @return $this
 */
public function productDeleted(Varien_Event_Observer $observer)
```

### 4. Return Values

Ensure all methods have consistent return values, especially in observer methods:

```php
// Always return $this at the end of observer methods
public function someObserverMethod(Varien_Event_Observer $observer)
{
    // Method logic here
    return $this;
}
```

## Git Hooks (Optional)

To automatically run code style checks before each commit, create a pre-commit hook:

1. Create `.git/hooks/pre-commit` with the following content:
   ```bash
   #!/bin/sh
   echo "Running code style checks..."
   composer run check
   ```

2. Make it executable:
   ```bash
   chmod +x .git/hooks/pre-commit
   ```
