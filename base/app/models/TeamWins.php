<?php

class TeamWins extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'team_wins');
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

    public function getByLeagueYear($season) {
        return $this->db->exec(
            "SELECT ".
            "CONCAT( ".
            "t.region, ' ',".
            "t.team) AS Team, ".
            "wins_line AS 'OverUnder' ".

            "FROM team_wins ".
            "LEFT JOIN teams AS t ".
            "  ON team_wins.team = t.id ".

            "WHERE league_year = ". $season .";"
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
