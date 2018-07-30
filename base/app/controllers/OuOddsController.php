<?php
class OuOddsController extends Controller {

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
		$this->f3->set('timeNow', date("Y-m-d") ."T00:00:00");

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$ouOdds = new OuOdds($this->db);
		$seasonList=$ouOdds->getSeasonList();
		$this->f3->set('seasonList',$seasonList);

		if(empty($this->f3->get('POST.season'))){
			$this->f3->set('season',date("Y"));
			$existingOuOdds=$ouOdds->getBySeason(date("Y"));
		} else {
			$this->f3->set('season',$this->f3->get('POST.season'));
			$existingOuOdds=$ouOdds->getBySeason($this->f3->get('POST.season'));
		}
		$this->f3->set('existingOuOdds',$existingOuOdds);

		$this->f3->set('view','ouodds.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addOuOdds(){
		//var_dump($_POST);
		$numberOfRows=count($this->f3->get('POST.ou'));
		for ($x = 0; $x < $numberOfRows; $x++) {
			$ouOdds = new OuOdds($this->db);
			$ouOdds->season=explode("-",$this->f3->get('POST.date'))[0];
			$ouOdds->team_id=$this->f3->get('POST.team_id')[$x];
			$ouOdds->ou=$this->f3->get('POST.ou')[$x];
			$ouOdds->date=$this->f3->get('POST.date');
			$ouOdds->save();
		}
		$this->f3->reroute('/setous');
	}

	function updateOus(){
		$this->f3->set('timeNow', date("Y-m-d") ."T00:00:00");

		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$ouOdds = new OuOdds($this->db);
		$this->f3->set('existingOuOdds', $ouOdds->getBySeason(date("Y")));

		$this->f3->set('view','updateouodds.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function writeUpdatedOuOdds(){
		var_dump($_POST);
		$date=$this->f3->get('POST.date');
		$numberOfRows=count($this->f3->get('POST.ou'));
		$ouOdds = new OuOdds($this->db);
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			$ou=$this->f3->get('POST.ou')[$x];
			$ouOdds->editOu($id,$ou,$date);
		}
		$this->f3->reroute('/setous');
	}

	function logResults(){
		//Should consider putting this in beforeroute
		$teams = new Teams($this->db);
		$this->f3->set('teams',$teams->all());

		$ouOdds = new OuOdds($this->db);
		$this->f3->set('season',$ouOdds->getLatestSeason()[0]['season']);
		$this->f3->set('ousNeedingResults', $ouOdds->getPastOusWithoutResults());
		$this->f3->set('recentOuResults', $ouOdds->getRecentOusWithResults());

		$this->f3->set('view','seasonresults.htm');
        	$template=new Template;
        	echo $template->render('layout.htm');
	}

	function addResults(){
		//var_dump($_POST);

		$numberOfRows=count($this->f3->get('POST.id'));
		for ($x = 0; $x < $numberOfRows; $x++) {
			$id = $this->f3->get('POST.id')[$x];
			$ouodds = new OuOdds($this->db);
			$result=$this->f3->get('POST.result')[$x];
			$ouodds->addResults($id,$result);
			$this->addPointsToOus($id,$result);
		}
		$this->f3->reroute('/logseasonresults');

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

	function addPointsToOus($odds_id,$result){
		$ouodds = new OuOdds($this->db);
		$ouodds->getById($odds_id);
		$ou=$ouodds->ou;
		$result=$ouodds->result;

		$ouPicks = new OuPicks($this->db);
		$ouPicksNeedingPoints=$ouPicks->getByOddsId($odds_id);

		foreach ($ouPicksNeedingPoints as &$picksObject) {
			$pick=$picksObject->pick;
			$lock=$picksObject->lock;
			//Push
			if($result === $ou){
				$pts = 0;
			//Under
			} else if ($result < $ou) {
				if($pick === "U"){
					$pts = 2 + $lock*2;
				} else {
					$pts = -$lock*2;
				}
			//Over
			} else {
				if($pick === "U"){
					$pts = -$lock*2;
				} else {
					$pts = 2 + $lock*2;
				}
			}
			$picksObject->editPoints($picksObject->id,$pts);
		}
	}
}
