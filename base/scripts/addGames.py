#!/usr/bin/env python3

"""
Check for games and add/update the lines as needed
"""

import argparse
import logging
import datetime
from zoneinfo import ZoneInfo
import requests
import json
import math
from slack_sdk.webhook import WebhookClient

from dbCreds import mydb, API_KEY, sportsbook_url, admin_url

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

SPORT = 'americanfootball_nfl'
# Kickoff times are stored/compared (NOW(), time()) against the app server's
# local clock, which runs America/New_York - so convert the-odds-api's UTC
# commence_time into that zone before splitting it into date/time strings.
EASTERN_TZ = ZoneInfo('America/New_York')

def get_id_from_region_team(mydb, region_team):
    mycursor = mydb.cursor()
    sql = "SELECT id, concat(region, ' ', team) as t FROM teams having t = '" + region_team + "';";
    mycursor.execute(sql)

    myresults = mycursor.fetchall()

    return myresults[0]

def get_events(mydb, league_year, league_week, dates_to_check):
    """Fetch upcoming NFL games from the-odds-api's /events endpoint.

    Replaces the old pro-football-reference HTML scrape, which pfr's
    Cloudflare bot-detection now blocks. the-odds-api is already used
    elsewhere in this script/repo for lines and scores, so this reuses the
    same API key/quota instead of fighting a JS challenge.
    """
    events_response = requests.get(
        f'https://api.the-odds-api.com/v4/sports/{SPORT}/events',
        params={'api_key': API_KEY}
    )

    if events_response.status_code != 200:
        print(f'Failed to get events: status_code {events_response.status_code}, response body {events_response.text}')
        exit(-1)

    events_json = events_response.json()

    games_list = []
    # dates_to_check is calendar-day granularity, so re-running this later in
    # the same day (e.g. re-pulling for Sunday's late/primetime games) must
    # not let an already-kicked-off early game back in - its line is locked
    # once picks have been made against it.
    now_eastern = datetime.datetime.now(EASTERN_TZ)

    for event in events_json:
        commence_utc = datetime.datetime.strptime(event['commence_time'], '%Y-%m-%dT%H:%M:%SZ')
        commence_utc = commence_utc.replace(tzinfo=datetime.timezone.utc)
        commence_eastern = commence_utc.astimezone(EASTERN_TZ)

        if commence_eastern.strftime('%Y-%m-%d') not in dates_to_check:
            continue

        if commence_eastern <= now_eastern:
            continue

        game = {
            "league_year"  : str(league_year),
            "league_week"  : str(league_week),
            "game_date"    : commence_eastern.strftime('%Y-%m-%d'),
            "game_time"    : commence_eastern.strftime('%H:%M:%S'),
            "away_team"    : get_id_from_region_team(mydb, event['away_team']),
            "home_team"    : get_id_from_region_team(mydb, event['home_team']),
            # Placeholder; add_odds_to_games overwrites this from real market data.
            "favorite"     : get_id_from_region_team(mydb, event['home_team']),
            "money_line"   : 0.0,
            "point_spread" : 0.0,
            "ou"           : 0.0,
            "odds_api_id"  : event['id']
        }

        games_list.append(game)

    return games_list

def get_current_week():
    """Look up the current NFL season/week from ESPN's public scoreboard API.

    Used as a default for --year/--week when they aren't passed explicitly.
    No API key required and no bot-detection like pro-football-reference has.
    """
    response = requests.get('https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard')
    if response.status_code != 200:
        print(f'Failed to look up current week from ESPN: status_code {response.status_code}, response body {response.text}')
        exit(-1)

    data = response.json()
    return data['season']['year'], data['week']['number']

def getOdds(now):
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

def format_kickoff(kickoff_time):
    """Mirror PHP's date('D m/d g:i A', ...) formatting used in GamesController."""
    if isinstance(kickoff_time, datetime.datetime):
        return kickoff_time.strftime('%a %m/%d %I:%M %p')
    return str(kickoff_time)

def get_line_snapshot(mycursor, odds_api_id):
    """(kickoff_time, favorite, point_spread, money_line, ou) currently
    stored for this game, or None if it isn't in the table yet."""
    mycursor.execute(
        "SELECT kickoff_time, favorite, point_spread, money_line, ou "
        "FROM games WHERE odds_api_id = %s",
        (odds_api_id,)
    )
    return mycursor.fetchone()

def update_db(mydb, games_list, sportsbook_url, admin_url):
    mycursor = mydb.cursor()
    sportsbook_webhook = WebhookClient(sportsbook_url)
    admin_webhook = WebhookClient(admin_url)

    # Only report what's actually true: "New Lines Posted!" only for games
    # that didn't already exist, and a "Line change!" diff (same style as
    # GamesController::updateGame) only for games whose line actually moved -
    # never for a re-run that left everything the same.
    new_games_msg = "New Lines Posted!\n"
    changed_games_msg = "Line change!\n"
    any_new = False
    any_changed = False

    for game in games_list:
        kickoff_time = game["game_date"] + " " + game["game_time"]

        before = get_line_snapshot(mycursor, game["odds_api_id"])

        sql = "INSERT INTO games (kickoff_time, league_year, league_week, away, home, favorite, point_spread, money_line, ou, odds_api_id) VALUES "
        sql += "('"
        sql += kickoff_time              + "', '"
        sql += game["league_year"]       + "', '"
        sql += game["league_week"]       + "', '"
        sql += str(game["away_team"][0]) + "', '"
        sql += str(game["home_team"][0]) + "', '"
        sql += str(game["favorite"][0])  + "', '"
        sql += game["point_spread"]      + "', '"
        sql += game["money_line"]        + "', '"
        sql += game["ou"]                + "', '"
        sql += game["odds_api_id"]
        sql += "')"
        # odds_api_id is UNIQUE (the-odds-api's stable id for this matchup),
        # so re-running for a game we've already inserted updates its line
        # instead of adding a duplicate row. The IF() guards are a second,
        # DB-level backstop (get_events already excludes started games) so a
        # game's line can never change once its kickoff_time has passed -
        # picks are graded against whatever line was locked in at kickoff.
        sql += (
            " ON DUPLICATE KEY UPDATE"
            " kickoff_time = IF(kickoff_time > NOW(), VALUES(kickoff_time), kickoff_time),"
            " favorite = IF(kickoff_time > NOW(), VALUES(favorite), favorite),"
            " point_spread = IF(kickoff_time > NOW(), VALUES(point_spread), point_spread),"
            " money_line = IF(kickoff_time > NOW(), VALUES(money_line), money_line),"
            " ou = IF(kickoff_time > NOW(), VALUES(ou), ou)"
        )
        try:
            mycursor.execute(sql)
        except Exception as e:
            # mysql-connector raises on error rather than returning a falsy
            # result, so this previously never caught anything (and an
            # uncaught error here would have killed the whole batch).
            error_msg = "Error inserting: " + sql + "\n" + str(e)
            admin_webhook.send(text=error_msg)
            mydb.commit()
            continue

        mydb.commit()

        if before is None:
            any_new = True
            new_games_msg += "* "  + kickoff_time + ": " + game["away_team"][1] + " @ " + game["home_team"][1]
            new_games_msg += " Line: " + game["favorite"][1] + " -" + game["point_spread"] + " ML: " + game["money_line"]
            new_games_msg += " OU: " + game["ou"] + "\n"
            continue

        # Read back what's actually stored now rather than trusting the
        # values we tried to write - reflects reality if the IF() guard
        # above left an already-started game's row untouched.
        after = get_line_snapshot(mycursor, game["odds_api_id"])
        if before == after:
            continue

        any_changed = True

        def team_name(team_id):
            if team_id == game['away_team'][0]:
                return game['away_team'][1]
            if team_id == game['home_team'][0]:
                return game['home_team'][1]
            return str(team_id)

        old_kickoff, old_favorite, old_point_spread, old_money_line, old_ou = before
        new_kickoff, new_favorite, new_point_spread, new_money_line, new_ou = after
        matchup = game["away_team"][1] + " @ " + game["home_team"][1]
        changed_games_msg += (
            "FROM:\n"
            "*  " + format_kickoff(old_kickoff) + ": " + matchup +
            " Line: " + team_name(old_favorite) + " -" + str(old_point_spread) +
            " ML: " + str(old_money_line) + " OU: " + str(old_ou) +
            "\nTO:\n"
            "*  " + format_kickoff(new_kickoff) + ": " + matchup +
            " Line: " + team_name(new_favorite) + " -" + str(new_point_spread) +
            " ML: " + str(new_money_line) + " OU: " + str(new_ou) + "\n"
        )

    if any_new:
        sportsbook_webhook.send(text=new_games_msg)
    if any_changed:
        sportsbook_webhook.send(text=changed_games_msg)

def main():
    """Main function of the script."""

    # Parse command-line arguments
    parser = argparse.ArgumentParser(description="Your script description.")
    parser.add_argument("-d", "--days", help="Number of days in the future to check", type=int, default=0)
    parser.add_argument("-y", "--year", help="League year (default: current NFL season, from ESPN)", type=int, default=None)
    parser.add_argument("-w", "--week", help="League week number (default: current NFL week, from ESPN)", type=int, default=None)
    args = parser.parse_args()

    # Your script logic goes here
    logger.info("Starting script execution...")
    if args.year is None or args.week is None:
        espn_year, espn_week = get_current_week()
        args.year = args.year if args.year is not None else espn_year
        args.week = args.week if args.week is not None else espn_week
        logger.info("Defaulted league_year=%s league_week=%s from ESPN", args.year, args.week)
    # Make list of days to check, in the same Eastern local time the games'
    # kickoff_time will be stored/compared in.
    now = datetime.datetime.now(EASTERN_TZ)
    pfr_date_format = '%Y-%m-%d'
    dates_to_check = [now.strftime(pfr_date_format)]
    for i in range(1, args.days + 1):
        now += datetime.timedelta(days=1)
        dates_to_check.append(now.strftime(pfr_date_format))
    logger.info("Checking dates for games: %s", dates_to_check)
    games_list = get_events(mydb, args.year, args.week, dates_to_check)
    if not games_list:
        # get_events() is free; getOdds() costs the-odds-api credits
        # (3/call: 1 region x 3 markets) regardless of how many events it
        # returns, so skip it entirely on days with nothing to update.
        logger.info("No games found for the requested dates - skipping the odds lookup.")
        return
    odds_json = getOdds(now)
    games_list = add_odds_to_games(games_list, odds_json)
    update_db(mydb, games_list, sportsbook_url, admin_url)
    
if __name__ == "__main__":
    main()
