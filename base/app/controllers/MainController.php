<?php
class MainController extends Controller {

	function render(){
		$this->f3->set('view','home.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}
}
