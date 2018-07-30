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

	public function getSeasonAndWeekList() {
		return $this->db->exec(
			'SELECT DISTINCT season,week FROM odds ORDER BY season DESC,week DESC;'
		);
	}

	public function getLatestSeason() {
		return $this->db->exec(
			'SELECT MAX(season) as season FROM odds;'
		);
	}

	public function getLatestWeekForSeason($season) {
		return $this->db->exec('SELECT MAX(week) as week FROM odds WHERE season='.$season.';');
	}

	public function getFutureOdds() {
		$this->load(array('date>?',date("Y-m-d H:i:s")));
		return $this->query;
	}

	public function getFutureOddsBySeasonWeek($season,$week) {
		$this->load(array('date>? AND season=? and week=?',date("Y-m-d H:i:s"),$season,$week));
		return $this->query;
	}

	public function getPastOddsWithoutResults() {
		$this->load(array('date<? AND (awayScore IS NULL OR homeScore IS NULL	)',date("Y-m-d H:i:s")));
		//$this->load(array('date<? AND homeScore=NULL OR awayScore=NULL',date("Y-m-d h:i A")));
		return $this->query;
	}

	public function getRecentOddsWithResults() {
		$this->load(array('date<? AND awayScore IS NOT NULL AND homeScore IS NOT NULL',date("Y-m-d H:i:s")));
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
	    $this->date_submitted = date("Y-m-d H:i:s"); //???
	    $this->update();
	}

	public function addScores($id,$awayScore,$homeScore) {
	    $this->load(array('id=?',$id));
	    $this->awayScore=$awayScore;
	    $this->homeScore=$homeScore;
	    $this->update();
	}
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
