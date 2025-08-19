<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteRequest;
use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\Http\Traits\ApiResponse;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

/**
 * Class SiteController
 * @package  App\Http\Controllers\Api\V1
 * @since    1.0
 */
class SiteController extends Controller
{
    use ApiResponse;

    /**
     * @param SiteRequest $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(SiteRequest $request): JsonResponse
    {
        $url = $request->string('url');
        $key = $request->string('key');

        $connectionService = App::makeWith(
            Connection::class,
            ["baseUrl" => rtrim($url, "/"), "key" => $key]
        );

        // Do a health check
        try {
            $healthResponse = $connectionService->checkHealth();
        } catch (ServerException|ClientException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }

        // Remove older duplicates of the site if registered
        Site::query()->where('url', $url)->delete();

        // Create new row
        $site = new Site();
        $site->key = $key;
        $site->url = $url;
        $site->last_seen = Carbon::now();

        // Fill with site info
        $site->fill($healthResponse->toArray());

        $site->save();

        CheckSiteHealth::dispatch($site);

        return $this->ok();
    }

    /**
     * @param SiteRequest $request
     *
     * @return JsonResponse
     */
    public function check(SiteRequest $request): JsonResponse
    {
        $url = $request->string('url');
        $key = $request->string('key');

        try {
            /** @var Site $site */
            $site = Site::where('url', $url)->where('key', $key)->firstOrFail();
        } catch (\Exception $e) {
            return $this->error("Not found", 404);
        }

        // Do a health check
        try {
            $site->connection->checkHealth();
        } catch (ServerException|ClientException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }

        return $this->ok();
    }

    /**
     * @param SiteRequest $request
     *
     * @return JsonResponse
     */
    public function delete(SiteRequest $request): JsonResponse
    {
        $url = $request->string('url');
        $key = $request->string('key');

        try {
            $site = Site::where('url', $url)->where('key', $key)->firstOrFail();
        } catch (\Exception $e) {
            return $this->error("Not found", 404);
        }

        $site->delete();

        return $this->ok();
    }
}
