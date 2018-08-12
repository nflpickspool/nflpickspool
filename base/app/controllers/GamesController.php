<?php
class GamesController extends UserController {

	function beforeroute() {
	}

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewLines(){
        $games = new Games($this->db);
        $season = 2018;
        $week = 1;
        $this->f3->set('season',$season);
        $this->f3->set('week',$week);
        $this->f3->set('pageName',$season .' Week '. $week .' Game Lines');
        $this->f3->set('games',$games->getByLeagueYearAndWeek($season,$week));
		$this->f3->set('view','gamelines.htm');	
	}
}

