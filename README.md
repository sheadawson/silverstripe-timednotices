#Silverstripe Timed Notices

Allows CMS users to create and display notices to other CMS users for the duration of a specified time period. Notices can be made visible to any logged in users or only people from specified user groups.

##Example

![Screenshot](https://raw.github.com/sheadawson/silverstripe-timednotices/master/images/screenshot.png)

##Requirements

SilverStripe ~3.1 (see 3.0 branch for ~3.0 compatible version)

##Install

```
composer require sheadawson/silverstripe-timednotices
php ./framework/cli-script.php dev/build flush=all
```

##TODO

* Add a "Context" option to Timed Notice, allowing notices to be displayed in the cms or frontend, rather than just cms

