<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteRequest;
use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $connectionService = new Connection($url, $key);

        // Do a health check
        try {
            $healthResponse = $connectionService->checkHealth();
        } catch (ServerException $e) {
            return $this->error($e->getMessage(), 500);
        } catch (ClientException|\Exception $e) {
            return $this->error($e->getMessage());
        }

        // If successful save site
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

        $connectionService = new Connection($url, $key);

        // Do a health check
        try {
            $connectionService->checkHealth();
        } catch (ServerException $e) {
            return $this->error($e->getMessage(), 500);
        } catch (ClientException|\Exception $e) {
            return $this->error($e->getMessage());
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
            Site::where('url', $url)->where('key', $key)->delete();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->ok();
    }
}
