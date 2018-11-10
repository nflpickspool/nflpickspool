<?php

class GamePicks extends DB\SQL\Mapper{

	public function __construct(DB\SQL $db) {
	    parent::__construct($db,'game_picks');
	}
	
	public function all() {
	    $this->load();
	    return $this->query;
	}

	public function getByGameId($game_id) {
	    $this->load(array('game_id=?',$game_id));
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
            "  ON g.id = gp.game_id AND u.id = gp.user_id ".

            "WHERE league_year = ". $league_year .
            " AND league_week = ". $league_week .
            " AND u.id = '" . $id . "'".
            " AND gp.spread_pick IS NULL ".
            " AND kickoff_time > NOW() ".
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
            "  ON g.id = gp.game_id AND u.id = gp.user_id ".

            "WHERE league_year = ". $league_year .
            " AND league_week = ". $league_week .
            " AND u.id = '" . $id . "'".
            " AND gp.spread_pick IS NOT NULL ".
            " ORDER BY Kickoff;"
            );
    }

    public function getLeaguePicksByLeagueYearAndWeek($league_year,$league_week) {
        $sql="SET SESSION group_concat_max_len = 4096";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $sql = "
            SELECT
            GROUP_CONCAT(DISTINCT
              CONCAT(
                'max(case when user_id = ',
                user_id,
                ' THEN CONCAT(spread_pick,'','',wager,'','',money_pick,'','',ou_pick,'','',spread_points,'','',money_points,'','',ou_points) END) AS `',
                handle,
                '`'
              )
            ) AS `pivot_columns`
            FROM game_picks AS gp
            LEFT JOIN users as u
            ON gp.user_id = u.id"
            ;
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $pivot_columns = $row['pivot_columns'];

        $sql = "
            SELECT 
            CONCAT(ta.team, ' ', '@', ' ', th.team) AS Matchup,
            CONCAT(UPPER(ta.abb), ' ', g.away_score, ', ', UPPER(th.abb), ' ',g.home_score) AS Result,
            ta.id AS away_id,
            UPPER(ta.abb) AS away,
            th.id AS home_id,
            UPPER(th.abb) AS home,
            kickoff_time AS Kickoff,
            ft.team AS Favorite,
            point_spread AS 'PointSpread',
            money_line AS 'MoneyLine',
            ou AS 'OverUnder',
            {$pivot_columns}
            FROM games AS g
            LEFT JOIN teams AS ta
            ON away = ta.id
            LEFT JOIN teams AS th
            ON home = th.id
            LEFT JOIN teams AS ft
            ON favorite = ft.id
            LEFT JOIN game_picks AS gp
            ON g.id = gp.game_id
            WHERE league_year = ". $league_year ."
            AND league_week = ". $league_week ."
            AND kickoff_time < NOW()
            GROUP BY Matchup,Result,away_id,away,home_id,home,
            Kickoff,Favorite,PointSpread,MoneyLine,OverUnder
            ORDER BY Kickoff,Matchup;"
            ;
            
        return $this->db->exec($sql);
    }

    public function getLeaguePicksTotalsByLeagueYearAndWeek($league_year,$league_week) {
        $sql="SET SESSION group_concat_max_len = 4096";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $sql = "
            SELECT
            GROUP_CONCAT(DISTINCT
              CONCAT(
                'sum(case when user_id = ',
                user_id,
                ' THEN spread_points+money_points+ou_points END) AS `',
                handle,
                '`'
              )
            ) AS `pivot_columns`
            FROM game_picks AS gp
            LEFT JOIN users as u
            ON gp.user_id = u.id"
            ;
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $pivot_columns = $row['pivot_columns'];

        $sql = "
            SELECT 
            {$pivot_columns}
            FROM games AS g
            LEFT JOIN game_picks AS gp
            ON g.id = gp.game_id
            WHERE league_year = ". $league_year ."
            AND league_week = ". $league_week ."
            AND kickoff_time < NOW();"
            ;
            
        return $this->db->exec($sql);
    }

	public function getStandings() {
        $sql = "
            SELECT
            u.f_name,
            u.handle,
            SUM(gp.spread_points+gp.money_points+gp.ou_points) AS total,
            SUM(gp.spread_points) AS spread_points,
            SUM(case when spread_result = 'W' then 1 else 0 end) AS spread_wins,
            SUM(case when spread_result = 'L' then 1 else 0 end) AS spread_losses,
            SUM(case when spread_result = 'W' and gp.wager = 2 then 1 else 0 end) AS lock_wins,
            SUM(case when spread_result = 'L' and gp.wager = 2 then 1 else 0 end) AS lock_losses,
            SUM(case when spread_result = 'W' and gp.wager = 3 then 1 else 0 end) AS iron_wins,
            SUM(case when spread_result = 'L' and gp.wager = 3 then 1 else 0 end) AS iron_losses,
            SUM(gp.money_points) AS money_points,
            SUM(gp.ou_points) AS ou_points,
            SUM(case when ou_result = 'W' then 1 else 0 end) AS ou_wins,
            SUM(case when ou_result = 'L' then 1 else 0 end) AS ou_losses,
            team_ou_points,team_ou_wins,team_ou_losses,
            team_ou_lock_wins,team_ou_lock_losses,team_ou_iron_wins,team_ou_iron_losses
            FROM game_picks AS gp
		    LEFT JOIN users AS u
            ON gp.user_id = u.id
            LEFT JOIN (
             SELECT
				tp.player_id as player_id,
				SUM(tp.points) AS team_ou_points,
                SUM(case when result = 'W' then 1 else 0 end) AS team_ou_wins,
                SUM(case when result = 'L' then 1 else 0 end) AS team_ou_losses,
                SUM(case when result = 'W' and wager = 2 then 1 else 0 end) AS team_ou_lock_wins,
                SUM(case when result = 'L' and wager = 2 then 1 else 0 end) AS team_ou_lock_losses,
                SUM(case when result = 'W' and wager = 3 then 1 else 0 end) AS team_ou_iron_wins,
                SUM(case when result = 'L' and wager = 3 then 1 else 0 end) AS team_ou_iron_losses
				FROM team_picks AS tp
                GROUP BY player_id
            ) t ON t.player_id = user_id
            GROUP BY user_id,
            team_ou_points,team_ou_wins,team_ou_losses,
            team_ou_lock_wins,team_ou_lock_losses,
            team_ou_points,team_ou_iron_wins,team_ou_iron_losses            
            ORDER BY total DESC"
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

    public function editPoints($game_id,$user_id,$spread_points,$spread_result,$money_points,$ou_points,$ou_result) {
	    $this->load(array('game_id=? AND user_id=?',$game_id,$user_id));
	    $this->spread_points=$spread_points;
	    $this->spread_result=$spread_result;
	    $this->money_points=$money_points;
	    $this->ou_points=$ou_points;
	    $this->ou_result=$ou_result;
	    $this->update();
	}
	
	public function delete($id) {
	    $this->load(array('id=?',$id));
	    $this->erase();
	}
}
