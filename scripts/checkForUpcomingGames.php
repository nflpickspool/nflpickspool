<?php
//Script to check if there are games today and write them to games table
require('include/simple_html_dom.php');
include('include/dbCreds.php');

//Hopefully, pro-football-reference remains pretty stable
$games_html = file_get_html('https://www.pro-football-reference.com/years/2017/games.htm');
//$games_html = file_get_html('https://www.pro-football-reference.com/years/'.date("Y").'/games.htm');  //Date doesn't work for playoffs
//$games_html = file_get_html('games.html');//Week 17 testing

$lines_html = file_get_html('https://www.sportsbook.ag/sbk/sportsbook4/nfl-betting/game-lines.sbk');
//$lines_html = file_get_html('lines.html');//Week 17 testing

if ($games_html->find('td[csk="' . date("Y-m-d") . '"]')){
//if ($games_html->find('td[csk="' . date("2017-12-31") . '"]')){//Week 17 testing
  $connect = mysqli_connect($host_name, $user_name, $password, $database);
  // Check connection
  if ($connect->connect_error) {
      die("Connection failed: " . $connect->connect_error);
  }
  // email message
  $message = '
  <html>
  <head>
    <title>New Lines Posted</title>
  </head>
  <body>
    <p>Games and Odds for Today - Go to nflpickspool.com to make your selections!</p>
    <table>
      <tr>
        <th>Away</th><th>Home</th><th>Spread</th><th>ML</th>
      </tr>';
  //game-date was a seemingly logical choice of a attribute to look for
  foreach($games_html->find('td[data-stat="game_date"]') as $element) {
    if ($element->csk==date("Y-m-d")){ //Check if there are games today
    //if ($element->csk==date("2017-12-31")){ //Week 17 testing
        $date = DateTime::createFromFormat('Y-m-d g:iA', $element->getAttribute(csk) . ' ' . $element->parent()->children(3)->plaintext);
      	$away = $element->parent()->children(4)->plaintext;
        $home = $element->parent()->children(6)->plaintext;
        $games_sql = 
          "INSERT INTO `Games` (`id`, `week`, `day`, `date`, `away`, `home`, `awayScore`, `homeScore`) 
	  VALUES (NULL,
	  '". $element->parent()->children(0)->plaintext . "', 
	  '". $element->parent()->children(1)->plaintext . "', 
	  STR_TO_DATE('" . $date->format('Y-m-d H:i:s') . "','%Y-%m-%d %H:%i:%s'),
	  '". $away . "',
	  '". $home . "',
	  NULL,NULL)";
      if ($connect->query($games_sql) === TRUE) {
        $last_id = $connect->insert_id;
        //echo "New record created successfully. Last inserted ID is: " . $last_id;
      } else {
        echo "Error: " . $games_sql . "<br>" . $conn->error;
      }
      //Defaults
      $home_spread = "0";
      $home_ml = "+100";
      foreach($lines_html->find('span[id="firstTeamName"]') as $element) {
        if(strpos($element->plaintext, $away) !== false) {
          $away_odds = $element->parent()->parent()->parent()->parent()->find('div[class="market"]');
          $away_ou = explode(' ',trim(preg_replace('/\s+/', ' ', $away_odds[0]->plaintext)))[1]; //odds are at index 2
          $away_spread = explode(' ',trim(preg_replace('/\s+/', ' ', $away_odds[1]->plaintext)))[0];//odds are at index 1
          $away_ml = $away_odds[2]->plaintext;
          //echo $away . " : " . $away_ou . " " . $away_spread . " " . $away_ml . "\n";
          $home_odds = $element->parent()->parent()->parent()->parent()->next_sibling()->find('div[class="market"]');
          $home_ou = explode(' ',trim(preg_replace('/\s+/', ' ', $home_odds[0]->plaintext)))[1]; //odds are at index 2
          $home_spread = explode(' ',trim(preg_replace('/\s+/', ' ', $home_odds[1]->plaintext)))[0]; //odds are at index 1
          $home_ml = $home_odds[2]->plaintext;
          //echo $home . " : " . $home_ou . " " . $home_spread . " " . $home_ml . "\n";
          //echo "\n";
          //TODO: Compute ML and spread based on odds
          $spread_sql = 
            "INSERT INTO `Odds` (`id`, `type`, `parentid`, `date`, `value`, `result`) 
	    VALUES (NULL,
            'PS',
	    '". $last_id . "', 
	    STR_TO_DATE('" . $date->format('Y-m-d H:i:s') . "','%Y-%m-%d %H:%i:%s'),
	    '". $home_spread . "', 
	    NULL)";
          $ml_sql = 
            "INSERT INTO `Odds` (`id`, `type`, `parentid`, `date`, `value`, `result`) 
	    VALUES (NULL,
            'PML',
	    '". $last_id . "', 
	    STR_TO_DATE('" . $date->format('Y-m-d H:i:s') . "','%Y-%m-%d %H:%i:%s'),
	    '". $home_ml . "', 
	    NULL)";
            if ($connect->query($spread_sql) === TRUE) {
              $last_id = $connect->insert_id;
              //echo "New record created successfully. Last inserted ID is: " . $last_id;
            } else {
              echo "Error: " . $spread_sql . "<br>" . $conn->error;
            }
            if ($connect->query($ml_sql) === TRUE) {
              $last_id = $connect->insert_id;
              //echo "New record created successfully. Last inserted ID is: " . $last_id;
            } else {
              echo "Error: " . $ml_sql . "<br>" . $conn->error;
            }
            //Finish email message
            $message = $message .
                     '<tr>
                        <td>' . $away . '</td><td>' . $home . '</td><td>' . $home_spread . '</td><td>' . $home_ml . '</td>
                      </tr>';
        }
      }
    }
  }
  $connect->close();
  $message = $message . 
             '    </table>
                </body>
              </html>';

  //email metadata
  $to  = 'brandoandpat@gmail.com';
  // subject
  $subject = 'NFL Picks Pool: New lines available';
  // To send HTML mail, the Content-type header must be set
  $headers  = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  // Additional headers
  $headers .= 'From: Kingmaker' . "\r\n";
  // Mail it
  mail($to, $subject, $message, $headers);
}
?>
