<?php

namespace App\TUF;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tuf\Exception\MetadataException;
use Tuf\Metadata\StorageInterface;

class TufFetcher
{
    protected StorageInterface $updateStorage;

    public function __construct()
    {
        $this->updateStorage = App::make(StorageInterface::class);
    }

    /**
     * @return Collection
     */
    public function getReleases(): Collection
    {
        // Cache response to avoid to make constant calls on the fly
        return Cache::remember(
            'cms_targets',
            (int) config('autoupdates.tuf_repo_cachetime') * 60, // @phpstan-ignore-line
            function () {
                $targets = $this->updateStorage->getTargets();

                // Make sure we have a valid list of targets
                if (is_null($targets)) {
                    throw new MetadataException("Empty targetlist in metadata");
                }

                // Convert format
                return (new Collection($targets->getSigned()['targets']))
                    ->mapWithKeys(function (mixed $target) {
                        if (!is_array($target) || empty($target['custom']) || !is_array($target['custom'])) {
                            throw new MetadataException("Empty target custom attribute");
                        }

                        return [$target['custom']['version'] => $target['custom']];
                    });
            }
        );
    }

    public function getLatestVersionForBranch(int $branch): string
    {
        return $this->getReleases()->filter(function ($release) {
                return $release["stability"] === "Stable";
            })->sort(function ($releaseA, $releaseB) {
                return version_compare($releaseA["version"], $releaseB["version"], '<');
            })->pluck('version')->first();
    }
}
