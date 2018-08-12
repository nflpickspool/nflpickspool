<?php
class TeamWinsController extends Controller {

	function beforeroute() {
	}

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewLines(){
        $teamWins = new TeamWins($this->db);
        $season = 2018;
        $this->f3->set('season',$season);
        $this->f3->set('lines',$teamWins->getByLeagueYear($season));
		$this->f3->set('view','teamlines.htm');	
	}
}

