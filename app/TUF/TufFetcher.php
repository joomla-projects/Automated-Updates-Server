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
     * @return Collection<string, ReleaseData>
     */
    public function getReleases(): Collection
    {
        // Cache response to avoid to make constant calls on the fly
        $releases = Cache::remember(
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

                        $release = ReleaseData::from($target['custom']);

                        return [$release->version => $release];
                    });
            }
        );

        if (!$releases instanceof Collection) {
            throw new MetadataException("Invalid release list");
        }

        return $releases;
    }

    public function getLatestVersionForBranch(int $branch): ?string
    {
        $versionMatch = $this->getReleases()->filter(function (ReleaseData $release) use ($branch): bool {
            return strtolower($release->stability) === "stable" && $release->version[0] === (string) $branch;
        })->sort(function (ReleaseData $releaseA, ReleaseData $releaseB): int {
            return version_compare($releaseA->version, $releaseB->version);
        })->last();

        if (!$versionMatch instanceof ReleaseData) {
            return null;
        }

        return $versionMatch->version;
    }
}
