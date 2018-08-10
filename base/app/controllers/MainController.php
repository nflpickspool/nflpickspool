<?php
class MainController extends Controller {

	function beforeroute() {
	}

	function afterroute() {
		$template=new Template;
        	echo $template->render('landingLayout.htm');
	}
	
	function renderLandingPage(){
		$this->f3->set('view','landing.htm');	
	}

	function renderAboutPage(){
		$this->f3->set('view','about.htm');	
	}

	function renderLoginPage(){
		$this->f3->set('view','login.htm');	
	}

	function renderSignUpPage(){
		$teams = new Teams($this->db);
            	$this->f3->set('teams',$teams->all());
		$this->f3->set('view','signup.htm');	
	}

	//Placeholder for now
	function authenticate(){
		$this->f3->reroute('/login');
	}

	//Placeholder for now
	function registerNewUser(){
		//var_dump($_POST);
		$smtp = new SMTP ( 'smtp.1and1.com', 587, 'TLS', 'admin@nflpickspool.com', 'Youbestn0tmiss!' );

		$smtp->set('From', '"NFL Picks Pool Admin" <admin@nflpickspool.com>');
		$smtp->set('To', '<brandoandpat@gmail.com>');
		$smtp->set('Subject', 'New User Registration');  
		$smtp->set('Errors-to', '<admin@nflpickspool.com>');  

		$message =     
				"New User Registration:
".				"Name: " . $this->f3->get('POST.firstName') . " " . $this->f3->get('POST.lastName') . " 
".				"Username: " . $this->f3->get('POST.username') . "
".				"Email: " . $this->f3->get('POST.email') . "
".				"State: " . $this->f3->get('POST.state') . "
".				"Zip: " . $this->f3->get('POST.zip') . "
".				"Favorite NFL Team: " . $this->f3->get('POST.favoriteTeam') . "
".				"DOB: " . $this->f3->get('POST.dob'); 

		$sent = $smtp->send($message, TRUE);

		/* Keep for debug
		$mylog = $smtp->log();

		$sentText = 'not sent';

		$headerText = 'does not exist';

		if ($sent)
		{
		    $sentText = 'was sent';
		}

		if ($smtp->exists('Date'))
		{
		    $headerText = 'exists';
		}

		echo "email result: " . $sentText . ",mylog: " . $mylog . ", header: " . $headerText;
		*/
		
		$this->f3->set('view','newUserRegistered.htm');
	}
}

