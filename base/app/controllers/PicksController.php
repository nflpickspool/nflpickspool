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
}
