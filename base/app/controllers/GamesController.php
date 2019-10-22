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
		$resultSpread=$awayScore-$homeScore;
        $totalScore=$awayScore+$homeScore;
        $games = new Games($this->db);
		$games->getById($game_id);
		$away=$games->away;
		$home=$games->home;
		$league_week=$games->league_week;
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
			$spread_pick=$picksObject->spread_pick;
			$wager=$picksObject->wager;
			$ml_pick=$picksObject->money_pick;
            $ou_pick=$picksObject->ou_pick;

			//Push
			if($resultSpread == $spread){
				$pts_spread = 0;
                $result_spread ='T';
			//Home Team Covered Spread
			} else if ($resultSpread < $spread) {
				if($spread_pick === $home){
					$pts_spread = $wager;
                    $result_spread = 'W';
				} else {
					$pts_spread = 1-$wager;
                    $result_spread = 'L';
				}
			//Home Team did not cover spread
			} else {
				if($spread_pick === $home){
					$pts_spread = 1-$wager;
                    $result_spread = 'L';
				} else {
					$pts_spread = $wager;
                    $result_spread = 'W';
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
            //If no ou pick, no points, result incomplete
			if($ou_pick === ""){
				$pts_ou = 0;
                $result_ou = 'I';
            //If push, no points, result tie
			} else if($totalScore == $ou){
				$pts_ou = 0;
                $result_ou = 'T';
			} else {
                //Total score over OU
                if($totalScore > $ou){
                    if($ou_pick === "O"){
                        $result_ou = 'W';
                        $pts_ou = 1;
                    } else {
                        $pts_ou = -1;
                        $result_ou = 'L';
                    }
                //Total score under OU
                } else {
                    if($ou_pick === "O"){
                        $pts_ou = -1;
                        $result_ou = 'L';
                    } else {
                        $pts_ou = 1;
                        $result_ou = 'W';
                    }

                }
            }
			$picksObject->editPoints($picksObject->game_id,$picksObject->user_id,$pts_spread,$result_spread,$pts_ml,$pts_ou,$result_ou);
		}
        
		$suicidePicks = new SuicidePicks($this->db);
        $suicidePicksNeedingPoints=$suicidePicks->getByGameId($game_id);
        foreach ($suicidePicksNeedingPoints as &$suicidePicksObject) {
            //Ties are losses
            if($resultSpread === 0){
                $suicidePoints=0;
                $suicideCorrect=0;
                $suicideResult='L';
            } else {
                $suicidePick=$suicidePicksObject->suicide_pick;
                if($resultSpread < 0){//Home Team Won
                    if($suicidePick === $home){
                        $suicideCorrect=1;
                        $suicideResult='W';
                    } else {
                        $suicideCorrect=0;
                        $suicideResult='L';
                    }
                } else { //Away team won
                    if($suicidePick === $away){
                        $suicideCorrect=1;
                        $suicideResult='W';
                    } else {
                        $suicideCorrect=0;
                        $suicideResult='L';
                    }
                }

                if($suicideCorrect == 1){
                    if($league_week<3){
                        $suicidePoints=0;
                    }else if($league_week==3){
                        $suicidePoints=1;                            
                    }else if($league_week==4){
                        $suicidePoints=1;//total = 2                            
                    }else if($league_week==5){
                        $suicidePoints=2;//total = 4                            
                    }else if($league_week==6){
                        $suicidePoints=1;//total = 5                            
                    }else if($league_week==7){
                        $suicidePoints=2;//total = 7                            
                    }else if($league_week==8){
                        $suicidePoints=2;//total = 9                            
                    }else if($league_week==9){
                        $suicidePoints=1;//total = 10                            
                    }else if($league_week==10){
                        $suicidePoints=2;//total = 12                            
                    }else if($league_week==11){
                        $suicidePoints=1;//total = 13                            
                    }else if($league_week==12){
                        $suicidePoints=2;//total = 15                            
                    }else if($league_week==13){
                        $suicidePoints=1;//total = 16                            
                    }else if($league_week==14){
                        $suicidePoints=2;//total = 18                            
                    }else if($league_week==15){
                        $suicidePoints=2;//total = 20                            
                    }else if($league_week==16){
                        $suicidePoints=2;//total = 22                            
                    }else if($league_week==17){
                        $suicidePoints=3;//total = 25                            
                    }
                }
            }
            $suicidePicksObject->editPoints($suicidePicksObject->game_id,$suicidePicksObject->user_id,$suicideCorrect,$suicidePoints,$suicideResult);
        }
    }
}

