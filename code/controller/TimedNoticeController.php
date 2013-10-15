<?php

class TimedNoticeController extends Controller{

	private static $allowed_actions = array(
		'notices'
	);

	public function notices($request){
		$now 		= date('Y-m-d H:m:s');
		$notices 	= TimedNotice::get()->where("
			StartTime < '$now' AND 
			(EndTime > '$now' OR EndTime IS NULL) 
		");

		return Convert::array2json($notices->toNestedArray());
	}
}