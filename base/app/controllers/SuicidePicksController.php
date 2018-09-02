<?php
class SuicidePicksController extends UserController {

    function extractDataFromPost($suicidePicks){
        $suicidePicks->league_year = $this->f3->get('POST.league_year');
        $suicidePicks->league_week = $this->f3->get('POST.league_week');
        $suicidePicks->user_id = $this->f3->get('POST.user_id');
        $suicidePicks->game_id = explode('|',$this->f3->get('POST.pick'))[2];
        $suicidePicks->suicide_pick = explode('|',$this->f3->get('POST.pick'))[0];
    }
	
	function renderViewPicks(){
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('league_week',$league_week);        
        $user_id=$this->f3->get('SESSION.user');
        $this->f3->set('user_id',$user_id);
        
        $suicidePicks = new SuicidePicks($this->db);
        $this->f3->set('previousPicks',$suicidePicks->getPreviousSuicidePicks($user_id,$league_year,$league_week));
        //Check for existing pick for given week
        $existingPick = $suicidePicks->getPickByIdYearAndWeek($user_id,$league_year,$league_week);
        //Check if existing pick is closed
        if(!empty($existingPick)){
            $this->f3->set('existingPick',$existingPick[0]['suicide_pick']);
            if(strtotime($existingPick[0]['kickoff_time']) < time()+(60-5)){
                $closed = 1;
                $this->f3->set('closed',$closed);
            }
        }
        
        //If not closed
        if(!$closed){
            $alive=$suicidePicks->checkIfAlive($user_id,$league_year);
            //Only get list of available teams if still alive
            if(!empty($alive) and $alive[0]['alive']==='0'){
                $this->f3->set('alive',0);
            } else {
                $this->f3->set('availableTeams',$suicidePicks->getAvailableTeamsForWeek($user_id,$league_year,$league_week));
            }
        }
  
        $this->f3->set('season',$league_year);
        $this->f3->set('pageName',$league_year .'  Suicide Picks');
        $this->f3->set('view','suicidepicks.htm');	
	}

    function addSuicidePicks(){
        $suicidePicks = new SuicidePicks($this->db);
            //Make sure this pick hasn't been made before
        $suicidePicks->load(array('league_year=? AND league_week=? AND user_id=?',$this->f3->get('POST.league_year'),$this->f3->get('POST.league_week'),$this->f3->get('POST.user_id')));
        if($suicidePicks->dry()){
            //Also check that the pick came in on time (kickoff - 5 minutes)
            if(strtotime(explode('|',$this->f3->get('POST.pick'))[1]) > time()+(60*5)){
                $this->extractDataFromPost($suicidePicks);
                $suicidePicks->save();
            }
        }
        $this->renderViewPicks();
    }

    function updateSuicidePicks(){
        //$this->f3->set('view','post.htm');
        //Check that the pick came in on time (kickoff - 5 minutes)
        if(strtotime(explode('|',$this->f3->get('POST.pick'))[1]) > time()+(60*5)){
            $suicidePicks = new SuicidePicks($this->db);
            $suicidePicks->load(array('league_year=? AND league_week=? AND user_id=?',$this->f3->get('POST.league_year'),$this->f3->get('POST.league_week'),$this->f3->get('POST.user_id')));
            $this->extractDataFromPost($suicidePicks);
            $suicidePicks->update();
        }
        $this->renderViewPicks();
    }
}

