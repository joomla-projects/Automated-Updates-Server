# Joomla Automated Updates Server
This is the server for automated updates of Joomla CMS instances running on joomla.org

# Deployment and usage
* Install docker and docker compose
* Check out this repo
* Copy to .env.example to .env and adjust to your requirements - important: set UID and GID to user and group on the host system that you would like to use for the processes
* Start the webserver and DB services:  `docker-compose -f docker-compose.prod.yml up -d` 
* Apply the database migrations: `docker-compose run --entrypoint="php artisan migrate" php`
* Daemonize the queue worker using supervisord; the call in question is: `docker-compose run --entrypoint="php artisan horizon" php`
* Add a cron job to execute the task scheduler. The scheduler should be trigger every 5min, the call is: `docker-compose run --entrypoint="php artisan schedule:run" php`

