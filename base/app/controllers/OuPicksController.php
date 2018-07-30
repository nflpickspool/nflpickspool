<?php
class OuPicksController extends Controller {

	function render(){
		$ouodds = new OuOdds($this->db);
		$this->f3->set('seasonList',$ouodds->getSeasonList());
		$email=$this->f3->get('SESSION.user');
		//TODO:Duplicated from renderScoreboard
		if(empty($this->f3->get('POST.season'))){
			//This causes problems when no odds are set. 
			//$season=$ouodds->getLatestSeason()[0]['season'];
			//Maybe we can get away with using the current year?
			$season=date("Y");
		} else {
			$season=explode("-",$this->f3->get('POST.season'))[0];
		}
		//TODO: Make getIncompletePicks query work
		//$this->f3->set('incompletePicks',$picks->getIncompletePicks($email,$season,$week));

		//TODO: Duplication from PicksController->renderIncompletePicks
		$futureOus = $ouodds->getFutureOus();
		$oddsForIncompleteOuPicks = array();
		$existingOus = array();
		$oddsForExistingOus = array();

		$oupicks = new OuPicks($this->db);
		foreach ($futureOus as &$ouObject) {
			$existingOusForUser = $oupicks->getByOddsIdAndEmail($ouObject->id,$email);
			if(empty($existingOusForUser)){
				array_push($oddsForIncompleteOuPicks,$ouObject);
			} else {			
				foreach ($existingOusForUser as &$picksObject) {
					array_push($oddsForExistingOus,$oddsObject);
					array_push($existingOus,$picksObject);
				}
			}
		}
		$this->f3->set('season', $season);
		$this->f3->set('oddsForIncompleteOuPicks', $oddsForIncompleteOuPicks);
		$this->f3->set('existingOus', $existingOus);
		$this->f3->set('oddsForExistingOus', $oddsForExistingOus);

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$this->f3->set('closedPicks',$oupicks->getClosedPicks($email,$season));

		$this->f3->set('view','prepicks.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	//TODO: duplication from PicksController->addPicks
	function addOuPicks(){
		//var_dump($_POST);
		
		$numberOfRows=count($this->f3->get('POST.pick'));
		for ($x = 0; $x < $numberOfRows; $x++) {
			$oupicks = new OuPicks($this->db);
			$oupicks->email = $this->f3->get('SESSION.user');
			$oupicks->odds_id = $this->f3->get('POST.odds_id')[$x];
			$oupicks->pick = $this->f3->get('POST.pick')[$x];
			if(!empty($this->f3->get('POST.lock')[$x])){
				$oupicks->lock = 1;
			} else {
				$oupicks->lock = 0;
			}
			$oupicks->date_submitted = date("Y-m-d H:i:s");
			$oupicks->save();
		}

		$this->f3->reroute('/incompletepicks');
		
	}

	//TODO:There is a lot of duplication here from render...
	function updatePicks(){
		$email=$this->f3->get('SESSION.user');

		$ouodds = new OuOdds($this->db);
		$oupicks = new OuPicks($this->db);
		
		$futureOus = $ouodds->getFutureOus();
		$existingOus = array();
		$oddsForExistingOus = array();

		foreach ($futureOus as &$ouObject) {
			$existingOusForUser = $oupicks->getByOddsIdAndEmail($ouObject->id,$email);
			if(!empty($existingOusForUser)){
				foreach ($existingOusForUser as &$picksObject) {
					array_push($oddsForExistingOus,$oddsObject);
					array_push($existingOus,$picksObject);
				}
			}
		}
		$this->f3->set('existingOus', $existingOus);
		$this->f3->set('oddsForExistingOus', $oddsForExistingOus);

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$this->f3->set('view','updateoupicks.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function writeUpdatedPicks(){
		$numberOfRows=count($this->f3->get('POST.pick'));
		$oupicks = new OuPicks($this->db);
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			$pick=$this->f3->get('POST.pick')[$x];
			if(!empty($this->f3->get('POST.lock')[$x])){
				$lock = 1;
			} else {
				$lock = 0;
			}
			$oupicks->editOuPicks($id,$pick,$lock);
		}
		$this->f3->reroute('/makeprepicks');
	}

}
