<?php
class GamesController extends UserController {

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewLines(){
        $games = new Games($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('season',$league_year);
        $this->f3->set('week',$league_week);
        $this->f3->set('pageName',$league_year .' Week '. $league_week .' Game Lines');
        $this->f3->set('games',$games->getByLeagueYearAndWeek($league_year,$league_week));
		$this->f3->set('view','gamelines.htm');	
	}
}

