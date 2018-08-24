<?php
class GamePicksController extends UserController {

    function extractDataFromPost($gamePicks,$x){
        $gamePicks->user_id = $this->f3->get('POST.user_id')[$x];
        $gamePicks->game_id = $this->f3->get('POST.game_id')[$x];
        $gamePicks->spread_pick = $this->f3->get('POST.spread_pick')[$x];
        $gamePicks->wager = $this->f3->get('POST.wager')[$x];
        $gamePicks->money_pick = $this->f3->get('POST.money_pick')[$x];
        $gamePicks->ou_pick = $this->f3->get('POST.ou_pick')[$x];
    }

	function renderViewPicks(){
        $gamePicks = new GamePicks($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('league_week',$league_week);
        $this->f3->set('incompletePicks',$gamePicks->getNullPicksByIdAndLeagueYearWeek($this->f3->get('SESSION.user'),$league_year,$league_week));
        $this->f3->set('submittedPicks',$gamePicks->getNotNullPicksByIdAndLeagueYearWeek($this->f3->get('SESSION.user'),$league_year,$league_week));
        $this->f3->set('pageName',$league_year. ' Week '. $league_week .'  Game Picks');
        $this->f3->set('view','gamepicks.htm');	
	}

    function addGamePicks(){
        foreach(array_keys($this->f3->get('POST.spread_pick')) as &$x){
            $gamePicks = new GamePicks($this->db);
            //Make sure this pick hasn't been made before
            $gamePicks->load(array('game_id=? AND user_id=?',$this->f3->get('POST.game_id')[$x],$this->f3->get('POST.user_id')[$x]));
            if($gamePicks->dry()){
                $this->extractDataFromPost($gamePicks,$x);
            }
            $gamePicks->save();
        }
        $this->renderViewPicks();
    }
    
    function updateGamePicks(){
        foreach(array_keys($this->f3->get('POST.spread_pick')) as &$x){
            $gamePicks = new GamePicks($this->db);
            $gamePicks->load(array('game_id=? AND user_id=?',$this->f3->get('POST.game_id')[$x],$this->f3->get('POST.user_id')[$x]));
            $this->extractDataFromPost($gamePicks,$x);
            $gamePicks->update();
        }
        $this->renderViewPicks();
    }

}

