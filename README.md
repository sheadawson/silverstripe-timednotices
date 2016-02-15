# Silverstripe Timed Notices

Allows CMS users to create and display notices for a specified time period. Notices can be displayed depending on the context of the user. Either to website visitors or other CMS users. Notices can be made visible to anyone, any logged in users or only people from specified user groups.

## Example

![Screenshot](https://raw.github.com/sheadawson/silverstripe-timednotices/master/images/screenshot.png)

## Requirements

SilverStripe ~3.1 (see 3.0 branch for ~3.0 compatible version)

## Installation and set up

```
composer require sheadawson/silverstripe-timednotices
php ./framework/cli-script.php dev/build flush=all
```

If you are intending to use the notices on your website you have to take care of the following two steps:

1.) Add ```$Notices``` to your templates/Page.ss file.

2.) Add suitable styles to your SASS/LESS/CSS. The following example shows the used markup:

```
<div class="message good">A successful message</div>

<div class="message warning">A warning</div>

<div class="message bad">A bad message</div>
```
