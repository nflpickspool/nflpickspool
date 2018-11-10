<?php
class UserController extends Controller {

	function beforeroute() {
		if($this->f3->get('SESSION.user') === null){
			$this->f3->reroute('/login');
			exit;
		}
        $user = new User($this->db);
        $user->getById($this->f3->get('SESSION.user'));
        $this->f3->set('handle',$user->handle);

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
    
	function renderStandings(){
        $league_id = 1; //Not sure where this goes yet
        $gamePicks = new GamePicks($this->db);
        $purse = new Purse($this->db);
        $this->f3->set('standings',$gamePicks->getStandings());
        $this->f3->set('purse',$purse->getByLeagueId($league_id));
        $this->f3->set('pageName','Standings');
		$this->f3->set('view','standings.htm');	
	}
    
	function renderRules(){
        $this->f3->set('pageName','Rules');
		$this->f3->set('view','about.htm');	
	}

	function renderProfile(){
        $this->f3->set('pageName','Profile');
		$this->f3->set('view','profile.htm');	
	}

	function renderForgotPassword(){
        $this->f3->set('pageName','Profile');
		$this->f3->set('view','profile.htm');	
	}

    function updatePassword(){
		$user_id = $this->f3->get('SESSION.user');
		$user = new User($this->db);
		$user->getById($user_id);
		$currentPassword = $this->f3->get('POST.currentPassword');
		$newPassword = $this->f3->get('POST.newPassword');
		$repeatPassword = $this->f3->get('POST.repeatPassword');
		if(!password_verify($currentPassword, $user->password)){
			$this->f3->set('passwordResult','Incorrect current password');
		} else if($newPassword !== $repeatPassword) {
			$this->f3->set('passwordResult','New passwords do not match');
		} else {
			$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
			$user->editPassword($user_id,$passwordHash);
			$this->f3->set('passwordResult','Password changed successfully');
		}
        $this->renderProfile();

    }
    
	function logout(){
		$this->f3->clear('SESSION.user');
		$this->f3->clear('SESSION.admin');
		$this->f3->reroute('/login');
	}

    function addGames(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}

		$teams = new Teams($this->db);
        $this->f3->set('teams',$teams->all());

        $games = new Games($this->db);
        $this->f3->set('recentlyAddedGames', $games->getRecentlyAddedGames());

        $this->f3->set('pageName','Add Games');
		$this->f3->set('view','addgames.htm');	
	}

    function enterResults(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}

        $games = new Games($this->db);
        $this->f3->set('gamesNeedingResults', $games->getPastGamesWithoutResults());
        $this->f3->set('recentResults', $games->getRecentResults());

        $this->f3->set('pageName','Enter Results');
		$this->f3->set('view','enterresults.htm');	
	}
}

