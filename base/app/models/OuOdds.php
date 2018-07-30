<?php

class OuOdds extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'OUodds');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

	public function getBySeason($season) {
	    $this->load(array('season=?',$season));
	    return $this->query;
	}

	public function getSeasonList() {
		return $this->db->exec(
			'SELECT DISTINCT season FROM OUodds ORDER BY season DESC;'
		);
	}

	public function getLatestSeason() {
		return $this->db->exec(
			'SELECT MAX(season) as season FROM OUodds;'
		);
	}

	public function getFutureOus() {
		$this->load(array('date>?',date("Y-m-d H:i:s")));
		return $this->query;
	}

	public function getPastOusWithoutResults() {
		$this->load(array('date<? AND result IS NULL',date("Y-m-d H:i:s")));
		return $this->query;
	}

	public function getRecentOusWithResults() {
		$this->load(array('date<? AND result IS NOT NULL',date("Y-m-d H:i:s")));
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

	public function editOu($id,$ou,$date) {
	    $this->load(array('id=?',$id));
	    $this->ou=$ou;
	    $this->date=$date;
	    $this->update();
	}

	public function addResults($id,$result) {
	    $this->load(array('id=?',$id));
	    $this->result=$result;
	    $this->update();
	}
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
