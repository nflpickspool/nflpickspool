<?php
include_once('include/simple_html_dom.php');
include_once('include/dbCreds.php');
include_once('include/slackInfo.php');

function get_id_from_region_team($region_team, $db_connection){
    $sql = "SELECT id, concat(region, ' ', team) as t FROM teams having t = '" . $region_team . "';";
    if ($id = $db_connection->query($sql)) {
        return $id->fetch_assoc()['id'];
    }
}

function postToSlack($message_text, $slack_webhook_url){
    $data = array("text" => $message_text);
    $json_string = json_encode($data);

    $slack_call = curl_init($slack_webhook_url);
    curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
    curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json"));

    $result = curl_exec($slack_call);
    curl_close($slack_call);
}

$games_html =
    file_get_html("https://www.pro-football-reference.com/years/".date("Y")."/games.htm");
$lines_html =
    file_get_html('https://www.sportsbook.ag/sbk/sportsbook4/nfl-betting/game-lines.sbk');

$date_to_check = (new DateTime('today'))->modify('+3 days');
    
if ($games_html->find('td[csk="' . $date_to_check->format('Y-m-d') . '"]')){
    $message_text = "New Lines Posted!\n";
    $connect = mysqli_connect($host_name, $user_name, $password, $database);
    // Check connection
    if ($connect->connect_error) {
        die("Connection failed: " . $connect->connect_error);
    }
    //Search every game (td[data-stat="game_date"])
    foreach($games_html->find('td[data-stat="game_date"]') as $element) {
        //Check if date of game matches date to compare
        if ($element->csk == $date_to_check->format('Y-m-d')){
            // Extract kickoff time, year and teams
            $kickoff_time = DateTime::createFromFormat('Y-m-d g:iA',
                                                       $element->getAttribute('csk') . ' ' .
                                                       $element->parent()->children(3)->plaintext)->format('Y-m-d H:i:s');
            $email_time = DateTime::createFromFormat('Y-m-d g:iA',
                                                       $element->getAttribute('csk') . ' ' .
                                                       $element->parent()->children(3)->plaintext)->format('D m/d g:i A');
            $league_year = DateTime::createFromFormat('Y-m-d g:iA',
                                                       $element->getAttribute('csk') . ' ' .
                                                       $element->parent()->children(3)->plaintext)->format('Y');
            $league_week = $element->parent()->children(0)->plaintext;
            $away_region_team = $element->parent()->children(4)->plaintext;
            $home_region_team = $element->parent()->children(6)->plaintext;
            // Convert team names to team ids
            $away = get_id_from_region_team($away_region_team, $connect);
            $home = get_id_from_region_team($home_region_team, $connect);

            // Using extracted team names, compare to Sportsbook
            // Default values (if no line is detected)
            $point_spread = NULL;
            $money_line = NULL;
            $ou = NULL;
            foreach($lines_html->find('span[id="firstTeamName"]') as $element) {
                if ($away_region_team == "Los Angeles Chargers"){
                    $away_region_team = "LA Chargers";
                }
                if(strpos($element->plaintext, $away_region_team) !== false) {
                    $away_odds = $element->parent()->parent()->parent()->parent()->find('div[class="market"]');
                    $away_ou = explode(' ',trim(preg_replace('/\s+/', ' ', $away_odds[0]->plaintext)))[1]; //odds are at index 2
                    $away_spread = explode(' ',trim(preg_replace('/\s+/', ' ', $away_odds[1]->plaintext)))[0];//odds are at index 1
                    $away_ml = $away_odds[2]->plaintext;
                    //echo $away_odds . " " . $away_ou . " " . $away_spread . " " . $away_ml . "\n";
                    $home_odds = $element->parent()->parent()->parent()->parent()->next_sibling()->find('div[class="market"]');
                    $home_ou = explode(' ',trim(preg_replace('/\s+/', ' ', $home_odds[0]->plaintext)))[1]; //odds are at index 2
                    $home_spread = explode(' ',trim(preg_replace('/\s+/', ' ', $home_odds[1]->plaintext)))[0]; //odds are at index 1
                    $home_ml = $home_odds[2]->plaintext;
                    //echo $home_odds . " " . $home_ou . " " . $home_spread . " " . $home_ml . "\n";
                    // Favorite logic - if number is positive, underdog
                    $favorite = $home_spread >= $away_spread ? $away : $home;
                    $favorite_region_team = $favorite == $home ? $home_region_team : $away_region_team;
                    $point_spread = abs($home_spread);
                    $money_line = round((abs($away_ml) + abs($home_ml)) / 200, 1, PHP_ROUND_HALF_UP);
                    $ou = abs($home_ou);
                    //echo $point_spread . "\n";
                    //echo $money_line . "\n";
                    //echo $ou . "\n";
                }
            }

            $games_sql = "INSERT INTO games (kickoff_time, league_year, league_week, away, home, favorite, point_spread, money_line, ou) "
                       . "VALUES ('" . $kickoff_time . "', '" . $league_year . "', '" . $league_week . "', '" . $away . "', '" .
                       $home . "', '" . $favorite . "', '" . $point_spread . "', '" . $money_line . "', '" . $ou ."'); ";
            //echo $games_sql;
            if ($connect->query($games_sql) === TRUE) {
                $message_text = $message_text .
                         "* "  . $email_time . ": " . $away_region_team . " @ " . $home_region_team .
                              " Line: " . $favorite_region_team . " -" . $point_spread . " ML: " . $money_line .
                              " OU: " . $ou . "\n";
            } else {
                postToSlack("Something went wrong when inserting games automatically: game didn't insert\n".
                            $games_sql . "\n" . $away_region_team ."\n" . $home_region_team , $admin_url);
            }
        }
    }
    postToSlack($message_text, $sportsbook_url);
}

// clean up memory
$games_html->clear();
unset($games_html);
$lines_html->clear();
unset($lines_html);

?>
