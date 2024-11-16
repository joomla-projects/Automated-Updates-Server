<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CheckSiteHealth;
use App\Models\Site;
use App\RemoteSite\Connection;
use App\Traits\ApiResponse;
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
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(Request $request): JsonResponse
    {
        $url = $request->string('url');
        $key = $request->string('key');

        if ($url->isEmpty() && $key->isEmpty()) {
            return $this->error('BadRequest');
        }

        $connectionService = new Connection($url, $key);

        // Do a health check
        try {
            $connectionService->checkHealth();
        } catch (ServerException $e) {
            return $this->error($e->getMessage(), 500);
        } catch (ClientException|\Exception $e) {
            return $this->error($e->getMessage());
        }

        // If successful save site
        $site = new Site();

        $site->key = $key;
        $site->url = $url;

        $site->save();

        CheckSiteHealth::dispatch($site);

        return $this->ok();
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        return response()->json(['check']);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        return response()->json(['delete']);
    }
}
