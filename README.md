# Silverstripe Timed Notices

Allows CMS users to create and display notices for a specified time period. Notices can be displayed depending on the context of the user. Either to website visitors or other CMS users. Notices can be made visible to anyone, any logged in users or only people from specified user groups.

## Example

![Screenshot](https://raw.github.com/sheadawson/silverstripe-timednotices/master/images/screenshot.png)

## Requirements

 * SilverStripe ~3.1 (see 3.0 branch for ~3.0 compatible version)
 * silverstripe/timepickerfield
 * unclecheese/display-logic

## Installation and set up

```
composer require sheadawson/silverstripe-timednotices
php ./framework/cli-script.php dev/build flush=all
```

### Setup for usage on the website

If you are intending to use the notices on your website you have to take care of the following steps first:

1.) Add ```$Notices``` to your templates/Page.ss file in a suitable position.

2.) Extend your *Page* with the *TimedNoticeExtension* **or** simply add

```
    /**
     * Gets any notices relevant to the present time, context and current users
     *
     * @return HTMLText
     **/
    public function notices()
    {
        // render a list of notications for this
        return $this
            ->customise(array('Notices' => TimedNotice::get_notices('Website')))
            ->renderWith('NoticesList');
    }

```

to your Page.php

3.) Add suitable styles to your SASS/LESS/CSS. The following example shows the used markup:

```
<div class="message good">A successful message</div>

<div class="message warning">A warning</div>

<div class="message bad">A bad message</div>
```
