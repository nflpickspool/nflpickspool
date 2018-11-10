<?php

class Games extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'games');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

    public function getRecentlyAddedGames() {
        $sql = "
            SELECT
            g.id,
            g.league_year,
            g.league_week,
            kickoff_time,
            ta.team AS away,
            th.team AS home,
            network,
            ft.team AS favorite,
            point_spread,
            money_line,
            ou
            FROM games AS g
            LEFT JOIN teams AS ta
            ON away = ta.id
            LEFT JOIN teams AS th
            ON home = th.id
            LEFT JOIN teams AS ft
            ON favorite = ft.id
            ORDER BY g.id DESC
            LIMIT 20"
            ;
        return $this->db->exec($sql);

	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

    public function getLeagueYearStartTime($league_year) {
		return $this->db->exec(
            'SELECT MIN(kickoff_time) AS startTime FROM games WHERE league_year = '.$league_year.';'
		);
	}
    
    public function getLeagueYearList() {
		return $this->db->exec(
			'SELECT DISTINCT league_year FROM games ORDER BY league_year DESC;'
		);
	}

    public function getWeekListForLeagueYear($league_year) {
		return $this->db->exec(
			'SELECT DISTINCT league_week FROM games WHERE league_year = '.$league_year.' ORDER BY league_week DESC;'
		);
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

   	public function getRecentResults() {
        $sql = "
            SELECT
            g.id AS game_id,
            g.kickoff_time, 
            ta.team AS away,
            g.away_score,
            th.team AS home,
            g.home_score
            FROM games as g
            LEFT JOIN teams AS ta
            ON away = ta.id
            LEFT JOIN teams AS th
            ON home = th.id
            WHERE kickoff_time < NOW()
            AND (
            away_score IS NOT NULL 
            AND
            home_score IS NOT NULL
            )
            ORDER BY kickoff_time DESC
            LIMIT 50";
        return $this->db->exec($sql);
	}

   	public function getPastGamesWithoutResults() {
        $sql = "
            SELECT
            g.id AS game_id,
            g.kickoff_time, 
            ta.team AS away,
            th.team AS home
            FROM games as g
            LEFT JOIN teams AS ta
            ON away = ta.id
            LEFT JOIN teams AS th
            ON home = th.id
            WHERE kickoff_time < NOW()
            AND (
            away_score IS NULL 
            OR
            home_score IS NULL
            )
            ORDER BY kickoff_time";
        return $this->db->exec($sql);
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
