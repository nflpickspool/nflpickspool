<?php
class TeamPicksController extends UserController {

    function extractDataFromPost($teamPicks,$x){
        $teamPicks->player_id = $this->f3->get('POST.player_id')[$x];
        $teamPicks->ou_id = $this->f3->get('POST.ou_id')[$x];
        $teamPicks->team_id = $this->f3->get('POST.team_id')[$x];
        $teamPicks->league_year = $this->f3->get('POST.league_year');
        $teamPicks->ou_pick = $this->f3->get('POST.ou_pick')[$x];
        $teamPicks->is_lock = $this->f3->get('POST.is_lock')[$x];
        $teamPicks->date_submitted = date("Y-m-d H:i:s");
    }
    
	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewPicks(){
        $teamPicks = new TeamPicks($this->db);
        $league_year = 2018;
        $this->f3->set('league_year',$league_year);
        $this->f3->set('incompletePicks',$teamPicks->getNullPicksByHandleAndLeagueYear($this->f3->get('SESSION.user'),$league_year));
        $this->f3->set('submittedPicks',$teamPicks->getNotNullPicksByHandleAndLeagueYear($this->f3->get('SESSION.user'),$league_year));
        $this->f3->set('pageName','Picks for '. $league_year .'  Team Wins O/Us');
		$this->f3->set('view','teampicks.htm');	
	}

    function addTeamPicks(){
        foreach(array_keys($this->f3->get('POST.ou_pick')) as &$x){
            $teamPicks = new TeamPicks($this->db);
            $this->extractDataFromPost($teamPicks,$x);
            $teamPicks->save();
        }
        $this->f3->reroute('/teampicks');
    }

    function updateTeamPicks(){
        foreach(array_keys($this->f3->get('POST.ou_pick')) as &$x){
            $teamPicks = new TeamPicks($this->db);
            $teamPicks->load(array('id=?',$this->f3->get('POST.id')[$x]));
            $this->extractDataFromPost($teamPicks,$x);
            $teamPicks->update();
        }
        $this->f3->reroute('/teampicks');
        //$this->f3->set('view','post.htm');

    }
}

