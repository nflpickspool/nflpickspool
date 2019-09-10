<?php

class SuicidePicks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'suicide_picks');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getById($id) {
	    $this->load(array('id=?',$id));
	    return $this->query;
	}

    public function getPickByIdYearAndWeek($user_id,$league_year,$league_week) {
		return $this->db->exec(
            "SELECT sp.league_year, sp.league_week, g.kickoff_time, sp.suicide_pick ".
            "FROM suicide_picks AS sp ".
            "LEFT JOIN games as g ".
            "ON game_id = g.id ".
            "WHERE sp.league_year =  ".$league_year." ". 
            "AND sp.league_week =  ".$league_week." ".
            "AND sp.user_id = ".$user_id .";"
        );
	}


    public function checkIfAlive($user_id,$league_year) {
		return $this->db->exec(
            "SELECT min(correct) ".
            "AS alive ".
            "FROM suicide_picks ".
            "GROUP BY league_year, user_id ".
            "HAVING league_year = ".$league_year ." ".
            "AND user_id = ".$user_id.";"
		);
	}

    public function getAvailableTeamsForWeek($user_id,$league_year,$league_week) {
		return $this->db->exec(
            "SELECT ".
            "g.id as game_id, ".
            "ta.id as team_id, ".
            "ta.team as team, ".
            "th.team as other_team, ".
            "CONCAT(IF(ta.team=ft.team, '(-', '(+'),g.point_spread, ') @  ') AS point_spread, ".
            "kickoff_time, ".
            "u.id as user_id, ".
            "sp.suicide_pick, ".
            "sp.correct ".
            "FROM games as g ".
            "CROSS JOIN users AS u ".
            "LEFT JOIN teams AS ta ". 
            "  ON away = ta.id ".
            "LEFT JOIN teams AS th ". 
            "  ON home = th.id ".
            "LEFT JOIN teams AS ft ".
            "  ON favorite = ft.id ".
            "LEFT JOIN suicide_picks AS sp ".
            "  ON ta.id = sp.suicide_pick ".
            "WHERE g.league_year =  ".$league_year." ". 
            " AND g.league_week =  ".$league_week." ".
            " AND u.id = ".$user_id." ".
            " AND kickoff_time > '".date('Y-m-d H:i:s')."' ".
            " AND correct IS NULL ".
            "UNION ".
            "SELECT ".
            "g.id, ".
            "th.id, ".
            "th.team, ".
            "ta.team, ".
            "CONCAT(IF(th.team=ft.team, '(-', '(+'),g.point_spread,') vs.'), ".
            "kickoff_time, ".
            "u.id, ".
            "sp.suicide_pick, ".
            "sp.correct ".
            "FROM games as g ".
            "CROSS JOIN users AS u ".
            "LEFT JOIN teams AS ta ". 
            "  ON away = ta.id ".
            "LEFT JOIN teams AS th ". 
            "  ON home = th.id ".
            "LEFT JOIN teams AS ft ".
            "  ON favorite = ft.id ".
            "LEFT JOIN suicide_picks AS sp ".
            "  ON th.id = sp.suicide_pick ".
            "WHERE g.league_year =  ".$league_year." ". 
            " AND g.league_week =  ".$league_week." ".
            " AND u.id = ".$user_id." ".
            " AND kickoff_time > '".date('Y-m-d H:i:s')."' ".
            " AND correct IS NULL".
            " ORDER BY kickoff_time,team;"
		);
    }

    public function getPreviousSuicidePicks($user_id,$league_year,$league_week) {
        return $this->db->exec(
            "SELECT ".
            "sp.league_week as league_week, ".
            "sp.id as game_id, ".
            "t.team as team, ".
            "u.id as user_id, ".
            "sp.suicide_pick, ".
            "sp.correct ".
            "FROM suicide_picks as sp ".
            "CROSS JOIN users AS u ".
            "ON sp.user_id = u.id ".
            "LEFT JOIN teams AS t ".
            "ON sp.suicide_pick = t.id ".
            "WHERE sp.league_year =  ".$league_year." ". 
            " AND u.id = ".$user_id.
            " ORDER BY league_week;"
        );
    }

    	public function getLeaguePicksByLeagueYear($league_year) {
        $sql = "
            SELECT
            GROUP_CONCAT(DISTINCT
              CONCAT(
                'max(case when user_id = ',
                user_id,
                ' THEN CONCAT(suicide_pick,'','',t.team) END) AS `',
                handle,
                '`'
              )
            ) AS `pivot_columns`
            FROM suicide_picks AS sp
            LEFT JOIN users as u
            ON sp.user_id = u.id
            LEFT JOIN teams AS t
            ON sp.suicide_pick = t.id
            WHERE u.active >0"
            ;
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $pivot_columns = $row['pivot_columns'];

        $sql = "
            SELECT
            sp.league_week AS Week,
            {$pivot_columns}
            FROM suicide_picks AS sp
            LEFT JOIN games as g
            ON sp.game_id = g.id
            LEFT JOIN teams AS t
            ON sp.suicide_pick = t.id
            WHERE sp.league_year = ".$league_year."
            AND kickoff_time < NOW()
            GROUP BY Week
            ORDER BY Week"
            ;
            
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
