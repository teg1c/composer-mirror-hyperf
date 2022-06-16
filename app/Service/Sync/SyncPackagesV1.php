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

class SyncPackagesV1
{
    public function exec()
    {
        while (true) {
            $jobJson = redis()->sPop(Code::packageP1Queue);
            if (empty($jobJson)) {
                std_logger()->info('get no task from ' . Code::packageP1Queue . ', sleep 1 second');
                Coroutine::sleep(1);
                continue;
            }

            try {
                $job = json_decode($jobJson, true);
                $this->syncPackage($job);
            } catch (\Throwable $e) {
                logger()->error("sync package v1 error:".format_throwable($e));
            }
        }
    }

    private function syncPackage($job)
    {
        std_logger()->info(sprintf("start sync package v1 %s",$job['Path']));
        if (empty($job)) {
            return false;
        }
        $content = make(Packagist::class)->get($job['Path']);

        if (empty($content)){
            return false;
        }
        $hash = hash('sha256', $content);
        if ($hash != $job['Hash']) {
            std_logger()->error('Wrong Hash, Original: ' . $job['Hash'] . ' Current: ' . $hash);
            return false;
        }
        $oss = make(AliyunOss::class)->write($job['Path'], $content, [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            return false;
        }
        redis()->hSet(Code::packageV1Set, $job['Key'], $job['Hash']);

        $response = json_decode($content, true);
        if (! empty($response['packages'])) {
            foreach ($response['packages'] as $packageName => $versions) {
                foreach ($versions as $versionName => $packageVersion) {
                    try {
                        $distName = $packageName . '/' . $versionName;
                        $dist = $packageVersion['dist'];
                        if (empty($dist)){
                            continue;
                        }
                        $path = 'dists/' . $packageName . '/' . $dist['reference'] . '.' . $dist['type'];
                        $exist = redis()->sIsMember(Code::distSet, $path);
                        if (empty($exist)) {
                            redis()->sAdd(Code::distQueue, json_encode([
                                'Path' => $path,
                                'Url' => $dist['url'],
                            ]));
                            redis()->sAdd(Code::versionsSet, $distName);
                            $getTodayKey = Code::versionsSet . '-' . date('Y-m-d');
                            redis()->sAdd($getTodayKey, $distName);
                            redis()->expireAt($getTodayKey, Carbon::tomorrow()->timestamp);
                        }
                    } catch (\Throwable $e) {
                        logger()->error('========error',$packageVersion);
                    }
                }
            }
        }
        std_logger()->info("[✓] sync package v1 success ". Code::packageP1Queue." count:".count($response['packages']));
        //TODO Warm cdn url
        $getTodayKey = Code::packageV1SetHash . '-' . date('Y-m-d');
        redis()->sAdd($getTodayKey, $job['Path']);
        redis()->expireAt($getTodayKey, Carbon::tomorrow()->timestamp);
        return true;
    }
}
