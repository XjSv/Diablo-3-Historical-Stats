#  Diablo 3 Historical Stats
================

Diablo 3 Historical Stats Application (Beta)

This is a very sloppy release due to user requests and a short period of time. I would almost consider this a prototype. 
It has little documentation, but I will document more on the days to come.

### Requirements:
- - -
 - Battlenet Account
 - PHP 5.3+
 - MongoDB

- - -

### Setup  
- - -
 - Edit: 'config/settings.php' and enter the desired db and collection names.

- - -

### Process Flow  
- - -
 - Add users via the "Search/Add BattleTag" in the header.
 - Schedule a job to run '/tools/load.php' every hour or so or run it manually. (This get character data and images)

- - -  

Using: https://github.com/XjSv/Diablo-3-API-PHP

Demo Video: http://www.youtube.com/watch?v=LozMnrCti5g

Live Site: http://d3stats.tk

Disclaimer: This application or armandotresova.com is no way affiliated or endorsed by Blizzard Entertainment®. All artwork related to the game and all other copyrighted content related to Diablo® III is property of Blizzard Entertainment®, Inc.

