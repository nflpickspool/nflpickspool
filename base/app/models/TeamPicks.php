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

    public function getLeagueYearList() {
		return $this->db->exec(
			'SELECT DISTINCT league_year FROM team_wins ORDER BY league_year DESC;'
		);
	}

	public function getNullPicksByIdAndLeagueYear($id,$league_year) {
            return $this->db->exec(
                "SELECT u.id AS player_id, ".
                "tw.id AS ou_id, ".
                "t.id AS team_id, ".
                "CONCAT(t.region, ' ',t.team) AS Team, ".
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
                "u.id = '" . $id . "';"
            );
    } 

	public function getNotNullPicksByIdAndLeagueYear($id,$league_year) {
            return $this->db->exec(
                "SELECT tp.id AS id, ".
                "u.id AS player_id, ".
                "tw.id AS ou_id, ".
                "t.id AS team_id, ".
                "CONCAT(t.region, ' ',t.team) AS Team, ".
                "tw.wins_line AS OU, ".
                "tp.ou_pick, ".
                "tp.is_lock ".
                "FROM teams t ".
                "CROSS JOIN users u ".
                "LEFT JOIN team_wins AS tw ".
                "ON t.id = tw.team ".
                "LEFT JOIN team_picks AS tp ".
                "ON tw.id = tp.ou_id ".
                "WHERE tw.league_year = " . $league_year . " AND".
                " tp.ou_pick IS NOT NULL AND ".
                "u.id = '" . $id . "';"
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
