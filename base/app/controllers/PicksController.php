<?php
class PicksController extends Controller {

	function render(){
		$odds = new Odds($this->db);
		$picks = new Picks($this->db);

		$futureOdds = $odds->getFutureOdds();
		$oddsForNewPicks = array();
		$existingPicks = array();
		$oddsForExistingPicks = array();

		foreach ($futureOdds as &$oddsObject) {
			$existingPicksForUser = $picks->getByOddsIdAndEmail($oddsObject->id,$this->f3->get('SESSION.user'));
			if(empty($existingPicksForUser)){
				//echo "New Pick " . $oddsObject->moneyline . "<br>";
				array_push($oddsForNewPicks,$oddsObject);
			} else {			
				foreach ($existingPicksForUser as &$picksObject) {
					//echo "Existing Pick " . $picksObject->ml_pick . "<br>";
					array_push($oddsForExistingPicks,$oddsObject);
					array_push($existingPicks,$picksObject);
				}
			}
		}
		$this->f3->set('oddsForNewPicks', $oddsForNewPicks);
		$this->f3->set('existingPicks', $existingPicks);
		$this->f3->set('oddsForExistingPicks', $oddsForExistingPicks);

		$this->f3->set('view','picks.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addPicks(){
		//var_dump($_POST);
		
		$numberOfRows=count($this->f3->get('POST.spread_pick'));
		for ($x = 0; $x < $numberOfRows; $x++) {
			$picks = new Picks($this->db);
			$picks->email = $this->f3->get('SESSION.user');
			$picks->odds_id = $this->f3->get('POST.odds_id')[$x];
			$picks->spread_pick = $this->f3->get('POST.spread_pick')[$x];
			$picks->ml_pick = $this->f3->get('POST.ml_pick')[$x];
			if(!empty($this->f3->get('POST.lock')[$x])){
				$picks->lock = 1;
			} else {
				$picks->lock = 0;
			}
			$picks->date_submitted = date("Y-m-d H:i:s");
			$picks->save();
		}

		$this->f3->reroute('/makepicks');
		
	}

	//TODO:There is a lot of duplication here from render...
	function updatePicks(){
		
		$this->f3->set('view','updatepicks.htm');

		$odds = new Odds($this->db);
		$picks = new Picks($this->db);

		$futureOdds = $odds->getFutureOdds();
		$existingPicks = array();
		$oddsForExistingPicks = array();

		foreach ($futureOdds as &$oddsObject) {
			$existingPicksForUser = $picks->getByOddsIdAndEmail($oddsObject->id,$this->f3->get('SESSION.user'));
			if(!empty($existingPicksForUser)){
				foreach ($existingPicksForUser as &$picksObject) {
					//echo "Existing Pick " . $picksObject->ml_pick . "<br>";
					array_push($oddsForExistingPicks,$oddsObject);
					array_push($existingPicks,$picksObject);
				}
			}
		}
		$this->f3->set('existingPicks', $existingPicks);
		$this->f3->set('oddsForExistingPicks', $oddsForExistingPicks);

        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function writeUpdatedPicks(){
		//var_dump($_POST);

		$numberOfRows=count($this->f3->get('POST.spread_pick'));
		$picks = new Picks($this->db);
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			if(!empty($this->f3->get('POST.del')[$x])){
				$picks->delete($id);
			} else {
				$spread_pick=$this->f3->get('POST.spread_pick')[$x];
				$ml_pick=$this->f3->get('POST.ml_pick')[$x];
				if(!empty($this->f3->get('POST.lock')[$x])){
					$lock = 1;
				} else {
					$lock = 0;
				}
    				$picks->editSpreadAndMLPicks($id,$spread_pick,$ml_pick,$lock);
			}
		}
		$this->f3->reroute('/makepicks');
	}
	
	function renderStandings(){
		$picks = new Picks($this->db);
		$this->f3->set('standings',$picks->getStandings());
		$this->f3->set('view','standings.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}
}
