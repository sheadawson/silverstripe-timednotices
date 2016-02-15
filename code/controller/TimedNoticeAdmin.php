<?php
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
    private static $managed_models = array(
        'TimedNotice'
    );

    private static $url_segment = 'timed-notices';

    private static $menu_title = 'Timed Notices';

    private static $menu_icon = 'timednotices/images/bell.png';

    public $showImportForm = false;

    /**
     * Update the SearchForm to use dropdown fields for MessageType/Status filters
     *
     * @return Form
     **/
    public function SearchForm()
    {
        $form   = parent::SearchForm();
        $fields = $form->Fields();
        $q      = $this->getRequest()->requestVar('q');

        $fields->removeByName('q[MessageType]');

        $fields->push(
            DropdownField::create(
                'q[MessageType]',
                'Message Type',
                ArrayLib::valuekey(Config::inst()->get('TimedNotice', 'message_types')),
                isset($q['MessageType']) ? $q['MessageType'] : null
            )->setEmptyString(' ')
        );

        $fields->push(
            DropdownField::create(
                'q[Status]',
                'Status',
                ArrayLib::valuekey(Config::inst()->get('TimedNotice', 'status_options')),
                isset($q['Status']) ? $q['Status'] : null
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
                } elseif ($status == 'Expired') {
                    return $list->where("EndTime < $now");
                } elseif ($status == 'Current') {
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
