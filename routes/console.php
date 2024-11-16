<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Queue\Console\PruneFailedJobsCommand;

Schedule::command(PruneFailedJobsCommand::class)->daily();
