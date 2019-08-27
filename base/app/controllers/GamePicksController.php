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
                //Also check that the pick came in on time (kickoff - 5 minutes)
                if(strtotime($this->f3->get('POST.Kickoff')[$x]) > time()+(60*5)){
                    $this->extractDataFromPost($gamePicks,$x);
                    $gamePicks->save();
                }
            }
        }
        $this->renderViewPicks();
    }
    
    function updateGamePicks(){
        foreach(array_keys($this->f3->get('POST.spread_pick')) as &$x){
            //Check that the pick came in on time (kickoff - 5 minutes)
            if(strtotime($this->f3->get('POST.Kickoff')[$x]) > time()+(60*5)){
                $gamePicks = new GamePicks($this->db);
                $gamePicks->load(array('game_id=? AND user_id=?',$this->f3->get('POST.game_id')[$x],$this->f3->get('POST.user_id')[$x]));
                $this->extractDataFromPost($gamePicks,$x);
                $gamePicks->update();
            }
        }
        $this->renderViewPicks();
    }

    function renderLeaguePicks(){
        $gamePicks = new GamePicks($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $this->f3->set('league_year',$league_year);
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('league_week',$league_week);
        $this->f3->set('leaguePicks',$gamePicks->getLeaguePicksByLeagueYearAndWeek($league_year,$league_week));
        $this->f3->set('leaguePickTotals',$gamePicks->getLeaguePicksTotalsByLeagueYearAndWeek($league_year,$league_week));
        //Constants derived from getLeaguePicksByLeagueYearAndWeek in GamePicks.php
        $numHeaderColumnsInQuery=11; //Matchup,Result,away_id,away,home_id,home,Kickoff,Favorite,PointSpread,MoneyLine,OverUnder
        $spreadPickIdx=0;
        $wagerIdx=1;
        $moneyPickIdx=2;
        $ouPickIdx=3;
        $spreadPointsColumnIdx=4;
        $moneyPointsColumnIdx=5;
        $ouPointsColumnIdx=6;
        //Wager value decoder
        $lockWagerValue='2';
        $ironWagerValue='3';
        $this->f3->set('numHeaderColumnsInQuery',$numHeaderColumnsInQuery);
        $this->f3->set('spreadPickIdx',$spreadPickIdx);
        $this->f3->set('wagerIdx',$wagerIdx);
        $this->f3->set('moneyPickIdx',$moneyPickIdx);
        $this->f3->set('ouPickIdx',$ouPickIdx);
        $this->f3->set('spreadPointsColumnIdx',$spreadPointsColumnIdx);
        $this->f3->set('moneyPointsColumnIdx',$moneyPointsColumnIdx);
        $this->f3->set('ouPointsColumnIdx',$ouPointsColumnIdx);
        $this->f3->set('lockWagerValue',$lockWagerValue);
        $this->f3->set('ironWagerValue',$ironWagerValue);
        $this->f3->set('pageName','League Picks for '. $league_year .' Week '.$league_week);
		$this->f3->set('view','leaguepicksgames.htm');	
    }

}

