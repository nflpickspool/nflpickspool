<?php
class MainController extends Controller {

	function render(){
		//TODO: rename and fix homepage		
		$this->f3->set('view','nav-top-fixed.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}
}
