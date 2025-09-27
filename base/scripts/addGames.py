#!/usr/bin/env python3

"""
Check for games and add/update the lines as needed
"""

import argparse
import logging
import datetime
import requests
from bs4 import BeautifulSoup
import json
import math
from slack_sdk.webhook import WebhookClient

from dbCreds import mydb, API_KEY, sportsbook_url, admin_url

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def get_id_from_region_team(mydb, region_team):
    mycursor = mydb.cursor()
    sql = "SELECT id, concat(region, ' ', team) as t FROM teams having t = '" + region_team + "';";
    mycursor.execute(sql)

    myresults = mycursor.fetchall()

    return myresults[0]

def scrape_pfr(league_year, dates_to_check):
    # Making a GET request
    r = requests.get('https://www.pro-football-reference.com/years/' + str(league_year) + '/games.htm')
    # Parsing the HTML
    soup = BeautifulSoup(r.content, 'html.parser')

    games_list = []

    for row in soup.find_all('td', attrs={"csk": dates_to_check}):
        game = {
            "league_year"  : str(league_year),
            "league_week"  : row.parent.find('th', attrs={"data-stat": "week_num"}).string,
            "game_date"    : row.parent.find('td', attrs={"data-stat": "game_date"}).string,
            "game_time"    : row.parent.find('td', attrs={"data-stat": "gametime"})['csk'],
            "away_team"    : get_id_from_region_team(mydb, row.parent.find('td', attrs={"data-stat": "winner"}).string),
            "home_team"    : get_id_from_region_team(mydb, row.parent.find('td', attrs={"data-stat": "loser"}).string),
            "favorite"     : get_id_from_region_team(mydb, row.parent.find('td', attrs={"data-stat": "loser"}).string),
            "money_line"   : 0.0,
            "point_spread" : 0.0,
            "ou"           : 0.0,
            "odds_api_id"  : ''
        }
        
        games_list.append(game)

    return games_list

def getOdds(now):
    SPORT = 'americanfootball_nfl'
    REGIONS = 'us'
    MARKETS = 'h2h,spreads,totals' # h2h | spreads | totals. Multiple can be specified if comma delimited
    ODDS_FORMAT = 'american' # decimal | american
    DATE_FORMAT = 'iso' # iso | unix
    BOOKMAKERS = 'draftkings'
    api_date_format = '%Y-%m-%dT%H:%M:%SZ'
    
    iso_zulu_string = now.strftime(api_date_format)

    odds_response = requests.get(
        f'https://api.the-odds-api.com/v4/sports/{SPORT}/odds',
        params={
            'api_key': API_KEY,
            'regions': REGIONS,
            'markets': MARKETS,
            'oddsFormat': ODDS_FORMAT,
            'dateFormat': DATE_FORMAT,
            'bookmakers': BOOKMAKERS
        }
    )
    
    '''
    events_response = requests.get(
        f'https://api.the-odds-api.com/v4/sports/{SPORT}/events',
        params={
            'api_key': API_KEY,
            'commenceTimeTo': iso_zulu_string
        }
    )
    '''
    
    if odds_response.status_code != 200:
        print(f'Failed to get odds: status_code {odds_response.status_code}, response body {odds_response.text}')
        exit(-1)

    odds_json = odds_response.json()
    logger.info('Number of events: ' + str(len(odds_json)))
    
    #with open('data.json', 'w') as f:
    #    json.dump(odds_json, f)
    
    #with open('data.json', 'r') as f:
    #    odds_json = json.load(f)

    #print(json.dumps(odds_json, sort_keys=True, indent=4))

    logger.info('Remaining requests ' + str(odds_response.headers['x-requests-remaining']))
    logger.info('Used requests ' + str(odds_response.headers['x-requests-used']))
    
    return odds_json

def add_odds_to_games(games_list, odds_json):
    for game in games_list: #game is tuple: (id, string)
        for odds in odds_json:
            if game['away_team'][1] == odds['away_team'] and game['home_team'][1] == odds['home_team']:
                # TODO: check if game already added
                game["odds_api_id"] = odds["id"]
                for bookmaker in odds['bookmakers']:
                    if bookmaker["key"] == "draftkings":
                        for market in bookmaker['markets']:
                            if market["key"] == "h2h":
                                price = 0
                                # Determine favorite
                                if market["outcomes"][0]['price'] < 0:
                                    game['favorite'] = get_id_from_region_team(mydb, market["outcomes"][0]['name'])
                                else:
                                    game['favorite'] = get_id_from_region_team(mydb, market["outcomes"][1]['name'])
                                for outcome in market["outcomes"]:
                                    price += abs(outcome["price"])
                                game['money_line'] = str(math.ceil((price / 2 / 100) * 10) / 10)
                            if market["key"] == "spreads":
                                # Take 1st spread
                                game['point_spread'] = str(abs(market["outcomes"][0]['point']))
                            if market["key"] == "totals":
                                # Take 1st total
                                game['ou'] = str(abs(market["outcomes"][0]['point']))
    return games_list

def update_db(mydb, games_list, sportsbook_url, admin_url):
    mycursor = mydb.cursor()
    sportsbook_webhook = WebhookClient(sportsbook_url)
    admin_webhook = WebhookClient(admin_url)
    sportsbook_msg = "New Lines Posted!\n"
    for game in games_list:
        kickoff_time = game["game_date"] + " " + game["game_time"]
        sql = "INSERT INTO games (kickoff_time, league_year, league_week, away, home, favorite, point_spread, money_line, ou) VALUES "
        sql += "('"
        sql += kickoff_time              + "', '"
        sql += game["league_year"]       + "', '"
        sql += game["league_week"]       + "', '"
        sql += str(game["away_team"][0]) + "', '"
        sql += str(game["home_team"][0]) + "', '"
        sql += str(game["favorite"][0])  + "', '"
        sql += game["point_spread"]      + "', '"
        sql += game["money_line"]        + "', '"
        sql += game["ou"]
        sql += "')"
        if mycursor.execute(sql):
            error_msg = "Error inserting: " + sql
            admin_webhook.send(text=error_msg)
        else:
            sportsbook_msg += "* "  + kickoff_time + ": " + game["away_team"][1] + " @ " + game["home_team"][1]
            sportsbook_msg += " Line: " + game["favorite"][1] + " -" + game["point_spread"] + " ML: " + game["money_line"]
            sportsbook_msg += " OU: " + game["ou"] + "\n";

        mydb.commit()
    #print(sportsbook_msg)
    sportsbook_webhook.send(text=sportsbook_msg)

def main():
    """Main function of the script."""

    # Parse command-line arguments
    parser = argparse.ArgumentParser(description="Your script description.")
    parser.add_argument("-d", "--days", help="Number of days in the future to check", type=int, default=0)
    parser.add_argument("-y", "--year", help="League year", type=int, default=2025)
    args = parser.parse_args()

    # Your script logic goes here
    logger.info("Starting script execution...")
    # Make list of days to check
    now = datetime.datetime.utcnow()
    pfr_date_format = '%Y-%m-%d'
    dates_to_check = [now.strftime(pfr_date_format)]
    for i in range(1, args.days + 1):
        now += datetime.timedelta(days=1)
        dates_to_check.append(now.strftime(pfr_date_format))
    logger.info("Checking dates for games: %s", dates_to_check)
    games_list = scrape_pfr(args.year, dates_to_check)
    odds_json = getOdds(now)
    games_list = add_odds_to_games(games_list, odds_json)
    update_db(mydb, games_list, sportsbook_url, admin_url)
    
if __name__ == "__main__":
    main()
