<?php

namespace SheaDawson\TimedNotice;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TimeField;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use UncleCheese\DisplayLogic\Extensions\DisplayLogic;

/**
 * TimedNotice
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNotice extends DataObject implements PermissionProvider
{

    /**
     * @var string
     */
    private static $singular_name = 'Timed Notice';

    /**
     * @var string
     */
    private static $plural_name = 'Timed Notices';

    /**
     * @var array
     */
    private static $db = [
        'Context'        => "Enum('Website,CMS','CMS')",
        'Message'        => 'Text',
        'MessageType'    => 'Varchar',
        'StartTime'      => 'Datetime',
        'EndTime'        => 'Datetime',
        'CanViewType'    => 'Enum("Anyone, LoggedInUsers, OnlyTheseUsers, LoggedInUsers")',
    ];

    /**
     * @var string
     */
    private static $table_name = "TimedNotice";

    /**
     * @var array
     */
    private static $many_many = [
        'ViewerGroups'   => Group::class,
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'CanViewType'    => 'LoggedInUsers',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'StartTime'      => "Start Time",
        'EndTime'        => "End Time",
        'StatusLabel'    => "Status",
        'MessageType'    => "Message Type",
        'Message'        => "Message",
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'MessageType',
    ];

    /**
     * @var array
     */
    private static $message_types = [
        'good',
        'warning',
        'bad',
    ];

    /**
     * @var array
     */
    private static $status_options = [
        'Current',
        'Future',
        'Expired',
    ];

    /**
     * @return FieldList $fields
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeFieldFromTab('Root', 'ViewerGroups');

        // prepare options for the target groups
        $viewersOptionsSource['Anyone'] = _t(
            'TimedNotice.ANYONE',
            'Anyone'
        );

        $viewersOptionsSource['LoggedInUsers'] = _t(
            'TimedNotice.ACCESSLOGGEDIN',
            'Logged-in users'
        );

        $viewersOptionsSource['OnlyTheseUsers'] = _t(
            'TimedNotice.ACCESSONLYTHESE',
            'Only these people (choose from list)'
        );

        $fields->addFieldToTab(
            'Root.Main',
            $canViewTypeField = OptionsetField::create(
                "CanViewType",
                _t('TimedNotice.ACCESSHEADER', "Who should see this notice?"),
                $viewersOptionsSource
            )
        );

        $groupsMap = Group::get()->map('ID', 'Breadcrumbs')->toArray();
        asort($groupsMap);

        $fields->addFieldToTab(
            'Root.Main',
            $viewerGroupsField = ListboxField::create(
                "ViewerGroups",
                _t('TimedNotice.VIEWERGROUPS', "Only people in these groups")
            )
            // ->setMultiple(true)
            ->setSource($groupsMap)
            ->setAttribute(
                'data-placeholder',
                _t('TimedNotice.GroupPlaceholder', 'Click to select group')
            )
        );

        $viewerGroupsField->displayIf("CanViewType")->isEqualTo("OnlyTheseUsers");

        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'MessageType',
                'Message Type',
                ArrayLib::valuekey($this->config()->get('message_types'))
            ),
            'Message'
        );

        $fields->addFieldToTab(
            'Root.Main',
            ReadonlyField::create(
                'TZNote',
                'Note',
                sprintf(_t(
                    'TimedNotice.TZNote',
                    'Your dates and times should be based on the server timezone: %s'),
                    date_default_timezone_get()
                )
            ),
            'StartTime'
        );

        $start = $fields->dataFieldByName('StartTime');
        $end   = $fields->dataFieldByName('EndTime');

        /**
         * ToDo: Add a calendar
         */
        // $start->getDateField()->setConfig('showcalendar', true);
        // $end->getDateField()->setConfig('showcalendar', true);

        // $start->setTimeField(
        //     TimeField::create('StartTime[time]', '')
        //         ->addExtraClass('fieldgroup-field')
        // );

        // $end->setTimeField(
        //     TimeField::create('EndTime[time]', '')
        //         ->addExtraClass('fieldgroup-field')
        // );

        return $fields;
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'TIMEDNOTICE_EDIT' => [
                'name' => 'Edit a Timed Notice',
                'category' => 'Timed Notices',
            ],
            'TIMEDNOTICE_DELETE' => [
                'name' => 'Delete a Timed Notice',
                'category' => 'Timed Notices',
            ],
            'TIMEDNOTICE_CREATE' => [
                'name' => 'Create a Timed Notice',
                'category' => 'Timed Notices'
           ]
        ];
    }

    /**
     * Gets any notices relevant to the present time, context and current users
     *
     * @param string $context (default: CMS)
     * @return ArrayList
     **/
    public static function get_notices($context = null)
    {
        // fallback to the CMS as the context - this is required to be consistent with the original behaviour.
        if ($context == null) {
            $context = 'CMS';
        }

         // prepare and filter the possible result
        $now     = DBDatetime::now()->getValue();
        $member  = Member::currentUser();

        $notices = TimedNotice::get()->filter("Context", $context);

        // Context = '{$context}' AND
        // StartTime < '{$now}' AND
        // (EndTime > '{$now}' OR EndTime IS NULL)

         // if there are notices verify if those are allowed for this group
        if ($notices->count()) {
            // turn the DataList into an ArrayList to make it editable.
            $notices = ArrayList::create($notices->toArray());
             foreach ($notices as $notice) {
                if ($notice->CanViewType == 'OnlyTheseUsers') {
                    if ($member && !$member->inGroups($notice->ViewerGroups())) {
                        $notices->remove($notice);
                    }
                }
            }
        }

         return $notices;
    }

    /**
     * @return boolean
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_EDIT');
    }

    /**
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_DELETE');
    }

    /**
     * @return boolean
     */
    public function canCreate($member = null, $content = [])
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_CREATE');
    }

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        $now = DBDatetime::now()->getValue();
        if ($this->StartTime > $now) {
            return 'Future';
        } elseif ($this->EndTime && $this->EndTime <= $now) {
            return 'Expired';
        } else {
            return 'Current';
        }
    }

    /**
     * @return RequiredFields
     */
    public function getCMSValidator()
    {
        return RequiredFields::create(
            [
                'Context',
                'Message',
                'StartTime'
            ]
        );
    }

    /**
     * @return TimedNotice $notice
     */
    public static function add_notice($message, $context, $end, $start = null, $type = 'good', $viewBy = null)
    {
        if (!$start) {
            $start = DBDatetime::now()->getValue();
        } else {
            $start = date('Y-m-d H:i:s', strtotime($start));
        }

        $end = date('Y-m-d H:i:s', strtotime($end));

        $notice = TimedNotice::create(
            [
                'Message'        => $message,
                'Context'        => $context,
                'StartTime'      => $start,
                'EndTime'        => $end,
                'CanViewType'    => 'LoggedInUsers',
                'MessageType'    => $type,
            ]
        );

        if ($viewBy == 'Anyone') {
            $notice->CanViewType = 'Anyone';
        }

        if ($viewBy instanceof Group) {
            $notice->CanViewType = 'OnlyTheseUsers';
        }

        $notice->write();

        if ($viewBy instanceof Group) {
            $notice->ViewerGroups()->add($viewBy);
        }

        return $notice;
    }
}
