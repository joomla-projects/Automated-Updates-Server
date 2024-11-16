<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Queue\Console\PruneFailedJobsCommand;
use App\Console\Commands\QueueHealthChecks;

Schedule::command(PruneFailedJobsCommand::class)->daily();
Schedule::command(QueueHealthChecks::class)->everyFifteenMinutes();
