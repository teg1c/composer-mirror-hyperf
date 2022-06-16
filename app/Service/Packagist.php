<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Service;

use Hyperf\Guzzle\ClientFactory;
use tegic\Http;

class Packagist
{
    public HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function getPackagesJSON()
    {
        $http = new Http();
        $response = $http->get(config('packagist.repo_url') . 'packages.json');

        if ($errMsg = $response->getErrorMessage()){
            std_logger()->error(sprintf('"get packages.json failed::%s', $errMsg));
            return false;
        }
        $lastModified = $response->getHeaderLine('Last-Modified');
        $body = $response->getBody();
        return compact('lastModified', 'body');
    }

    public function getInitTimestamp()
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'metadata/changes.json');
        if ($err && empty($response)) {
            std_logger()->error('get v2 last updatetimekey error:' . $err->getMessage());
            return false;
        }
        $response = json_decode($response, true);
        if (empty($response)) {
            std_logger()->error('get v2 last updatetimekey json decode error:');
            return false;
        }
        return $response['timestamp'];
    }

    public function getAllPackages()
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'packages/list.json');
        if ($err) {
            std_logger()->error('getAllPackages error:' . format_throwable($err));
            return false;
        }
        return json_decode($response, true);
    }

    public function getMetadataChanges($lastTimestamp)
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'metadata/changes.json?since=' . $lastTimestamp);
        if ($err) {
            std_logger()->error('getMetadataChanges error:' . format_throwable($err));
            return false;
        }
        return json_decode($response, true);
    }

    public function get($path)
    {
        $url = config('packagist.repo_url') . $path;
        [$err,$response] = $this->httpRequest->request($url);
        if ($err) {
            std_logger()->error("packagist.repo_url [{$url}] error :" . $err->getMessage());
            return false;
        }
        return $response;
    }

    public function getPackage($packageName)
    {
        $url = config('packagist.api_url') . 'p2/' . $packageName . '.json';
        [$err,$response] = $this->httpRequest->request($url);
        if ($err) {
            std_logger()->error("packagist.api_url [{$url}] error :" . $err->getMessage());
            return false;
        }
        return $response;
    }
}
