<?php
class SuicidePicksController extends UserController {

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewPicks(){
        $league_year = $this->f3->get('POST.league_year');
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('season',$season);
        $this->f3->set('pageName',$season .'  Suicide Picks');
        $this->f3->set('view','suicidepicks.htm');	
	}
}
