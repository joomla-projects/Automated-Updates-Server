<?php

namespace App\Console\Traits;

use App\TUF\TufFetcher;

trait RequestTargetVersion
{
    protected function getVersionChoices()
    {
        $releases = (new TufFetcher())->getReleases();

        $targetVersion = $this->choice(
            "What's the target version?",
            $releases->map(fn($release) => $release["version"])->values()->toArray()
        );

        return $targetVersion;
    }
}
