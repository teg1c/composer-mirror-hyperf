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
use Hyperf\Utils\Coroutine;

class SyncPackagesV2
{
    public function exec()
    {
        while (true) {
            $jobJson = redis()->sPop(Code::packageV2Queue);
            if (empty($jobJson)) {
                std_logger()->info('get no task from ' . Code::packageV2Queue . ', sleep 1 second');
                Coroutine::sleep(1);
                continue;
            }

            try {
                $job = json_decode($jobJson, true);
                $this->syncPackage($job);
            } catch (\Throwable $e) {
                logger()->error("SyncPackagesV2 error:".format_throwable($e));
            }
        }
    }

    private function syncPackage($job): void
    {
        if (empty($job)) {
            return;
        }
        $actionType = $job['type'];
        if ($actionType == 'update') {
            $this->updatePackageV2($job);
            return;
        }
        if ($actionType == 'delete') {
            $this->deletePackageV2($job);
            return;
        }
        std_logger()->error(sprintf('unsupported action: %s', $actionType));
    }

    private function updatePackageV2($job): bool
    {
        $packageName = $job['package'];
        $content = make(Packagist::class)->getPackage($packageName);
        if (empty($content)) {
            return false;
        }
        //json decode
        $packageJson = json_decode($content, true);
        if (empty($packageJson)) {
            std_logger()->error('JSON Decode Error:' . $packageName);
            return false;
        }
        if (empty($packageJson['minified'])) {
            std_logger()->error('package field not found: minified: ' . $packageName);
            return false;
        }
        // Put to OSS
        $oss = make(AliyunOss::class)->write('p2/' . $packageName . '.json', $content, [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            return false;
        }
        redis()->hSet(Code::packageV2Set, $packageName, $job['time']);
        //TODO Warm cdn url

        return true;
    }

    private function deletePackageV2($job): bool
    {
        $packageName = $job['package'];
        $path = 'p2/' . $packageName . '.json';
        make(AliyunOss::class)->delete($path);
        redis()->hDel(Code::packageV2Set, $path);
        return true;
    }
}
