<?php
/**
 * TimedNoticeController is used to supply the notices to the administration section
 * as well as providing the snooze functionality for the website notices.
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
     * @param string $context (default: CMS)
     * @return JSON
     **/
    public function notices($context = null)
    {
        $notices = array();

        // fallback to the CMS as the context - this is required to be consistent with the original behaviour.
        if ($context == null || $context instanceof SS_HTTPRequest) {
            $context = 'CMS';
        }

        // We want to deliver notices only if a user is logged in.
        // This way we ensure, that a potential attacker can't read notices for CMS users.
        if (Member::currentUser()) {
            $notices = TimedNotice::get_notices($context)->toNestedArray();
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
