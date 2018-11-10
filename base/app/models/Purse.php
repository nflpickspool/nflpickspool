<?php

class Purse extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'purse');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getByLeagueId($league_id) {
	    $this->load(array('league_id=?',$league_id));
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
