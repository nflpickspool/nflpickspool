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
                "CONCAT(t.conference, ' ',t.division) AS Division, ".
                "tw.wins_line AS OU, ".
                "tp.ou_pick ".
                "FROM teams t ".
                "CROSS JOIN users u ".
                "LEFT JOIN team_wins AS tw ".
                "ON t.id = tw.team ".
                "LEFT JOIN team_picks AS tp ".
                "ON tw.id = tp.ou_id AND u.id = tp.player_id ".
                "WHERE tw.league_year = " . $league_year . " AND".
                " tp.ou_pick IS NULL AND ".
                "u.id = '" . $id . "'".
                "ORDER BY Division,Team;"
            );
    } 

	public function getNotNullPicksByIdAndLeagueYear($id,$league_year) {
            return $this->db->exec(
                "SELECT tp.id AS id, ".
                "u.id AS player_id, ".
                "tw.id AS ou_id, ".
                "t.id AS team_id, ".
                "CONCAT(t.region, ' ',t.team) AS Team, ".
                "CONCAT(t.conference, ' ',t.division) AS Division, ".
                "tw.wins_line AS OU, ".
                "tp.ou_pick, ".
                "tp.wager ".
                "FROM teams t ".
                "CROSS JOIN users u ".
                "LEFT JOIN team_wins AS tw ".
                "ON t.id = tw.team ".
                "LEFT JOIN team_picks AS tp ".
                "ON tw.id = tp.ou_id AND u.id = tp.player_id ".
                "WHERE tw.league_year = " . $league_year . " AND".
                " tp.ou_pick IS NOT NULL AND ".
                "u.id = '" . $id . "'".
                "ORDER BY Division,Team;"
            );
    }

	public function getLeaguePicksByLeagueYear($league_year) {
        $sql = "
            SELECT
            GROUP_CONCAT(DISTINCT
              CONCAT(
                'max(case when player_id = ',
                player_id,
                ' THEN CONCAT(ou_pick,'','',wager) END) AS `',
                handle,
                '`'
              )
            ) AS `pivot_columns`
            FROM team_picks AS tp
            LEFT JOIN users as u
            ON tp.player_id = u.id"
            ;
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $pivot_columns = $row['pivot_columns'];

        $sql = "
            SELECT CONCAT(t.region, ' ',t.team) AS Team, 
            CONCAT(t.conference, ' ',t.division) AS Division,
            {$pivot_columns}
            FROM team_picks AS tp
            LEFT JOIN teams AS t
            ON tp.team_id = t.id
            WHERE tp.league_year = ".$league_year."
            GROUP BY team_id
            ORDER BY Division,Team"
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
