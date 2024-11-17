<?php

namespace App\Providers;

use App\Models\TufMetadata;
use App\TUF\DatabaseStorage;
use App\TUF\HttpLoader;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Tuf\Client\Updater;
use Tuf\Loader\SizeCheckingLoader;
use Tuf\Metadata\StorageInterface;

class TUFServiceProvider extends ServiceProvider
{
    public const REPO_PATH = "https://update.joomla.org/cms/";

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(StorageInterface::class, function ($app) {
            // Setup loader
            $httpLoader = new HttpLoader(
                self::REPO_PATH,
                App::make(Client::class)
            );

            $sizeCheckingLoader = new SizeCheckingLoader($httpLoader);

            // Setup storage
            $storage = new DatabaseStorage(TufMetadata::findOrFail(1));

            // Create updater
            $updater = new Updater(
                $sizeCheckingLoader,
                $storage
            );

            // Fetch Updates
            $updater->refresh();

            $storage->persist();

            return $storage;
        });
    }
}
