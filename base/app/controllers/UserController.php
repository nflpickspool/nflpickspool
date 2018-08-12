<?php
class UserController extends Controller {

	function beforeroute() {
		if($this->f3->get('SESSION.user') === null){
			$this->f3->reroute('/login');
			exit;
		}
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

