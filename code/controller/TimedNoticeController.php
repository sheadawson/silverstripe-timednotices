<?php
/**
 * TimedNoticeController
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNoticeController extends Controller{

	private static $allowed_actions = array(
		'notices'
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
}