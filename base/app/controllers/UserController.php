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
        $user = new User($this->db);
        $user->getById($this->f3->get('SESSION.user'));
        $this->f3->set('handle',$user->handle);
        $this->f3->set('pageName','Home');
		$this->f3->set('view','home.htm');	
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
}

