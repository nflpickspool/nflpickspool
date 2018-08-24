<?php

class GamePicks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'game_picks');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

    public function getNullPicksByIdAndLeagueYearWeek($id,$league_year,$league_week) {
            return $this->db->exec(
            "SELECT ".
            "g.id AS game_id, ".
            "u.id AS user_id, ".
            "ta.id AS away_id, ".
            "UPPER(ta.abb) AS away, ".
            "th.id AS home_id, ".
            "UPPER(th.abb) AS home, ".
            "kickoff_time AS Kickoff, ".
            "CONCAT( ".
            "ta.team, ' ', '@', ' ', ".
            "th.team) AS Matchup, ".
            "s.stadium_name AS Stadium, ".
            "network AS Network, ".
            "ft.team AS Favorite, ".
            "point_spread AS 'PointSpread', ".
            "money_line AS 'MoneyLine', ".
            "ou AS 'OverUnder', ".
            "gp.spread_pick AS spread_pick ".

            "FROM games AS g ".
            "CROSS JOIN users AS u ".
            "LEFT JOIN teams AS ta ".
            "  ON away = ta.id ".
            "LEFT JOIN teams AS th ".
            "  ON home = th.id ".
            "LEFT JOIN teams AS ft ".
            "  ON favorite = ft.id ".
            "LEFT JOIN stadiums AS s ".
            "  ON stadium = s.id ".
            "LEFT JOIN game_picks AS gp ".
            "  ON g.id = gp.game_id ".

            "WHERE league_year = ". $league_year .
            " AND league_week = ". $league_week .
            " AND u.id = '" . $id . "'".
            " AND gp.spread_pick IS NULL ".
            " ORDER BY Kickoff;"
            );
    } 

    public function getNotNullPicksByIdAndLeagueYearWeek($id,$league_year,$league_week) {
            return $this->db->exec(
            "SELECT ".
            "g.id AS game_id, ".
            "u.id AS user_id, ".
            "ta.id AS away_id, ".
            "UPPER(ta.abb) AS away, ".
            "th.id AS home_id, ".
            "UPPER(th.abb) AS home, ".
            "kickoff_time AS Kickoff, ".
            "CONCAT( ".
            "ta.team, ' ', '@', ' ', ".
            "th.team) AS Matchup, ".
            "s.stadium_name AS Stadium, ".
            "network AS Network, ".
            "ft.team AS Favorite, ".
            "point_spread AS 'PointSpread', ".
            "money_line AS 'MoneyLine', ".
            "ou AS 'OverUnder', ".
            "gp.spread_pick AS spread_pick, ".
            "gp.wager AS wager, ".
            "gp.money_pick AS money_pick, ".
            "gp.ou_pick AS ou_pick ".

            "FROM games AS g ".
            "CROSS JOIN users AS u ".
            "LEFT JOIN teams AS ta ".
            "  ON away = ta.id ".
            "LEFT JOIN teams AS th ".
            "  ON home = th.id ".
            "LEFT JOIN teams AS ft ".
            "  ON favorite = ft.id ".
            "LEFT JOIN stadiums AS s ".
            "  ON stadium = s.id ".
            "LEFT JOIN game_picks AS gp ".
            "  ON g.id = gp.game_id ".

            "WHERE league_year = ". $league_year .
            " AND league_week = ". $league_week .
            " AND u.id = '" . $id . "'".
            " AND gp.spread_pick IS NOT NULL ".
            " ORDER BY Kickoff;"
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
