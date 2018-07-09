<?php
class OddsController extends Controller {

	function beforeroute() {
		if($this->f3->get('SESSION.user') === null){
			$this->f3->reroute('/login');
			exit;
		}
		//from Controller Class
		//Should probably find a way to avoid duplication
		if($this->f3->get('SESSION.admin') === null){
		 	$this->f3->set('menu','menu.htm');
		} else {
		 	$this->f3->set('menu','admin-menu.htm');
		}
		if($this->f3->get('SESSION.admin') === null){
			$this->f3->error(404);
		}
	}

	function render(){
		$this->f3->set('view','odds.htm');
		$odds = new Odds($this->db);
		$this->f3->set('existingOdds', $odds->getFutureOdds());

        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addOdds(){
		$odds = new Odds($this->db);
		$odds->date = $this->f3->get('POST.date');
		$odds->away = $this->f3->get('POST.away');
		$odds->home = $this->f3->get('POST.home');
		$odds->spread = $this->f3->get('POST.spread');
		$odds->moneyline = $this->f3->get('POST.moneyline');
		$odds->save();

		$this->f3->reroute('/setodds');		
	}

	function updateOdds(){
		$this->f3->set('view','updateodds.htm');
		$odds = new Odds($this->db);
		$this->f3->set('existingOdds', $odds->getFutureOdds());

        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function writeUpdatedOdds(){
		$numberOfRows=count($this->f3->get('POST.spread'));
		$odds = new Odds($this->db);
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			if(!empty($this->f3->get('POST.del')[$x])){
				$odds->delete($id);
			} else {
				$spread=$this->f3->get('POST.spread')[$x];
				$moneyline=$this->f3->get('POST.moneyline')[$x];
    				$odds->editSpreadAndML($id,$spread,$moneyline);
			}
		}
		$this->f3->reroute('/setodds');
	}
}
