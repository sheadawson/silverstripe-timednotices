<?php
/**
 * TimedNoticeController
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNoticeController extends Controller{

	private static $allowed_actions = array(
		'notices',
		'snooze',
		
	);


	/**
	 * Gets any notices relevant to the present time and current users
	 * @return JSON
	 **/
	public function notices($request){
		$now 		= date('Y-m-d H:i:s');
		$member 	= Member::currentUser();
		$notices 	= TimedNotice::get()->where("
			StartTime < '$now' AND 
			(EndTime > '$now' OR EndTime IS NULL) 
		");


		if($notices->count()){
			$notices = ArrayList::create($notices->toArray());
			foreach ($notices as $notice) {
				if($notice->CanViewType == 'OnlyTheseUsers'){
					if($member && !$member->inGroups($notice->ViewerGroups())){
						$notices->remove($notice);
					}
				}
			}
		}

		return Convert::array2json($notices->toNestedArray());
	}
	
	public function snooze() {
		if (!Permission::check('TIMEDNOTICE_EDIT')) {
			return;
		}
		
		$id = (int) $this->request->postVar('ID');
		$increase = (int) $this->request->postVar('plus');

		if ($id) {
			$notice = TimedNotice::get()->byID($id);
			if ($notice && $notice->ID && $increase) {
				$notice->StartTime = time() + ($increase * 60);
				$notice->write();
				return $increase;
			}
		}
		return 0;
	}
}