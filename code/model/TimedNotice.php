<?php
/**
 * TimedNotice
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNotice extends DataObject implements PermissionProvider
{

    private static $singular_name    = 'Timed Notice';
    private static $plural_name      = 'Timed Notices';

    private static $db = array(
        'Context'        => "Enum('Website,CMS','CMS')",
        'Message'        => 'Text',
        'MessageType'    => 'Varchar',
        'StartTime'      => 'SS_DateTime',
        'EndTime'        => 'SS_DateTime',
        'CanViewType'    => "Enum('Anyone,LoggedInUsers,OnlyTheseUsers', 'LoggedInUsers')",
    );

    private static $many_many = array(
        'ViewerGroups'   => 'Group',
    );

    private static $defaults = array(
        'CanViewType'    => 'LoggedInUsers',
    );

    private static $summary_fields = array(
        'StartTime'      => "Start Time",
        'EndTime'        => "End Time",
        'StatusLabel'    => "Status",
        'MessageType'    => "Message Type",
        'Message'        => "Message",
    );

    private static $searchable_fields = array(
        'MessageType',
    );

    private static $message_types = array(
        'good',
        'warning',
        'bad',
    );

    private static $status_options = array(
        'Current',
        'Future',
        'Expired',
    );


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
            ->setMultiple(true)
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

        $start->getDateField()->setConfig('showcalendar', true);
        $end->getDateField()->setConfig('showcalendar', true);

        $start->setTimeField(
            TimePickerField::create('StartTime[time]', '')
                ->addExtraClass('fieldgroup-field')
        );
        $end->setTimeField(
            TimePickerField::create('EndTime[time]', '')
                ->addExtraClass('fieldgroup-field')
        );

        return $fields;
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'TIMEDNOTICE_EDIT' => array(
                'name' => 'Edit a Timed Notice',
                'category' => 'Timed Notices',
            ),
            'TIMEDNOTICE_DELETE' => array(
                'name' => 'Delete a Timed Notice',
                'category' => 'Timed Notices',
            ),
            'TIMEDNOTICE_CREATE' => array(
                'name' => 'Create a Timed Notice',
                'category' => 'Timed Notices'
            )
        );
    }


    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_EDIT');
    }

    public function canDelete($member = null)
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_DELETE');
    }

    public function canCreate($member = null)
    {
        return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_CREATE');
    }

    /**
     * Gets any notices relevant to the present time, context and current users
     *
     * @return ArrayList
     **/
    public static function get_notices()
    {
        // analyse the context this message gets requested
        $context = self::get_context();

        // prepare and filter the possible result
        $now        = SS_Datetime::now()->getValue();
        $member     = Member::currentUser();
        $notices    = TimedNotice::get()->where("
            Context = '{$context}' AND
            StartTime < '{$now}' AND
            (EndTime > '{$now}' OR EndTime IS NULL)
        ");

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
     * Analysis the context and returns the context information as string
     *
     * @return string
     */
    public static function get_context()
    {
        // default assumption is website.
        $context = 'Website';

        // if the notice is delivered via the TimedNoticeController it is the CMS
        if (get_class(Controller::curr()) == 'TimedNoticeController') {
            $context = 'CMS';
        }

        return $context;
    }

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        $now = SS_Datetime::now()->getValue();
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
        return RequiredFields::create(array(
            'Context',
            'Message',
            'StartTime'
        ));
    }

    public static function add_notice($message, $context, $end, $start = null, $type = 'good', $viewBy = null)
    {
        if (!$start) {
            $start = SS_Datetime::now()->getValue();
        } else {
            $start = date('Y-m-d H:i:s', strtotime($start));
        }

        $end = date('Y-m-d H:i:s', strtotime($end));

        $notice = TimedNotice::create(array(
            'Message'        => $message,
            'Context'        => $context,
            'StartTime'      => $start,
            'EndTime'        => $end,
            'CanViewType'    => 'LoggedInUsers',
            'MessageType'    => $type,
        ));

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
