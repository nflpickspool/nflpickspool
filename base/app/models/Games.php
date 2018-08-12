<?php

class Games extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'games');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

    public function getByLeagueYearAndWeek($season,$week) {
        return $this->db->exec(
            "SELECT ".
            "kickoff_time AS Kickoff, ".
            "CONCAT( ".
            "ta.team, ' ', '@', ' ', ".
            "th.team) AS Matchup, ".
            "s.stadium_name AS Stadium, ".
            "network AS Network, ".
            "ft.team AS Favorite, ".
            "point_spread AS 'PointSpread', ".
            "money_line AS 'MoneyLine', ".
            "ou AS 'OverUnder' ".

            "FROM games ".
            "LEFT JOIN teams AS ta ".
            "  ON away = ta.id ".
            "LEFT JOIN teams AS th ".
            "  ON home = th.id ".
            "LEFT JOIN teams AS ft ".
            "  ON favorite = ft.id ".
            "LEFT JOIN stadiums AS s ".
            "  ON stadium = s.id ".

            "WHERE league_year = ". $season .
            " AND league_week = ". $week .";"
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
