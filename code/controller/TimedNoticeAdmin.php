<?php

class TimedNoticeAdmin extends ModelAdmin {

	private static $managed_models = array(
		'TimedNotice'
	);
	
	private static $url_segment = 'time-notices';
	
	private static $menu_title = 'Timed Notices';
	
	public function init(){
		parent::init();
	}
	
	public function getEditForm($id = null, $fields = null){
		$form = parent::getEditForm();
		
		return $form;
	}

}