# Changelog

## 5.0.0 - UNRELEASED

### Added

- Added support for Craft 4
- Added Form Types
- Custom tabs and fields can be added to the Form Element Field Layout
- Form Fields can be disabled for a given Form Type

### Changed

- Updated Form Element to use unified element editor
- Renamed Form Entry Element to  Submission Element
- Updated Submission Element to use unified element editor
- Updated `craft.sproutForms` variable to `sprout.forms`
- Updated Project Config settings from `sprout-forms` => `sprout-module-forms`
- Updated translation category from `sprout-forms` => `sprout-module-forms`
- Updated `craft.sproutForms` variable to `sprout.forms`
- Updated `giggsey/libphonenumber-for-php` requirement v8.12.11
- Removed Form Rules in favor of Craft Element/Field Rules API
- Removed Craft Fields Email, URL, and others, Template Select …
- Updated form content tables to be named using ID instead of handle
- Updates submission layout to be stored as JSON settings column instead of separate tables
- Renamed `EVENT_REGISTER_FIELDS` to `EVENT_REGISTER_FORM_FIELDS`
- Updated Widget `RecentEntries` => `RecentSubmissions`
- Updated Event `SaveEntryEvent` => `SaveSubmissionEvent`
- Updated Event `OnSaveEntryEvent` => `OnSaveSubmissionEvent`
- Updated Event `OnBeforeValidateEntryEvent` => `OnBeforeValidateSubmissionEvent`
- Updated Event `OnBeforeSaveEntryEvent` => `OnBeforeSaveSubmissionEvent`
- Updated Event `OnBeforePopulateEntryEvent` => `OnBeforePopulateSubmissionEvent`
- Updated Field `Entries` => `SubmissionsRelationField`
- Updated Field `Forms` => `FormsRelationField`
- Updated Data Source `EntriesDataSource` => `SubmissionsDataSource`
- Updated Job `RenameFormEntries` => `ResaveFormSubmissions`
- Updated Record `EntryStatusRecord` => `SubmissionStatusRecord`
- Updated Record `EntryRecord` => `SubmissionRecord`
- Updated Record `EntriesSpamLogRecord` => `SubmissionsSpamLogRecord`

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
- Removed FormTemplates in favor of Form Types
- Removed template hook `cp.sproutForms.submissions.edit.details`
- Removed template hook `cp.sproutForms.submissions.edit.content`
- Removed Form Element Source Groups. Use Custom Field Layout and Customize Sources.

### Breaking Changes

- Websites using Custom Form Fields or Field Template Overrides should read the
  upgrade notes regarding updates to the FormField::getFrontEndInputHtml()
  method signature and front-end field templates to better support error classes
  in rendering options.
- From Templates have been updated to treat individual fields and inputs more like components
  - Many variables are passed in as processed values at higher level templates
  - New getFrontEndInputVariables
- Recaptcha ⇒ Sprout Forms native? hasn’t been migrated yet…
- Form Conditionals and Integrations have not yet been migrated
- Notes Field ⇒ Default Craft
- Removed Predefined Field and Predefined Date Field
- Removed `{% hook 'sproutForms.modifyForm' %}` in favor of `{{ form.getCaptchaHtml() }}`
- Updated variables and settings with `formEntry` in the name to `submission`
