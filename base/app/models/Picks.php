<?php

class Picks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'picks');
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

	public function getByOddsIdAndEmail($id,$email) {
	    $this->load(array('odds_id=? AND email=?',$id,$email));
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
	
	public function editSpreadAndMLPicks($id,$spread_pick,$ml_pick,$lock) {
	    $this->load(array('id=?',$id));
	    $this->spread_pick=$spread_pick;
	    $this->ml_pick=$ml_pick;
	    $this->lock=$lock;
	    $this->update();
	}

	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
