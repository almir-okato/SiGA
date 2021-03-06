<?php

require_once("BarNotification.php");

class RegularNotification extends BarNotification{

	public function __construct($user, $id = FALSE, $seen = FALSE, $content = FALSE){
		parent::__construct($user, $id, $seen, $content);
	}

	public function type(){
		return get_class($this);
	}

	public function notify(){
		parent::notify();
	}
}