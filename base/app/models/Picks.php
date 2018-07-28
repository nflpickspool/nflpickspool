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

	public function getByOddsId($odds_id) {
	    $this->load(array('odds_id=?',$odds_id));
	    return $this->query;
	}

	public function getByEmail($email) {
	    $this->load(array('email=?',$email));
	}

	public function getByOddsIdAndEmail($id,$email) {
	    $this->load(array('odds_id=? AND email=?',$id,$email));
	    return $this->query;
	}

	public function getBySeasonAndWeek($season,$week) {
	    $this->load(array('season=? AND week=?',$season,$week));
	    return $this->query;
	}

	public function getIncompletePicks($email,$season,$week) {
		return $this->db->exec(
			'SELECT odds.season, odds.week, odds.date, odds.away, odds.home, odds.spread, odds.moneyline '.
			'FROM picks '.
			'INNER JOIN odds ON picks.odds_id = odds.id '.
			'WHERE odds.date>\''.date("Y-m-d H:i:s").'\' AND odds.season='.$season.' AND odds.week='.$week.' AND picks.email=\''.$email.'\';'		
		);
	}

	public function getSubmittedPicks($email,$season,$week) {
		return $this->db->exec(
			'SELECT odds.season, odds.week, odds.date, odds.away, odds.home, odds.spread, odds.moneyline, picks.spread_pick, picks.ml_pick, picks.lock '.
			'FROM picks '.
			'INNER JOIN odds ON picks.odds_id = odds.id '.
			'WHERE odds.date>\''.date("Y-m-d H:i:s").'\' AND odds.season='.$season.' AND odds.week='.$week.' AND picks.email=\''.$email.'\';'		
		);
	}

	public function getClosedPicks($email,$season,$week) {
		return $this->db->exec(
			'SELECT odds.season, odds.week, odds.date, odds.away, odds.home, odds.spread, odds.moneyline, picks.spread_pick, picks.ml_pick, picks.lock '.
			'FROM picks '.
			'INNER JOIN odds ON picks.odds_id = odds.id '.
			'WHERE odds.date<\''.date("Y-m-d H:i:s").'\' AND odds.season='.$season.' AND odds.week='.$week.' AND picks.email=\''.$email.'\';'		
		);
	}

	public function getStandings() {
		return $this->db->exec(
			'SELECT email,SUM(pts_spread) as pts_spread,'.
			'SUM(pts_ml)as pts_ml,SUM(pts_spread + pts_ml)'.
			' as pts_total FROM picks GROUP BY email ORDER BY pts_total;'
		);
	}

	public function getScoreboard($email,$season,$week) {
		return $this->db->exec(
			'SELECT odds.season, odds.week, odds.date, odds.away, odds.home, odds.spread, odds.moneyline, picks.spread_pick, picks.ml_pick, picks.lock '.
			'FROM picks '.
			'INNER JOIN odds ON picks.odds_id = odds.id '.
			'WHERE odds.date<\''.date("Y-m-d H:i:s").'\' AND odds.season='.$season.' AND odds.week='.$week.' AND picks.email=\''.$email.'\';'		
		);
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

	public function editSpreadAndMLPoints($id,$pts_spread,$pts_ml) {
	    $this->load(array('id=?',$id));
	    $this->pts_spread=$pts_spread;
	    $this->pts_ml=$pts_ml;
	    $this->update();
	}

	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
