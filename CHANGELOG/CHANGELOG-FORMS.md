# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4

### Changed

- Updated `craft.sproutForms` variable to `sprout.forms`
- Updated Project Config settings from `sprout-forms` => `sprout-module-forms`
- Updated translation category from `sprout-forms` => `sprout-module-forms`
- Updated `craft.sproutForms` variable to `sprout.forms`
- Updated `giggsey/libphonenumber-for-php` requirement v8.12.11
- Removed Form Rules in favor of Craft Element/Field Rules API
- Removed Craft Fields Email, URL, and others, Template Select …

### Removed

- Removed Forms `showReportsTab` and `showNotificationsTab` settings
- Migrated `barrelstrength/sprout-base-fields` => `barrelstrength/sprout`
- Killed Predefined Field and Predefined Date field
- Remove Sprout Fields Email Field? Make FR to add link to Email in UI.
- Remove Sprout Fields URL Field? Make FR to add link to Email in UI.
- ** Removed Sprout Email Field. Data has been migrated to native Craft Email
  field.
- ** Removed Sprout Url Field. Data has been migrated to native Craft URL field.
- ** Removed Sprout Notes Field. See upgrade notes to manually update Notes to
  new Craft Field UI Elements.

### Breaking Changes

- Websites using Custom Form Fields or Field Template Overrides should read the
  upgrade notes regarding updates to the FormField::getFrontEndInputHtml()
  method signature and front-end field templates to better support error classes
  in rendering options.
- Recaptcha ⇒ Sprout Forms native? hasn’t been migrated yet…
- Form Conditionals and Integrations have not yet been migrated
- Notes Field ⇒ Default Craft
- Removed Predefined Field and Predefined Date Field

