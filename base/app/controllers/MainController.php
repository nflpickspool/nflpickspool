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
		if($this->f3->get('SESSION.user') !== null){
			$this->f3->reroute('/home');
		}
		$this->f3->set('view','login.htm');
	}

	function renderSignUpPage(){
		$teams = new Teams($this->db);
        $this->f3->set('teams',$teams->all());
		$this->f3->set('view','signup.htm');	
	}

	function authenticate(){
		 $email = $this->f3->get('POST.email');
		 $password = $this->f3->get('POST.password');

		 $users = new Users($this->db);
		 $users->getByEmail($email);

		 if($users->dry()){
			$this->f3->reroute('/login');
		}

		if(password_verify($password, $users->password)){
			$this->f3->set('SESSION.user',$users->handle);
            /* To be excluded until later
			if($users->admin === 1){
				$this->f3->set('SESSION.admin',1);
			}
            */
			$this->f3->reroute('/home');
		} else {
		  	$this->f3->reroute('/login');
		}
	}

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

