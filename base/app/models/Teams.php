<?php

class Teams extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'teams');
	}
	
	public function all() {
	    $this->load(
	    	array(),
	    	array('order'=>'region, team')
        );
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
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
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
