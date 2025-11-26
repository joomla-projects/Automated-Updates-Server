<?php

namespace App\Health;

use Laravel\Horizon\Contracts\JobRepository;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
class QueueLengthCheck extends Check
{
    public function run(): Result
    {
        /** @var JobRepository $jobs */
        $jobs = app(JobRepository::class);

        $result = Result::make()
            ->appendMeta([
                'pending' => $jobs->countPending(),
                'failed' => $jobs->countFailed(),
                'completed' => $jobs->countCompleted()
            ]);

        if ($jobs->countPending() > 50000) {
            return $result
                ->failed('Job queue larger than 50.000 items')
                ->shortSummary('Excessive queue length');
        }

        return $result->ok();
    }
}
