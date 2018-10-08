<?php
class TeamWinsController extends UserController {

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewLines(){
        $teamWins = new TeamWins($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('season',$league_year);
        $this->f3->set('pageName',$league_year .'  Team Wins O/Us');
        $this->f3->set('lines',$teamWins->getByLeagueYear($league_year));
		$this->f3->set('view','teamlines.htm');	
	}

    function updateTeamWins(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);

        $teamWins = new TeamWins($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('teamWinsList', $teamWins->getByLeagueYear($league_year));

        $this->f3->set('pageName','Update Team Win Totals');
		$this->f3->set('view','updateteamwins.htm');	
	}

    function editTeamWins(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}

        foreach(array_keys($this->f3->get('POST.id')) as &$x){
            $id = $this->f3->get('POST.id')[$x];
            $teamWins = new TeamWins($this->db);
            $teamWins->load(array('id=?',$id));
            $teamWins->wins_actual = $this->f3->get('POST.wins_actual')[$x];
            $teamWins->losses = $this->f3->get('POST.losses')[$x];
            $teamWins->ties = $this->f3->get('POST.ties')[$x];
            $teamWins->update();
            $this->addPointsToPicks($id);
        }

        $this->updateTeamWins();
    }

    function addPointsToPicks($ou_id){
        $teamWins = new TeamWins($this->db);
        $teamWins->load(array('id=?',$ou_id));
        $games_remaining = 16 - $teamWins->wins_actual - $teamWins->losses - $teamWins->ties;
        //Check if win total is over
        if($teamWins->wins_actual > $teamWins->wins_line){
            $teamPicks = new TeamPicks($this->db);
            $picksNeedingPoints=$teamPicks->getByOuId($ou_id);
            foreach ($picksNeedingPoints as &$picksObject) {
                $ou_pick=$picksObject->ou_pick;
                $wager=$picksObject->wager;
                if($ou_pick === "O"){
                    $pts_ou = 2*$wager;
                } else {
                    $pts_ou = 2-2*$wager;
                }
                $picksObject->editPoints($picksObject->id,$pts_ou);
            }
        //Check if remaining games + wins is under
        } else if($teamWins->wins_actual + $games_remaining <  $teamWins->wins_line){
            $teamPicks = new TeamPicks($this->db);
            $picksNeedingPoints=$teamPicks->getByOuId($ou_id);
            foreach ($picksNeedingPoints as &$picksObject) {
                $ou_pick=$picksObject->ou_pick;
                $wager=$picksObject->wager;
                if($ou_pick === "U"){
                    $pts_ou = 2*$wager;
                } else {
                    $pts_ou = 2-2*$wager;
                }
                $picksObject->editPoints($picksObject->id,$pts_ou);
            }
        // No points yet (useful for mistakes)
        } else {
            $teamPicks = new TeamPicks($this->db);
            $picksNeedingPoints=$teamPicks->getByOuId($ou_id);
            foreach ($picksNeedingPoints as &$picksObject) {
                $picksObject->editPoints($picksObject->id,0);
            }
        }
    }
}

