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
		$message =     
				"New User Registration:
".				"Name: " . $this->f3->get('POST.firstName') . " " . $this->f3->get('POST.lastName') . " 
".				"Username: " . $this->f3->get('POST.username') . "
".				"Email: " . $this->f3->get('POST.email') . "
".				"State: " . $this->f3->get('POST.state') . "
".				"Zip: " . $this->f3->get('POST.zip') . "
".				"Favorite NFL Team: " . $this->f3->get('POST.favoriteTeam') . "
".				"DOB: " . $this->f3->get('POST.dob'); 

  		//email metadata
  		$to  = 'brandoandpat@gmail.com';
  		// subject
  		$subject = 'NFL Picks Pool: New user registration';
  		// To send HTML mail, the Content-type header must be set
  		$headers  = 'MIME-Version: 1.0' . "\r\n";
  		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  		// Additional headers
  		$headers .= 'From: admin@nflpickspool.com' . "\r\n";
  		// Mail it
  		mail($to, $subject, $message, $headers);
		
		$this->f3->set('view','newUserRegistered.htm');
	}
}

