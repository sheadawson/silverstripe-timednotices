<?php

namespace MBIE\TimedNotice;

use MBIE\TimedNotice\TimedNotice;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayLib;

/**
 * TimedNoticeAdmin
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNoticeAdmin extends ModelAdmin
{
    /**
     * @var array
     */
    private static $managed_models = [
        TimedNotice::class
    ];

    /**
     * @var string
     */
    private static $url_segment = 'timed-notices';

    /**
     * @var string
     */
    private static $menu_title = 'Timed Notices';

    /**
     * @var string
     *
     * Regression: $menu_icon not currently working in SS4.2. See this link for more information:
     * https://github.com/silverstripe/silverstripe-admin/issues/558
     */
    // private static $menu_icon = 'silverstripe-timednotices/images/bell.png';

    /**
     * @var boolean
     */
    public $showImportForm = false;

    /**
     * Update the SearchForm to use dropdown fields for MessageType/Status filters
     *
     * @return Form
     **/
    public function SearchForm()
    {
        $form = parent::SearchForm();
        $fields = $form->Fields();
        $q = $this->getRequest()->requestVar('q');

        $fields->removeByName('q[MessageType]');

        $fields->push(
            DropdownField::create(
                'q[MessageType]',
                'Message Type',
                ArrayLib::valuekey(Config::inst()->get(TimedNotice::class, 'message_types')),
                isset($q['MessageType']) ? $q['MessageType'] : []
            )->setEmptyString(' ')
        );

        $fields->push(
            DropdownField::create(
                'q[Status]',
                'Status',
                ArrayLib::valuekey(Config::inst()->get(TimedNotice::class, 'status_options')),
                isset($q['Status']) ? $q['Status'] : []
            )->setEmptyString(' ')
        );

        return $form;
    }

    /**
     * Custom filtering on "Status"
     *
     * @return Form
     **/
    public function getList()
    {
        $list = parent::getList();
        $r = $this->getRequest();
        if ($q = $r->requestVar('q')) {
            if (isset($q['Status'])) {
                $status = $q['Status'];
                $now = date('Y-m-d H:i:s');
                if ($status == 'Future') {
                    return $list->where("StartTime > '$now'");
                } else if ($status == 'Expired') {
                    return $list->where("EndTime < $now");
                } else if ($status == 'Current') {
                    return $list->where("
                        StartTime < '$now' AND
                        (EndTime > '$now' OR EndTime IS NULL)
                    ");
                }
            }
        }

        return $list;
    }
}
