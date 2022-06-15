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

class Github
{
    public HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function getDist($url)
    {
        [$err,$response] = $this->httpRequest->request($url, [], [
            'Authorization' => sprintf('token %s', config('packagist.github_token')),
        ]);
        if ($err) {
            std_logger()->error("get github dist faild [{$url}] error:" . $err->getMessage());
            return false;
        }
        std_logger()->info("get github dist success [{$url}]");
        return $response;
    }

    public function test(): void
    {
        $url = 'https://api.github.com/zen';
        [$err,] = $this->httpRequest->request($url, [], [
            'Authorization' => sprintf('token %s', config('packagist.github_token')),
        ]);
        if ($err) {
            throw $err;
        }
    }
}
