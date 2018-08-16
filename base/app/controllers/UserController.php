<?php
class UserController extends Controller {

	function beforeroute() {
		if($this->f3->get('SESSION.user') === null){
			$this->f3->reroute('/login');
			exit;
		}
        $teamWins = new TeamWins($this->db);
        $this->f3->set('teamWinsYearList',$teamWins->getLeagueYearList());
        $games = new Games($this->db);
        $gamesYearList = $games->getLeagueYearList();
        $gamesWeekList = array();
        foreach ($gamesYearList as $row) {
            array_push($gamesWeekList,$games->getWeekListForLeagueYear($row['league_year']));
        }
        $this->f3->set('gamesYearList',$gamesYearList);
        $this->f3->set('gamesWeekList',$gamesWeekList);        
	}

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderHomePage(){
        $this->f3->set('pageName','Home');
		$this->f3->set('view','home.htm');	
	}
    
	function logout(){
		$this->f3->clear('SESSION.user');
		$this->f3->clear('SESSION.admin');
		$this->f3->reroute('/login');
	}
}

