# Wortspielkasse

A virtual pun fund based on [Strichliste](https://github.com/strichliste/strichliste).

## Requirements

* Strichliste installed in `/var/www/public`
* Environment file located at `/var/www/.env` (for database credentials)
* An article used to charge a pun (In our case, the article has the ID 53)
* A user account to hold the fund balance (In our case, the user has the ID 98)
* Periodic execution of [convert.php](convert.php) (e.g. via cronjob)
* Recommended own directory: `/var/www/sl-wortspielkasse`