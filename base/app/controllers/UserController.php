<?php
class UserController extends Controller {

	function beforeroute() {
	}

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderHomePage(){
		$this->f3->set('view','home.htm');	
	}
}

