<?php

namespace App\Console\Traits;

use App\TUF\TufFetcher;

trait RequestTargetVersion
{
    protected function queryTargetVersion(): string
    {
        $releases = (new TufFetcher())->getReleases();

        return $this->choice( // @phpstan-ignore-line
            "What's the target version?",
            $releases->map(fn ($release) => $release["version"])->values()->toArray() // @phpstan-ignore-line
        );
    }
}
