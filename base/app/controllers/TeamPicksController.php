<?php
class TeamPicksController extends UserController {

    function extractDataFromPost($teamPicks,$x){
        $teamPicks->player_id = $this->f3->get('POST.player_id')[$x];
        $teamPicks->ou_id = $this->f3->get('POST.ou_id')[$x];
        $teamPicks->team_id = $this->f3->get('POST.team_id')[$x];
        $teamPicks->league_year = $this->f3->get('POST.league_year');
        $teamPicks->ou_pick = $this->f3->get('POST.ou_pick')[$x];
        $teamPicks->wager = $this->f3->get('POST.wager')[$x];
        $teamPicks->date_submitted = date("Y-m-d H:i:s");
    }
	
	function renderViewPicks(){
        $teamPicks = new TeamPicks($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);
        $games = new Games($this->db);
        $this->f3->set('startTime',$games->getLeagueYearStartTime($league_year));
        $this->f3->set('incompletePicks',$teamPicks->getNullPicksByIdAndLeagueYear($this->f3->get('SESSION.user'),$league_year));
        $this->f3->set('submittedPicks',$teamPicks->getNotNullPicksByIdAndLeagueYear($this->f3->get('SESSION.user'),$league_year));
        $this->f3->set('pageName','Picks for '. $league_year .'  Team Wins O/Us');
		$this->f3->set('view','teampicks.htm');	
	}

    function addTeamPicks(){
        foreach(array_keys($this->f3->get('POST.ou_pick')) as &$x){
            $teamPicks = new TeamPicks($this->db);
            //Make sure this pick hasn't been made before
            $teamPicks->load(array('ou_id=? AND player_id=?',$this->f3->get('POST.ou_id')[$x],$this->f3->get('POST.player_id')[$x]));
            if($teamPicks->dry()){
                //Also check that the pick came in on time (kickoff - 2 hours)
                $games = new Games($this->db);
                $startTime=$games->getLeagueYearStartTime($this->f3->get('POST.league_year'));
                if(strtotime($startTime[0]['startTime']) > time()+(60*120)){
                    $this->extractDataFromPost($teamPicks,$x);
                    $teamPicks->save();
                }
            }
        }
        $this->renderViewPicks();
    }

    function updateTeamPicks(){
        foreach(array_keys($this->f3->get('POST.ou_pick')) as &$x){
            //Check that the pick came in on time (kickoff - 2 hours)
            $games = new Games($this->db);
            $startTime=$games->getLeagueYearStartTime($this->f3->get('POST.league_year'));
            if(strtotime($startTime[0]['startTime']) > time()+(60*120)){
                $teamPicks = new TeamPicks($this->db);
                $teamPicks->load(array('id=?',$this->f3->get('POST.id')[$x]));
                $this->extractDataFromPost($teamPicks,$x);
                $teamPicks->update();
            }
        }
        $this->renderViewPicks();
        //$this->f3->set('view','post.htm');
    }

    function renderLeaguePicks(){
        $teamPicks = new TeamPicks($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);
        $games = new Games($this->db);
        $this->f3->set('startTime',$games->getLeagueYearStartTime($league_year));
        $this->f3->set('leaguePicks',$teamPicks->getLeaguePicksByLeagueYear($league_year));
        $this->f3->set('pageName','League Picks for '. $league_year .'  Team Wins O/Us');
		$this->f3->set('view','leaguepicksteam.htm');	
    }
}