<?php

class Odds extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'odds');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

	public function getByEmail($email) {
	    $this->load(array('email=?',$email));
	}

	public function getFutureOdds() {
		$this->load(array('date>?',date("Y-m-d h:i A")));
		//$this->load(array('line>?',4));
		return $this->query;
	}

	public function add() {
	    $this->copyFrom('POST');
	    $this->save();
	}
	
	public function edit($id) {
	    $this->load(array('id=?',$id));
	    $this->copyFrom('POST');
	    $this->update();
	}

	public function editSpreadAndML($id,$spread,$moneyline) {
	    $this->load(array('id=?',$id));
	    $this->spread=$spread;
	    $this->moneyline=$moneyline;
	    $this->update();
	}
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
