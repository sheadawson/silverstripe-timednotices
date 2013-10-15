<?php
class TimedNotice extends DataObject {

	private static $singular_name 	= 'Timed Notice';
	private static $plural_name	 	= 'Timed Notices';
	
	private static $db = array(
		'Message' 		=> 'Text',
		'MessageType' 	=> 'Varchar',
		//'Context' 		=> 'Varchar',
		'StartTime' 	=> 'SS_DateTime',
		'EndTime' 		=> 'SS_DateTime',
		'CanViewType' 	=> "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')"
	);

	private static $many_many = array(
		'ViewerGroups' 	=> 'Group'
	);

	private static $summary_fields = array(
		'StartTime' 	=> "Start Time",
		'EndTimeLabel'	=> "End Time",
		'Now'			=> "Now",
		'StatusLabel'	=> "Status",
		'MessageType' 	=> "Message Type",
		'Message' 		=> "Message",
	);

	public function getNow(){
		return date('Y-m-d H:m:s');
	}

	private static $message_types = array(
		'good' 		=> 'Good',
		'warning' 	=> 'Warning',
		'bad' 		=> 'Bad'
	);

	public function getCMSFields(){
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', DropdownField::create(
			'MessageType', 
			'Message Type', 
			$this->config()->get('message_types')
		));

		$start 	= $fields->dataFieldByName('StartTime');
		$end 	= $fields->dataFieldByName('EndTime');
		
		$start->getDateField()->setConfig('showcalendar',true);
		$end->getDateField()->setConfig('showcalendar',true);

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


	public function getStatusLabel(){
		$now = date('Y-m-d H:m:s');
		if($this->StartTime > $now){
			return 'Future';
		}elseif($this->EndTime && $this->EndTime <= $now){
			return 'Expired';
		}else{
			return 'Current';
		}
	}


	public function getEndTimeLabel(){
		return $this->EndTime ? $this->EndTime : '-';
	}


	public function getCMSValidator(){
		return RequiredFields::create(array(
			'Message',
			'StartTime'
		));
	}
}
