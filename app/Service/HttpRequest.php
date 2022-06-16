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

use tegic\Http;

class HttpRequest
{
    public function request($url, $data = [], $headers = [], $responseOrigin = false)
    {
        $headers['User-Agent'] = config('packagist.user_agent');
        $client = new Http();
        $response = $client->request($data ? 'POST' : 'GET', $url, [
            'header' => $headers,
            'timeout' => 120,
            //                'curlOptions'=>[
            //                    CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,
            //                    CURLOPT_TCP_KEEPALIVE=>10,
            //                    CURLOPT_TCP_KEEPIDLE=>10,
            //                ]
        ]);

        if ($response->getStatusCode() != 200 || $response->getErrorMessage()) {
            return [new \Exception(sprintf('request fail code :%s message:%s', $response->getStatusCode(), $response->getErrorMessage())), $response->getBody() ?? null];
        }

        return [null, $responseOrigin ? $response : $response->getBody()];
    }
}
