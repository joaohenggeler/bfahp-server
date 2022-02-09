# Big Fat Awesome House Party Server

Big Fat Awesome House Party (BFAHP) was a Shockwave game developed by Powerful Robot Games and published by Cartoon Network on AwesomeHouseParty.com. The game was released on May 15th, 2006, and discontinued by June 15th, 2009, not long after [the show it was based on](https://en.wikipedia.org/wiki/Foster%27s_Home_for_Imaginary_Friends) had ended. 

This repository contains a collection of PHP scripts that emulate the various endpoints required to play the game using a local web server. Users may register any number of accounts, with the game's progress being saved locally to a SQLite database. [The Python server created by sebastian404](https://github.com/sebastian404/bfahps) was used as a reference throughout development, with the following changes being made to its behavior:

* Does not store photo album information in a separate table in the database.

* Responds with random player names and scores for the world highscores.

* Keeps track of some configurations in a JSON file, including the desired home page layout (simplified or original), whether or not to show the buffer page after authentication, and the current game date (for the April Fools and birthday events). This implementation also includes a page called the BFAHP Control Panel which can be used to cheat, change the game's date, list all accounts, or test different server endpoints.

* Remembers the last authenticated user when registering or logging in. This was meant to allow players to play the game using a Shockwave projector if they were always going to use the same account. In practice, players must always play using a browser since the game client doesn't properly encode URLs with spaces in them when loading assets, leading to a malformed HTTP request.

The server endpoints have only been tested using [version 1.14.3 (2007-06-18)](https://github.com/sebastian404/bfahps/blob/master/docs/versions.md) of the game's client. [Here is a video of this version being played locally with this server implementation.](https://youtu.be/ec6HKfvs35U)

**This repository does not include any of the game's assets.**

## Structure

This section will list every relevant file inside the [source directory](Source). These should be placed in `awesomehouseparty.com` for the game to run properly. The domains `i.cartoonnetwork.com` and `i.awesomehouseparty.com` should, respectively, include static page assets (images, CSS, JavaScript) and game assets (Flash movies, Flash videos, XML, Shockwave movies, Shockwave external casts).

Two `.htaccess` files are provided so that Apache servers can perform the following redirections: 1) `awesomehouseparty.com/toonahp/*` to `i.awesomehouseparty.com/toonahp/*`; 2) `awesomehouseparty.com/toon/*` to `i.cartoonnetwork.com/toon/*`; 3) `house/<Endpoint>` to `house/<Endpoint>.php` (with `home.jsp` mapping to `Home.php`). You should be able to easily adapt these to other servers. The `crossdomain.xml` file allows the Flash authentication movie to request data from different domains.

* `house/Bfahp.php`: defines any basic constants (e.g. save version, idle timeout, default JSON configuration, etc) and general purpose functions (e.g. generating avatar code, loading configuration file, etc) used by other scripts.

* `house/Database.php`: defines the game's database schema and provides functions to save the player's information and progress between different sessions.

* `house/Game.php`: defines how the `Game` endpoint responds when the player goes to the Shockwave movie's page.

* `house/Home.php`: defines how the `home.jsp` endpoint responds when the player goes to the game's home page.

* `house/Login.php`: defines how the `Login` endpoint responds when the player authenticates their account.

* `house/Registration.php`: defines how the `Registration` endpoint responds when the player creates an account.

* `house/Service.php`: defines how the `Service` endpoint responds as the player progresses through the game (e.g. when they create an imaginary friend, unlock collectibles, complete daily duties, submit minigame highscores, etc).

* `house/templates/*`: defines the page templates used to build the response for the `Game` and `home.jsp` endpoints. The files `game.html` and `home_simple.html` were taken from sebastian404's Python server project.

The following scripts were added specifically for this implementation and do not have an equivalent in either the original game or sebastian404's Python server.

* `house/controlpanel/index.php`: defines the BFAHP Control Panel frontend using simple submission forms.

* `house/controlpanel/Cheats.php`: defines how the server responds when the player cheats (e.g. max out their citizenship or popularity bars, unlock all minigames, etc) using the BFAHP Control Panel page.

* `house/controlpanel/Configuration.php`: defines how the server responds when the player changes the configuration (e.g. toggle the home page layouts, toggle the buffer page, etc) using the BFAHP Control Panel page.

* `house/controlpanel/Time.php`: defines how the server responds when the player changes the game's date (e.g. skip to the next day, set the game's date to April Fools, etc) using the BFAHP Control Panel page.
