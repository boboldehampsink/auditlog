DEPRECATED - Audit Log plugin for Craft CMS [![Build Status](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/build-status/develop) [![Code Coverage](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/?branch=develop) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/boboldehampsink/auditlog/?branch=develop)
=================

Plugin that allows you to log adding/updating/deleting of categories/entries/users.

Features:
 - Log Entries, Users and Categories
 - View exact details on what fields have changed
 - View who changed what on what page
 - Export an Audit Log CSV
 - Search, filter and use date ranges to find log entries
 - Has hooks that you can use to extend this plugin
   - registerAuditLogSources
   - getAuditLogTableAttributeHtml
   - defineAvailableTableAttributes
   - modifyAuditLogSortableAttributes
 - Has events that you can listen to
   - auditLog.onElementChanged

Important:
The plugin's folder should be named "auditlog"

Deprecated
=================

With the release of Craft 3 on 4-4-2018, this plugin has been deprecated. You can still use this with Craft 2 but you are encouraged to use (and develop) a Craft 3 version. At this moment, I have no plans to do so.

Development
=================
Run this from your Craft installation to test your changes to this plugin before submitting a Pull Request
```bash
phpunit --bootstrap craft/app/tests/bootstrap.php --configuration craft/plugins/auditlog/phpunit.xml.dist --coverage-text craft/plugins/auditlog/tests
```

Changelog
=================
### 0.7.1 ###
 - Fix comparing of non-existing attribute before, closing issue #15

### 0.7.0 ###
 - Added Craft 2.5 compatibility
 - Refactored plugin for better readability, quality and testability
 - All service code is now fully covered by unit tests

### 0.6.2 ###
 - Fixed a bug where the date range didn't fully work
 - Fixed criteria attributes not fully working

### 0.6.1 ###
 - Added a registerAuditLogSources hook to provide custom sources/criteria
 - Fixed Audit Log not being able to fetch a specific log item
 - Added a MIT license

### 0.6.0 ###
 - Added the ability to control logging per element type
 - Performance fixes - works much smoother now by fully utilizing ElementType API

### 0.5.0 ###
 - Added "onElementChanged" event so you can check if a saved element really changed
   - This event will also generate a diff between the before and after state of an element
 - Fix errors that could occur when saving entries and categories anonymously
 - Clean up arrays before showing, making them more readable

### 0.4.2 ###
 - Fixed source not being selected in CSV download

### 0.4.1 ###
 - Added CSRF protection for CSV downloads (thanks to Marion Newlevant)

### 0.4.0 ###
 - Added the ability to download a csv of the log
 - Log more readable info, like Category Title & Group, Section Name and User Groups
 - Improved searching
 - Fixed date ranges not being accurate
 - Added a modifyAuditLogSortableAttributes hook

### 0.3.0 ###
 - Removed ability to clear log - you can uninstall the plugin to do this
 - Added a date range selector
 - Made sorting work
 - Added modifyAuditLogTableAttributes and getAuditLogTableAttributeHtml hooks

Warning! This version is updated for Craft 2.3 and does NOT work on Craft 2.2

### 0.2.8 ###
 - Fixed a bug where the user couldn't be shown in some cases

### 0.2.7 ###
 - Fixed origin url in some cases

### 0.2.6 ###
 - Better object parsing

### 0.2.5 ###
 - Also parse logged objects to string
 - Also log user id when registrating

### 0.2.4 ###
 - Also save section id for entry logs

### 0.2.3 ###
 - Fixed a bug where the plugin didn't work on PHP 5.3

### 0.2.2 ###
 - Added the ability to clear the log
 - Better fieldtype parsing
 - You can now easily go to origin
 - ID (and for Entries, Title) are also stored now

### 0.2.1 ###
 - Fix "changes" url in CP - Thanks to Tim Kelty
 - Avoid Twig errors on array value - Thanks to Tim Kelty

### 0.2.0 ###
 - Transformed AuditLog into an ElementType for easier sorting, filtering, searching and pagination

### 0.1.0 ###
 - Initial push to GitHub
