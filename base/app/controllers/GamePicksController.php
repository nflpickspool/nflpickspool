<?php
class GamePicksController extends UserController {

	function beforeroute() {
	}

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewPicks(){
        $teamWins = new TeamWins($this->db);
        $season = 2018;
        $this->f3->set('season',$season);
        $this->f3->set('pageName',$season .'  Game Picks');
        $this->f3->set('view','gamepicks.htm');	
	}
}

