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
}

