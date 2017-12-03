<?php
//Script to check if there are games today and write them to games table
require('include/simple_html_dom.php');
include('include/dbCreds.php');

//Hopefully, pro-football-reference remains pretty stable
$games_html = file_get_html('https://www.pro-football-reference.com/years/'.date("Y").'/games.htm');

//game-date was a seemingly logical choice of a attribute to look for
foreach($games_html->	find('td[data-stat="game_date"]') as $element) {
  if ($element->csk==date("Y-m-d")){ //Check if there are games today
    $connect = mysqli_connect($host_name, $user_name, $password, $database);
    $date = DateTime::createFromFormat('Y-m-d g:iA', $element->getAttribute(csk) . ' ' . $element->parent()->children(3)->plaintext);
    mysqli_query($connect, 
       "INSERT INTO `Games` (`id`, `week`, `day`, `date`, `away`, `home`, `awayScore`, `homeScore`) 
	VALUES (NULL,
	'". $element->parent()->children(0)->plaintext . "', 
	'". $element->parent()->children(1)->plaintext . "', 
	STR_TO_DATE('" . $date->format('Y-m-d H:i:s') . "','%Y-%m-%d %H:%i:%s'),
	'". $element->parent()->children(4)->plaintext . "',
	'". $element->parent()->children(6)->plaintext . "',
	NULL,NULL)");
  }
}

//$lines_html = file_get_html('https://www.sportsbook.ag/sbk/sportsbook4/nfl-betting/game-lines.sbk');
//foreach($lines_html->	find('span[id="firstTeamName"]') as $element) 
  //if ($element->csk==date("2017-12-04"))
    //echo $element->plaintext.'<br>';
?>	
