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

use App\Kernel\Curl;
use Hyperf\Guzzle\ClientFactory;

class HttpRequest
{

    public function request($url, $data = [], $headers = [], $responseOrigin = false)
    {
        $headers['User-Agent'] = config('packagist.user_agent');

        $client =new \EasyHttp\Client();
        try {
            $response = $client->request($data ? 'POST' : 'GET', $url, [
                'header' => $headers,
                'body' => $data ?  : [],
            ]);
        } catch (\Throwable $e) {
            return [$e, null];
        }

        if ($responseCode = $response->getStatusCode() != 200) {
            return [new \Exception(sprintf('request fail code :%s', $responseCode)), null];
        }

        return [null, $responseOrigin ? $response : $response->getBody()];
    }

}
