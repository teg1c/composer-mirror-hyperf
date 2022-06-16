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
namespace App\Service\Sync;

use App\Service\AliyunOss;
use App\Service\Code;
use App\Service\Packagist;
use Carbon\Carbon;
use Hyperf\Utils\Coroutine;

class SyncPackagesJsonFile
{
    public function exec()
    {
        while (true) {
            try {
                $this->sync();
            } catch (\Throwable $e) {
                logger()->error("SyncPackagesJsonFile error:".format_throwable($e));
            }
            Coroutine::sleep(60);
        }
    }

    private function sync()
    {
        // Get root file from packagist repo
        std_logger()->info('get packages.json now');
        $getPackagesJSON = make(Packagist::class)->getPackagesJSON();
        if ($getPackagesJSON === false) {
            return false;
        }

        std_logger()->info('get packages.json done');
        $packagistLastModified = $getPackagesJSON['lastModified'];
        $packagistContent = $getPackagesJSON['body'];
        redis()->set(Code::packagistLastModifiedKey, $packagistLastModified);

        $hash = hash('sha256', $packagistContent);
        $localPackagesJsonHash = redis()->get(Code::packagesJsonKey);
        if ($hash == $localPackagesJsonHash) {
            std_logger()->error('packages.json is not changed');
            return false;
        }
        $packagesJson = json_decode($packagistContent, true);

        if (! empty($packagesJson['provider-includes'])) {
            foreach ($packagesJson['provider-includes'] as $provider => $hashValue) {
                $providerHash = $hashValue['sha256'];
                $providerPath = str_replace('%hash%', $providerHash, $provider);
                std_logger()->info('dispatch providers: ' . $provider);
                redis()->sAdd(Code::providerQueue, json_encode([
                    'Key' => $provider,
                    'Path' => $providerPath,
                    'Hash' => $providerHash,
                ],JSON_UNESCAPED_UNICODE));
                $getTodayKey = Code::providerQueue . '-' . date('Y-m-d');
                redis()->sAdd($getTodayKey, $providerHash);
                redis()->expireAt($getTodayKey, Carbon::tomorrow()->timestamp);
            }
        }
        while (true) {
            // If all tasks are completed, skip the loop and update the file
            $distQueueSize = redis()->sCard(Code::distQueue);
            $providerQueueSize = redis()->sCard(Code::providerQueue);
            $packageP1QueueSize = redis()->sCard(Code::packageP1Queue);
            $packageV2QueueSize = redis()->sCard(Code::packageV2Queue);
            $left = $distQueueSize + $providerQueueSize + $packageP1QueueSize + $packageV2QueueSize;
            if ($left == 0) {
                break;
            }
            std_logger()->info(sprintf('Processing: %s, Check again in 1 second.', $left));
            Coroutine::sleep(1);
        }
        // Update `packages.json`
        $newPackagesJson = json_decode($packagistContent, true);
        $lastUpdateTime = date('Y-m-d H:i:s');
        // set to redis
        redis()->set(Code::lastUpdateTimeKey, $lastUpdateTime);

        // Update packages.json
        $newPackagesJson = json_encode([
            'info' => '',
            'last-update' => $lastUpdateTime,
            'metadata-url' => config('packagist.provider_url') . 'p2/%package%.json',
            'providers-url' => config('packagist.provider_url') . 'p/%package%$%hash%.json',
            'mirrors' => [
                [
                    'dist-url' => config('packagist.dist_url') . 'dists/%package%/%reference%.%type%',
                    'preferred' => true,
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
        $oss = make(AliyunOss::class)->write('packages.json', $newPackagesJson, [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            std_logger()->error('save packages.json on OSS failed');
            return false;
        }
        redis()->set(Code::packagesJsonKey, $hash);
        return true;
    }
}
