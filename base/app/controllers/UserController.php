<?php
class UserController extends Controller {

	function beforeroute() {
	}

	function render(){
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

	function logout(){
		$this->f3->clear('SESSION.user');
		$this->f3->clear('SESSION.admin');
		$this->f3->reroute('/login');
	}
}
