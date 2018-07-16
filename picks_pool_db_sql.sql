CREATE TABLE players (
	player_id INT, 
	f_name VARCHAR(25), 
	l_name VARCHAR(35),
	handle VARCHAR(30),
	email VARCHAR(75),
	date_of_birth DATE, 
	city VARCHAR(50),
	state VARCHAR(2),
	ZIP VARCHAR(5)
	PRIMARY KEY (player_id)
	);
	
	
CREATE TABLE teams (
	team_id  INT, 
	region_name VARCHAR (40),
	team_name VARCHAR(30),
	PRIMARY KEY (team_id)
	);
	

CREATE TABLE division (
	division_id INT,
	division_region VARCHAR(7),
	division_conference VARCHAR(3),
	PRIMARY KEY (division_id)
	);
	
CREATE TABLE stadium (
	stadium_id  INT, 
	stadium_name VARCHAR (50), 
	stadium_zip VARCHAR(5),
	PRIMARY KEY (stadium_id)
	);

CREATE TABLE time (
	time_id  INT AUTO_INCREMENT, 
	league_day INT, 
	league_week INT, 
	year INT, 
	league_year INT,
	PRIMARY KEY (time_id)
	);
	



	
CREATE TABLE games (
	game_id INT AUTO_INCREMENT, 
	away_id INT,
	home_id INT,
	stadium_id INT,
	league_year INT,
	FOREIGN KEY (away_id) REFERENCES teams(team_id), 
	FOREIGN KEY (home_id) REFERENCES teams(team_id), 
	FOREIGN KEY (stadium_id) REFERENCES stadium(stadium_id),
	FOREIGN KEY (league_year) REFERENCES time(league_year),
	FOREIGN KEY (league_week) REFERENCES time(league_week),
	game_start_time VARCHAR (5), 
	game_network VARCHAR(10),
	away_score INT, 
	home_score INT, 
	is_ot INT, 
	PRIMARY KEY (game_id)
	);
	
CREATE TABLE picks (
	picks_id INT AUTO_INCREMENT,
	favorite INT,
	underdog INT,
	game_id INT,
	FOREIGN KEY (favorite) REFERENCES teams(team_id), 
	FOREIGN KEY (underdog) REFERENCES teams(team_id),
	FOREIGN KEY (game_id) REFERENCES games(game_id),
	point_spread DEC(2,1), 
	money_line DEC(2,1),
	PRIMARY KEY (picks_id)
	);
	
	
	
	