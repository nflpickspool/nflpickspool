<?php
class TeamPicksController extends UserController {

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewPicks(){
        $teamPicks = new TeamPicks($this->db);
        $league_year = 2018;
        $this->f3->set('league_year',$league_year);
        $this->f3->set('incompletePicks',$teamPicks->getNullPicksByHandleAndLeagueYear($this->f3->get('SESSION.user'),$league_year));
        $this->f3->set('pageName','Picks for '. $league_year .'  Team Wins O/Us');
		$this->f3->set('view','teampicks.htm');	
	}
}

