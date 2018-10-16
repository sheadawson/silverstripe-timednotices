<?php

namespace MBIE\TimedNotice;

use MBIE\TimedNotice\TimedNotice;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * TimedNoticeController
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNoticeController extends Controller
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'notices',
        'snooze',
    ];

    /**
     * Gets any notices relevant to the present time and current users
     *
     * @return JSON
     **/
    public function notices($request)
    {
        $now = DBDatetime::now()->getValue();
        $member = Member::currentUser();
        $notices    = TimedNotice::get()->where("
            StartTime < '$now' AND
            (EndTime > '$now' OR EndTime IS NULL)
        ");

        if ($notices->count()) {
            $notices = ArrayList::create($notices->toArray());

            foreach ($notices as $notice) {
                if ($notice->CanViewType == 'OnlyTheseUsers') {
                    if ($member && !$member->inGroups($notice->ViewerGroups())) {
                        $notices->remove($notice);
                    }
                }
            }
        }

        return Convert::array2json($notices->toNestedArray());
    }

    public function snooze()
    {
        if (!Permission::check('TIMEDNOTICE_EDIT')) {
            return;
        }

        $id = (int) $this->request->postVar('ID');
        $increase = (int) $this->request->postVar('plus');

        if ($id) {
            $notice = TimedNotice::get()->byID($id);
            if ($notice && $notice->ID) {
                if ($increase > 0) {
                    $notice->StartTime = time() + ($increase * 60);
                    $notice->EndTime = strtotime($notice->EndTime) + ($increase * 60);
                } else {
                    $notice->EndTime = time() + $increase;
                }

                $notice->write();
                return $increase;
            }
        }

        return 0;
    }
}
