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
        'snooze',
    );

    /**
     * Gets any notices relevant to the present time and current users
     *
     * @return JSON
     **/
    public function notices()
    {
        return Convert::array2json(TimedNotice::getNotices()->toNestedArray());
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
