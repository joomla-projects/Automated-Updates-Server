# Joomla Automated Updates Server
This is the server for automated updates of Joomla CMS instances running on joomla.org

## Deployment
* Install docker and docker compose
* Check out this repo
* Copy to .env.example to .env and adjust to your requirements - important: set UID and GID to user and group on the host system that you would like to use for the processes
* Start the webserver and DB services:  `docker-compose -f docker-compose.prod.yml up -d` 
* Apply the database migrations: `docker-compose run --entrypoint="php artisan migrate" php`
* Daemonize the queue worker, i.e. by using supervisord; the call in question is: `docker-compose run --entrypoint="php artisan horizon" php`
* Add a cron job to execute the task scheduler. The scheduler should be trigger every 5min, the call is: `docker-compose run --entrypoint="php artisan schedule:run" php`

## Periodic operations
The update server performs several operations automatically, if the the cron job is configured. Each operation is a artisan console job and can be manually performed if necessary.

| Rhythm           | Command                  | Description                                                                                                                                                                                                                                                            |
|------------------|--------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Every six hours  | app:cleanup-sites-list   | Clean sites that didn't have a successful health check call in the last 7 days                                                                                                                                                                                         |
| Every 15 minutes | app:queue-health-checks  | Retrieves a list of sites that are pending for their health check (currently defined as "last checked more than 24 hours ago") and pushed the individual check jobs into the queue; if a pending update is detected during a check, it will be executed automatically. |
| Daily            | queue:prune-failed       | Removes failed jobs from the laravel job queue; failed updates and their details are logged in the updates table anyways                                                                                                                                               |

## Manual operations
The update server has multiple manual operations available that can be triggered via CLI. In order to do so, execute the respective command in the PHP container of the docker setup:
`docker-compose run --entrypoint="php artisan $COMMAND" php`

| Command                        | Description                                                                                                               |
|--------------------------------|---------------------------------------------------------------------------------------------------------------------------|
| app:check-site-health {siteId} | Runs a health check for a given site ID.                                                                                  |
| app:perform-update {siteId}    | Runs an update job for a given site ID.                                                                                   |
| app:queue-updates              | Triggers update jobs for a given number of sites. Runs in interactive mode and queries target version and number of sites |

## Queue Monitoring
A monitoring dashboard for the queue is available on the selected HTTP port via `domain.tld/horizion`. It's protected with basic auth, credentials are defined in the .env file.
