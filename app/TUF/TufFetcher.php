<?php

namespace App\TUF;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tuf\Exception\MetadataException;
use Tuf\Metadata\StorageInterface;

class TufFetcher
{
    protected Client $httpClient;

    protected StorageInterface $updateStorage;

    public function __construct()
    {
        $this->httpClient = App::make(Client::class);
        $this->updateStorage = App::make(StorageInterface::class);
    }

    public function getReleases()
    {
        return Cache::remember('cms_targets', config('autoupdates.tuf_repo_cachetime') * 60, function () {
            $targets = $this->updateStorage->getTargets();

            if (is_null($targets)) {
                throw new MetadataException("Empty targetlist in metadata");
            }

            return collect($targets->getSigned()['targets'])
                ->mapWithKeys(function ($target) {
                    return [$target['custom']['version'] => $target['custom']];
                });
        });
    }
}
