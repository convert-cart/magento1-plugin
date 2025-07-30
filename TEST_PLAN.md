# Convertcart Unified Module Test Plan

This test plan provides a checklist for validating the unified Convertcart module on Magento 1. Complete all applicable checks before considering the module ready for production.

## 1. Admin Panel
- [ ] The module appears under `System > Configuration > Convertcart`.
- [ ] All configuration fields (API key, toggles, etc.) are visible and can be saved.
- [ ] The module is enabled under `System > Configuration > Advanced > Advanced`.

## 2. Frontend Functionality
- [ ] Analytics script is injected on all relevant pages (check page source).
- [ ] No JS errors in browser console related to Convertcart.

## 3. Data Synchronization
- [ ] Product, order, customer, and category data are sent to Convertcart servers as expected.
- [ ] Wishlist, review, and other sync endpoints function correctly.
- [ ] Product/category deletions are tracked and synced.

## 4. Observers & Events
- [ ] Cart, checkout, search, and other tracked events trigger the correct observers.
- [ ] Observer logic executes without errors (check logs).

## 5. Database
- [ ] The `convertcart_sync_cc_activity` table exists and is updated as expected.

## 6. Logging & Error Handling
- [ ] No Convertcart-related errors in `var/log/system.log` or `var/log/exception.log`.
- [ ] All errors are reported gracefully in the Magento admin UI.

## 7. Migration & Legacy
- [ ] No references to `Convertcart_Analytics` or `Convertcart_Sync` remain.
- [ ] Old modules are fully removed and do not interfere.

## 8. Uninstall
- [ ] Disabling the module removes all admin UI and frontend effects.
- [ ] Removing code and clearing cache does not break Magento.

## 9. General
- [ ] All features work as documented in the README.
- [ ] No critical or blocking issues remain before go-live.
