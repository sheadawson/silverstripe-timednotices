<?php
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
    private static $allowed_actions = array(
        'notices',
        'snooze' => 'TIMEDNOTICE_EDIT',
    );

    /**
     * Gets any notices relevant to the present time and current users
     *
     * @return JSON
     **/
    public function notices()
    {
        $notices = array();

        // We want to deliver notices only if a user is logged in.
        // This way we ensure, that a potential attacker can't read notices for CMS users.
        if (Member::currentUser()) {
            $notices = TimedNotice::getNotices()->toNestedArray();
        }

        return Convert::array2json($notices);
    }

    public function snooze($request)
    {
        $id = (int) $request->postVar('ID');
        $increase = (int) $request->postVar('plus');

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
