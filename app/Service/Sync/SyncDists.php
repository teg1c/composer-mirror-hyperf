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
use App\Service\Github;
use Carbon\Carbon;
use Hyperf\Utils\Coroutine;

class SyncDists
{
    public function exec()
    {
        while (true) {
            $jobJson = redis()->sPop(Code::distQueue);
            if (empty($jobJson)) {
                std_logger()->info('get no task from ' . Code::distQueue . ', sleep 1 second');
                Coroutine::sleep(1);
                continue;
            }
            $job = json_decode($jobJson, true);
            $this->uploadDist($job);
        }
    }

    private function uploadDist($job)
    {
        if (empty($job)) {
            return;
        }
        // Get information
        $path = $job['Path'];
        $url = $job['Url'];
        if (empty($url)) {
            std_logger()->error(sprintf('url is invalid  %s', json_encode($job)));
            return;
        }
        $getTodayKey = Code::distSet . '-' . date('Y-m-d');
        redis()->sAdd($getTodayKey, $path);
        redis()->expireAt($getTodayKey, Carbon::tomorrow()->timestamp);

        $isExist = make(AliyunOss::class)->isObjectExist($path);
        if ($isExist) {
            std_logger()->error(sprintf('object(%s) exists', $path));
            return;
        }
        //Get dist
        $dist = make(Github::class)->getDist($url);
        if (empty($dist)) {
            return;
        }
        //Put OSS
        $oss = make(AliyunOss::class)->write($path, $dist);
        if (empty($oss)) {
            std_logger()->error(sprintf('save %s on OSS failed', $path));
            return;
        }
        // TODO warm cdn url
    }
}
