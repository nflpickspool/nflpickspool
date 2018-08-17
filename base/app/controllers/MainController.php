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

		 $user = new User($this->db);
		 $user->getByEmail($email);

		 if($user->dry()){
			$this->f3->reroute('/login');
		}

		if(password_verify($password, $user->password)){
			$this->f3->set('SESSION.user',$user->id);
            /* To be excluded until later
			if($user->admin === 1){
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
        $user = new User($this->db);
        $user->f_name=$this->f3->get('POST.f_name');
        $user->l_name=$this->f3->get('POST.l_name');
        $user->handle=$this->f3->get('POST.handle');
        $user->email=$this->f3->get('POST.email');
        $user->date_of_birth=$this->f3->get('POST.date_of_birth');
        $user->state=$this->f3->get('POST.state');
        $user->ZIP=$this->f3->get('POST.ZIP');
        $user->favorite_nfl_team=$this->f3->get('POST.favorite_nfl_team');
        $user->password=password_hash($this->f3->get('POST.password'),PASSWORD_DEFAULT);
        $user->save();
        $message =     
				"New User Registration:
".				"Name: " . $this->f3->get('POST.f_name') . " " . $this->f3->get('POST.l_name') . " 
".				"Username: " . $this->f3->get('POST.handle') . "
".				"Email: " . $this->f3->get('POST.email') . "
".				"State: " . $this->f3->get('POST.state') . "
".				"Zip: " . $this->f3->get('POST.ZIP') . "
".				"Favorite NFL Team: " . $this->f3->get('POST.favorite_nfl_team') . "
".				"DOB: " . $this->f3->get('POST.date_of_birth'); 

  		// subject
  		$subject = 'NFL Picks Pool: New user registration';
  		//email metadata
  		$to  = 'brandoandpat@gmail.com';
  		// To send HTML mail, the Content-type header must be set
  		$headers  = 'MIME-Version: 1.0' . "\r\n";
  		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  		// Additional headers
  		$headers .= 'From: admin@nflpickspool.com' . "\r\n";
  		// Mail it
  		mail($to, $subject, $message, $headers);
		
		$this->f3->set('view','newUserRegistered.htm');
	}

    function renderForgotPassword(){
		if($this->f3->get('SESSION.user') !== null){
			$this->f3->reroute('/home');
		}
		$this->f3->set('view','forgotpassword.htm');	
	}

    function emailNewPassword(){
		$email = $this->f3->get('POST.email');
		$user = new User($this->db);
		$user->getByEmail($email);
		if(!$user->dry()){
			$length = 8;
    			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    			$password = substr( str_shuffle( $chars ), 0, $length );
			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			$user->editPassword($user->id,$passwordHash);
        }
  		// subject
  		$subject = 'NFL Picks Pool: Password Reset';
        // message
        $message = 'Your temporary password is: '.$password.'  Log in and change it';
        //email metadata
  		$to  = $email;
  		// To send HTML mail, the Content-type header must be set
  		$headers  = 'MIME-Version: 1.0' . "\r\n";
  		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  		// Additional headers
  		$headers .= 'From: admin@nflpickspool.com' . "\r\n";
  		// Mail it
  		mail($to, $subject, $message, $headers);
        $this->f3->set('result','Password sent. If it does not arrive in 15 minutes, contact your league commissioner.');
        $this->renderForgotPassword();
	}
}

