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
		$this->f3->set('timeNow', date("Y-m-d") . "T00:00:00");

        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addOdds(){
		//var_dump($_POST);
		$odds = new Odds($this->db);
		$odds->season = $this->f3->get('POST.season');
		$odds->week = $this->f3->get('POST.week');
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

	function logResults(){
		$odds = new Odds($this->db);
		
		$this->f3->set('oddsNeedingResults', $odds->getPastOddsWithoutResults());
		$this->f3->set('recentResults', $odds->getRecentOddsWithResults());

		$this->f3->set('view','results.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addResults(){
		//var_dump($_POST);

		$numberOfRows=count($this->f3->get('POST.id'));
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			$odds = new Odds($this->db);
			$awayScore=$this->f3->get('POST.awayScore')[$x];
			$homeScore=$this->f3->get('POST.homeScore')[$x];
    			$odds->addScores($id,$awayScore,$homeScore);
			$this->addPointsToPicks($id,$awayScore,$homeScore);
		}
		$this->f3->reroute('/logresults');

	}

	function updateResults(){
		$this->f3->set('view','updateresults.htm');
		$odds = new Odds($this->db);
		$this->f3->set('resultsToUpdate', $odds->getRecentOddsWithResults());

        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function writeUpdatedResults(){
		//var_dump($_POST);

		$numberOfRows=count($this->f3->get('POST.id'));
		$odds = new Odds($this->db);
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			if(!empty($this->f3->get('POST.del')[$x])){
				$odds->addScores($id,NULL,NULL);
			} else {
				$awayScore=$this->f3->get('POST.awayScore')[$x];
				$homeScore=$this->f3->get('POST.homeScore')[$x];
    				$odds->editSpreadAndML($id,$awayScore,$homeScore);
			}
			$this->addPointsToPicks($id,$awayScore,$homeScore);
		}
		$this->f3->reroute('/logresults');
	}

	function addPointsToPicks($odds_id,$awayScore,$homeScore){
		$odds = new Odds($this->db);
		$odds->getById($odds_id);
		$away=$odds->away;
		$home=$odds->home;
		$spread=$odds->spread;
		$moneyline=$odds->moneyline;

		$picks = new Picks($this->db);
		$picksNeedingPoints=$picks->getByOddsId($odds_id);

		foreach ($picksNeedingPoints as &$picksObject) {
			$resultSpread=$awayScore-$homeScore;
			$spread_pick=$picksObject->spread_pick;
			$lock=$picksObject->lock;
			$ml_pick=$picksObject->ml_pick;
			//Push
			if($resultSpread === $spread){
				$pts_spread = 0;
			//Home Team Covered Spread
			} else if ($resultSpread < $spread) {
				if($spread_pick === $home){
					$pts_spread = 1 + $lock;
				} else {
					$pts_spread = -$lock;
				}
			//Home Team did not cover spread
			} else {
				if($spread_pick === $home){
					$pts_spread = -$lock;
				} else {
					$pts_spread = 1 + $lock;
				}
			}
			//If no moneyline pick or tie, no points
			if($ml_pick === "" or $resultSpread === 0){
				$pts_ml = 0;
			} else {
				//Home Team Won
				if($resultSpread < 0){
					//Home team favored or pickem
					if($spread <= 0) {
						if($ml_pick === $home){
							$pts_ml = 1;
						} else {
							$pts_ml = -1;
						}
					//Away team favored
					} else {
						if($ml_pick === $home){
							$pts_ml = $moneyline;
						} else {
							$pts_ml = -$moneyline;
						}
					}	
				//Away Team Won
				} else {
					//Home team favored or pickem
					if($spread <= 0) {
						if($ml_pick === $home){
							$pts_ml = -$moneyline;
						} else {
							$pts_ml = $moneyline;
						}
					//Away team favored
					} else {
						if($ml_pick === $home){
							$pts_ml = -1;
						} else {
							$pts_ml = 1;
						}
					}	
				}
			}
			$picksObject->editSpreadAndMLPoints($picksObject->id,$pts_spread,$pts_ml);
		}
	}
}
