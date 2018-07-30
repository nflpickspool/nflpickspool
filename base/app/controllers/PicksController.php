<?php
class PicksController extends Controller {

	function render(){
		$odds = new Odds($this->db);
		$this->f3->set('seasonList',$odds->getSeasonAndWeekList());
		$email=$this->f3->get('SESSION.user');
		//TODO:Duplicated from renderScoreboard
		if(empty($this->f3->get('POST.season'))){
			$season=$odds->getLatestSeason()[0]['season'];
			$week=$odds->getLatestWeekForSeason($season)[0]['week'];
		} else {
			$season=explode("-",$this->f3->get('POST.season'))[0];
			$week=explode("-",$this->f3->get('POST.season'))[1];
		}
		$picks = new Picks($this->db);
		//TODO: Make getIncompletePicks query work
		//$this->f3->set('incompletePicks',$picks->getIncompletePicks($email,$season,$week));

		$futureOdds = $odds->getFutureOddsBySeasonWeek($season,$week);
		$oddsForNewPicks = array();
		$existingPicks = array();
		$oddsForExistingPicks = array();

		foreach ($futureOdds as &$oddsObject) {
			$existingPicksForUser = $picks->getByOddsIdAndEmail($oddsObject->id,$email);
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
		$this->f3->set('season', $season);
		$this->f3->set('week', $week);
		$this->f3->set('oddsForNewPicks', $oddsForNewPicks);
		$this->f3->set('existingPicks', $existingPicks);
		$this->f3->set('oddsForExistingPicks', $oddsForExistingPicks);

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$this->f3->set('closedPicks',$picks->getClosedPicks($email,$season,$week));

		$this->f3->set('view','picks.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	//TODO: Duplication from render...big time
	function renderIncompletePicks(){
		//Weekly Odds
		$odds = new Odds($this->db);
		$email=$this->f3->get('SESSION.user');
		$season=$odds->getLatestSeason()[0]['season'];
		$week=$odds->getLatestWeekForSeason($season)[0]['week'];
		$picks = new Picks($this->db);
		//TODO: Make getIncompletePicks query work
		//$this->f3->set('incompletePicks',$picks->getIncompletePicks($email,$season,$week));

		$futureOdds = $odds->getFutureOddsBySeasonWeek($season,$week);
		$oddsForNewPicks = array();

		foreach ($futureOdds as &$oddsObject) {
			$existingPicksForUser = $picks->getByOddsIdAndEmail($oddsObject->id,$email);
			if(empty($existingPicksForUser)){
				//echo "New Pick " . $oddsObject->moneyline . "<br>";
				array_push($oddsForNewPicks,$oddsObject);
			}
		}
		$this->f3->set('oddsForNewPicks', $oddsForNewPicks);

		//Over/Under Odds
		$ouOdds = new OuOdds($this->db);
		$futureOus = $ouOdds->getFutureOus();
		$this->f3->set('futureOus', $futureOus);

		$oddsForIncompleteOuPicks = array();

		$oupicks = new OuPicks($this->db);
		foreach ($futureOus as &$ouObject) {
			$existingOusForUser = $oupicks->getByOddsIdAndEmail($ouObject->id,$email);
			if(empty($existingOusForUser)){
				array_push($oddsForIncompleteOuPicks,$ouObject);
			}
		}
		$this->f3->set('oddsForIncompleteOuPicks', $oddsForIncompleteOuPicks);

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		//Variable for printing no incomplete picks
		if(empty($oddsForNewPicks) and empty($oddsForIncompleteOuPicks)){
			$this->f3->set('noIncompletePicks', 1);
		}

		$this->f3->set('view','incompletepicks.htm');
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

	function renderScoreboard(){
		$ouodds = new OuOdds($this->db);
		$seasonList=$ouodds->getSeasonList();
		$this->f3->set('seasonList',$seasonList);

		$odds = new Odds($this->db);
		$picks = new Picks($this->db);
		$oupicks = new OuPicks($this->db);
		$user = new User($this->db);

		if(empty($this->f3->get('POST.season'))){
			$season=$odds->getLatestSeason()[0]['season'];
			$week=$odds->getLatestWeekForSeason($season)[0]['week'];
		} else {
			$season=$this->f3->get('POST.season');
			$week=$this->f3->get('POST.week');
		}
		
		$users = $user->all();
		$this->f3->set('users', $users);

		$scores=array();
		$preseasonOus=array();

		//Preaseason picks
		if($week === "Preseason"){
			//var_dump($_POST);
			foreach ($users as &$userObject) {
				array_push($preseasonOus, $oupicks->getScoreboard($userObject->email,$season));
			}

			$this->f3->set('preseasonOus', $preseasonOus);
			//print_r(array_values($preseasonOus));
		} else {
		//Weekly picks
			$this->f3->set('weekly',1);
			foreach ($users as &$userObject) {
				array_push($scores, $picks->getScoreboard($userObject->email,$season,$week));
			}

			$this->f3->set('scores', $scores);
		}

		$this->f3->set('view','scoreboard.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}
	
	function renderStandings(){
		$picks = new Picks($this->db);
		$this->f3->set('standings',$picks->getStandings());
		$this->f3->set('view','standings.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}
}
