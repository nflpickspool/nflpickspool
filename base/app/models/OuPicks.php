<?php

class OuPicks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'OUpicks');
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

	public function getByOddsIdAndEmail($id,$email) {
	    $this->load(array('odds_id=? AND email=?',$id,$email));
	    return $this->query;
	}

	public function getClosedPicks($email,$season) {
		return $this->db->exec(
			'SELECT teams.region_name, teams.team_name, OUodds.ou, OUpicks.pick, OUpicks.lock '.
			'FROM OUpicks '.
			'INNER JOIN OUodds ON OUpicks.odds_id = OUodds.id '.
			'INNER JOIN teams ON OUodds.team_id = teams.id '.
			'WHERE OUodds.date<\''.date("Y-m-d H:i:s").'\' AND OUodds.season='.$season.' AND OUpicks.email=\''.$email.'\';'		
		);
	}

	public function getScoreboard($email,$season) {
		return $this->db->exec(
			'SELECT teams.region_name, teams.team_name, OUodds.ou, OUpicks.pick, OUpicks.lock '.
			'FROM OUpicks '.
			'INNER JOIN OUodds ON OUpicks.odds_id = OUodds.id '.
			'INNER JOIN teams ON OUodds.team_id = teams.id '.
			'WHERE OUodds.date<\''.date("Y-m-d H:i:s").'\' AND OUodds.season='.$season.' AND OUpicks.email=\''.$email.'\';'		
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

	public function editOuPicks($id,$pick,$lock) {
	    $this->load(array('id=?',$id));
	    $this->pick=$pick;
	    $this->lock=$lock;
	    $this->update();
	}

	public function editPoints($id,$pts) {
	    $this->load(array('id=?',$id));
	    $this->pts=$pts;
	    $this->update();
	}
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
