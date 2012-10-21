#  Diablo 3 Historical Stats
================

Diablo 3 Historical Stats Application (Beta/Prototype)

This is a very sloppy release due to user requests and a short period of time. I would almost consider this a prototype. 
It has little documentation, but I will document more on the days to come.

Requirements:
- - -
 - Battlenet Account
 - PHP 5.3+
 - MongoDB
 - ** Windows (for the load process)
- - -

** For other OS's you can either run the scheduled jobs manually via the browser or hopefully you know how to schedule jobs for your OS. If not and requested I will post instructions.

### Setup:
- - -
 - Edit: 'config/settings.php' and enter the desired db and collection names.
 - All the data in settings.php IS REQUIRED.
- - -

### Process Flow:
- - -
 - Schedule a job to run 'Run Load.vbs' every hour. (This get character data and images)
 - Navigate to '/index.php' to view the application.
 - Navigate to 'app_stats.php' to view application stats.
- - -

Using: https://github.com/XjSv/Diablo-3-API-PHP

Demo Video: http://www.youtube.com/watch?v=LozMnrCti5g

