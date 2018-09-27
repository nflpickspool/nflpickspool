<?php
class GamesController extends UserController {

	function afterroute() {
		$template=new Template;
        echo $template->render('homeLayout.htm');
	}
	
	function renderViewLines(){
        $games = new Games($this->db);
        $league_year = $this->f3->get('POST.league_year');
        $league_week = $this->f3->get('POST.league_week');
        $this->f3->set('season',$league_year);
        $this->f3->set('week',$league_week);
        $this->f3->set('pageName',$league_year .' Week '. $league_week .' Game Lines');
        $this->f3->set('games',$games->getByLeagueYearAndWeek($league_year,$league_week));
		$this->f3->set('view','gamelines.htm');	
	}

    function addGame(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}

        $games = new Games($this->db);
        $games->add();
        $this->f3->reroute('/addgames');
    }

    function updateGame(){
        if($this->f3->get('SESSION.user') > 2){
			$this->f3->reroute('/home');
			exit;
		}

        foreach(array_keys($this->f3->get('POST.id')) as &$x){
            $games = new Games($this->db);
            $games->load(array('id=?',$this->f3->get('POST.id')[$x]));
            $games->league_year = $this->f3->get('POST.league_year')[$x];
            $games->league_week = $this->f3->get('POST.league_week')[$x];
            $games->kickoff_time = $this->f3->get('POST.kickoff_time')[$x];
            $games->network = $this->f3->get('POST.network')[$x];
            $games->away = $this->f3->get('POST.away')[$x];
            $games->home = $this->f3->get('POST.home')[$x];
            $games->favorite = $this->f3->get('POST.favorite')[$x];
            $games->point_spread = $this->f3->get('POST.point_spread')[$x];
            $games->money_line = $this->f3->get('POST.money_line')[$x];
            $games->ou = $this->f3->get('POST.ou')[$x];
            $games->update();
        }

        $this->f3->reroute('/addgames');
    }


    function addGameScores(){
        //$this->f3->set('view','post.htm');
        foreach(array_keys($this->f3->get('POST.game_id')) as &$x){
            $game_id = $this->f3->get('POST.game_id')[$x];
            $away_score = $this->f3->get('POST.away_score')[$x];
            $home_score = $this->f3->get('POST.home_score')[$x];
            $games = new Games($this->db);
            $games->load(array('id=?',$game_id));
            $games->away_score = $away_score;
            $games->home_score = $home_score;
            $games->update();
            $gamePicks = new GamePicks($this->db);
            $this->addPointsToPicks($game_id,$away_score,$home_score);
        }
        $this->f3->reroute('/enterresults');
    }
    
    function addPointsToPicks($game_id,$awayScore,$homeScore){
		$games = new Games($this->db);
		$games->getById($game_id);
		$away=$games->away;
		$home=$games->home;
        //Logic below assumes point spread relative to home team
        //so invert if favorite is away
        if($games->favorite === $home){
            $spread=-$games->point_spread;
        } else {
            $spread=$games->point_spread;
        }
		$moneyline=$games->money_line;
        $ou=$games->ou;

		$gamePicks = new GamePicks($this->db);
		$picksNeedingPoints=$gamePicks->getByGameId($game_id);

        foreach ($picksNeedingPoints as &$picksObject) {
			$resultSpread=$awayScore-$homeScore;
            $totalScore=$awayScore+$homeScore;
			$spread_pick=$picksObject->spread_pick;
			$wager=$picksObject->wager;
			$ml_pick=$picksObject->money_pick;
            $ou_pick=$picksObject->ou_pick;

			//Push
			if($resultSpread === $spread){
				$pts_spread = 0;
			//Home Team Covered Spread
			} else if ($resultSpread < $spread) {
				if($spread_pick === $home){
					$pts_spread = $wager;
				} else {
					$pts_spread = 1-$wager;
				}
			//Home Team did not cover spread
			} else {
				if($spread_pick === $home){
					$pts_spread = 1-$wager;
				} else {
					$pts_spread = $wager;
				}
			}
			//If no moneyline pick or tie, no points
			if($ml_pick === 0 or $resultSpread === 0){
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
            //If no moneyline pick or tie, no points
			if($ou_pick === "" or $totalScore === $ou){
				$pts_ou = 0;
			} else {
                //Total score over OU
                if($totalScore > $ou){
                    if($ou_pick === "O"){
                        $pts_ou = 1;
                    } else {
                        $pts_ou = -1;
                    }
                //Total score under OU
                } else {
                    if($ou_pick === "O"){
                        $pts_ou = -1;
                    } else {
                        $pts_ou = 1;
                    }

                }
            }
			$picksObject->editPoints($picksObject->game_id,$picksObject->user_id,$pts_spread,$pts_ml,$pts_ou);
		}
    }
}

