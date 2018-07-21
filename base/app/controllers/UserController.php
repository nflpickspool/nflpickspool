<?php
class UserController extends Controller {

	function beforeroute() {
		//TODO:Had to copy this from Controller. Should avoid duplication
		if($this->f3->get('SESSION.admin') === null){
		 	$this->f3->set('menu','menu.htm');
		} else {
		 	$this->f3->set('menu','admin-menu.htm');
		}
	}

	function render(){
		if($this->f3->get('SESSION.user') !== null){
			$this->f3->reroute('/');
		}
		 $template=new Template;
        	 echo $template->render('login.htm');
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
			$this->f3->set('SESSION.user',$user->email);
			if($user->admin === 1){
				$this->f3->set('SESSION.admin',1);
			}
			$this->f3->reroute('/');
		} else {
		  	$this->f3->reroute('/login');
		}
	}

	function displayProfile(){
		$this->f3->set('view','profile.htm');
		$user = new User($this->db);
		$user->getByEmail($this->f3->get('SESSION.user'));
		$this->f3->set('user',$user);
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function changePassword(){
		$this->f3->set('view','changePassword.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function forgotPassword(){
		$template=new Template;
        	echo $template->render('forgotPassword.htm');	
	}

	function updatePassword(){
		$email = $this->f3->get('SESSION.user');
		$user = new User($this->db);
		$user->getByEmail($email);
		$currentPassword = $this->f3->get('POST.currentPassword');
		$newPassword = $this->f3->get('POST.newPassword');
		$repeatPassword = $this->f3->get('POST.repeatPassword');
		if(!password_verify($currentPassword, $user->password)){
			$this->f3->set('passwordResult','Incorrect current password');
		} else if($newPassword !== $repeatPassword) {
			$this->f3->set('passwordResult','New passwords do not match');
		} else {
			$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
			$user->editPassword($email,$passwordHash);
			$this->f3->set('passwordResult','Password changed successfully');
		}
		$this->f3->set('view','updatePassword.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');	
	}

	function emailTemporaryPassword(){
		$email = $this->f3->get('POST.inputEmail');
		$user = new User($this->db);
		$user->getByEmail($email);
		if(!$user->dry()){
			$length = 8;
    			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    			$password = substr( str_shuffle( $chars ), 0, $length );
			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			$user->editPassword($email,$passwordHash);

			$smtp = new SMTP ( 'smtp.1and1.com', 587, 'TLS', 'admin@nflpickspool.com', 'Youbestn0tmiss!' );

			$smtp->set('From', '"NFL Picks Pool Admin" <admin@nflpickspool.com>');
			$smtp->set('To', '<'.$email.'>');
			$smtp->set('Subject', 'Password Reset');  
			$smtp->set('Errors-to', '<admin@nflpickspool.com>');  

			$message = 'Your temporary password is: '.$password.'  Log in and change it'; 

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
		}
		$this->f3->reroute('/passwordSent');
	}

	function passwordSent(){
        	$template=new Template;
        	echo $template->render('passwordSent.htm');
	}

	function logout(){
		$this->f3->clear('SESSION.user');
		$this->f3->clear('SESSION.admin');
		$this->f3->reroute('/login');
	}
}
