--displays a weeks' games and lines
SELECT
  kickoff_time AS Kickoff,
  CONCAT(
  ta.team, ' ', '@', ' ',
  th.team) AS Matchup,
  s.stadium_name AS Stadium,
  network AS Network,
  ft.team AS Favorite,
  point_spread AS "Point Spread",
  money_line AS "Money Line",
  ou AS "Over Under"

FROM games
LEFT JOIN teams AS ta
  ON away = ta.id
LEFT JOIN teams AS th
  ON home = th.id
LEFT JOIN teams AS ft
  ON favorite = ft.id
LEFT JOIN stadiums AS s
  ON stadium = s.id

WHERE league_year = 2018
AND league_week = 1;


-- builds results for game picks a given player
SELECT
  kickoff_time AS Kickoff,
  CONCAT(
  ta.team, ' ', '@', ' ',
  th.team) AS Matchup,
  s.stadium_name AS Stadium,
  network AS Network,
  ft.team AS Favorite,
  point_spread AS "Point Spread",
  money_line AS "Money Line",
  ou AS "Over Under",
  p.spread_pick AS "ATS Pick",
  p.is_lock AS "Lock?",
  pm.money_pick AS "Money Line",
  p.ou_pick AS "Over/Under Pick",
  ps.suicide_pick AS "Suicide Team?"
  

FROM games AS g
LEFT JOIN teams AS ta
  ON away = ta.id
LEFT JOIN teams AS th
  ON home = th.id
LEFT JOIN teams AS ft
  ON favorite = ft.id
LEFT JOIN stadiums AS s
  ON stadium = s.id
LEFT JOIN game_picks AS p 
ON g.id = p.game_id
LEFT JOIN game_picks AS pm
ON g.id = pm.game_id
LEFT JOIN game_picks AS ps
ON g.id = ps.game_id
LEFT JOIN users 
ON p.user_id = users.id


WHERE league_year = 2018
AND league_week = 1
AND users.id = 1;



