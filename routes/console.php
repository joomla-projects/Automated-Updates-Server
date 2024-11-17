<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Queue\Console\PruneFailedJobsCommand;
use App\Console\Commands\QueueHealthChecks;
use App\Console\Commands\CleanupSitesList;

Schedule::command(CleanupSitesList::class)->everySixHours();
Schedule::command(PruneFailedJobsCommand::class)->daily();
Schedule::command(QueueHealthChecks::class)->everyFifteenMinutes();
