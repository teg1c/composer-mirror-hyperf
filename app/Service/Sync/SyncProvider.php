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

class SyncProvider
{
    public function exec()
    {
        while (true) {
            $jobJson = redis()->SPop(Code::providerQueue);
            if (empty($jobJson)) {
                Coroutine::sleep(1);
                continue;
            }
            $providerArr = json_decode($jobJson, true);
//            std_logger()->info(sprintf('dispatch provider: %s', $providerArr['Key']));
            try {
                $this->sync($providerArr);
            } catch (\Throwable $e) {
                var_dump('syncProvider error', $providerArr);
            }
        }
    }

    private function sync($providerArr)
    {
        if (empty($providerArr)) {
            return false;
        }
        $content = make(Packagist::class)->get($providerArr['Path']);
        if (empty($content)) {
            std_logger()->error('get SyncProvider Path error :' . $providerArr['Path']);
            return false;
        }
        $hash = hash('sha256', $content);
        if ($hash != $providerArr['Hash']) {
            std_logger()->error('Wrong Hash, Original: ' . $providerArr['Hash'] . ' Current: ' . $hash);
            redis()->sAdd(Code::providerQueue, json_encode($providerArr, JSON_UNESCAPED_UNICODE));
            return false;
        }
        $oss = make(AliyunOss::class)->write($providerArr['Path'], json_encode($providerArr, JSON_UNESCAPED_UNICODE), [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            return false;
        }
        redis()->hSet(Code::providerSet, $providerArr['Key'], $providerArr['Hash']);
        $providersRoot = json_decode($content, true);

        if (empty($providersRoot['providers'])) {
            return false;
        }
        foreach ($providersRoot['providers'] as $packageName => $hashers) {
            $sha256 = $hashers['sha256'];
            $value = redis()->hGet(Code::packageV1Set, $packageName);
            if ($sha256 != $value) {
                std_logger()->info(sprintf('dispatch package(%s) to %s', $packageName, Code::packageP1Queue));
                redis()->sAdd(Code::packageP1Queue, json_encode([
                    'Key' => $packageName,
                    'Path' => 'p/' . $packageName . '$' . $sha256 . '.json',
                    'Hash' => $sha256,
                ], JSON_UNESCAPED_UNICODE));
                $getTodayKey = Code::packageV1Set . '-' . date('Y-m-d');
                redis()->sAdd($getTodayKey, $packageName);
                redis()->expireAt($getTodayKey, Carbon::tomorrow()->timestamp);
            }
        }
        // TODO warm cdn url
        return true;
    }
}
