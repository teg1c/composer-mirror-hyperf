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

class Packagist
{
    public HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function getPackagesJSON()
    {
        /** Psr\Http\Message\ResponseInterface $response */
        [$err,$response] = $this->httpRequest->request(config('packagist.repo_url') . 'packages.json', [], [], true);
        if ($err) {
            std_logger()->error(sprintf('"get packages.json failed::%s', format_throwable($err)));
            return false;
        }
        $lastModified = $response->getHeaderLine('Last-Modified');
        $body = $response->getBody();
        return compact('lastModified', 'body');
    }

    public function getInitTimestamp()
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'metadata/changes.json');
        if ($err) {
            return false;
        }
        $response = json_decode($response,true);
        if (empty($response)){
            return false;
        }
        return $response['timestamp'];
    }

    public function getAllPackages()
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'packages/list.json');
        if ($err) {
            return false;
        }
        return json_decode($response,true);
    }

    public function getMetadataChanges($lastTimestamp)
    {
        [$err,$response] = $this->httpRequest->request(config('packagist.api_url') . 'metadata/changes.json?since='.$lastTimestamp);
        if ($err) {
            return false;
        }
        return json_decode($response,true);
    }

    public function get($path)
    {
        $url = config('packagist.repo_url') .$path;
        [$err,$response] = $this->httpRequest->request($url);
        if ($err) {
            std_logger()->error("packagist.repo_url [{$url}] error :".$err->getMessage());
            return false;
        }
        return $response;
    }
}
