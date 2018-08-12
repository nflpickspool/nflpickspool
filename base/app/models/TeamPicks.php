<?php

class TeamPicks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'team_picks');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

	public function getNullPicksByHandleAndLeagueYear($handle,$league_year) {
            return $this->db->exec(
                "SELECT CONCAT(t.region, ' ',t.team) AS Team, ".
                "tw.wins_line AS OU, ".
                "tp.ou_pick ".
                "FROM teams t ".
                "CROSS JOIN users u ".
                "LEFT JOIN team_wins AS tw ".
                "ON t.id = tw.team ".
                "LEFT JOIN team_picks AS tp ".
                "ON tw.id = tp.ou_id ".
                "WHERE tw.league_year = " . $league_year . " AND".
                " tp.ou_pick IS NULL AND ".
                "u.handle = '" . $handle . "';"
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
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}