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
use Carbon\Carbon;
use Hyperf\Utils\Coroutine;

class SyncStatus
{
    public function exec(): bool
    {
        std_logger()->info('start to sync status');
        while (true) {
            Coroutine::sleep(1);
            // Format: Tue, 14 Jun 2022 09:03:17 GMT
            $packagistLastModified = redis()->get(Code::packagistLastModifiedKey);
            // Format: 2006-01-02 15:04:05
            $lastUpdateTime = redis()->get(Code::lastUpdateTimeKey);

            $packagistLastModifiedTimestamp = Carbon::parse($packagistLastModified)->timestamp;
            $aliDateTimeTimestamp = strtotime($lastUpdateTime);

            $interval = $aliDateTimeTimestamp - $packagistLastModifiedTimestamp;
            $status = [
                'Delayed' => 0,
                'Interval' => $interval,
            ];
            if ($interval < 0) {
                $status['Title'] = 'Delayed ' . abs($interval) . ' Seconds, waiting for updates...';
                $status['Delayed'] = abs($interval);
                if (abs($interval) > 600) {
                    $status['ShouldReportDelay'] = true;
                }
            } else {
                $status['Title'] = 'Synchronized within ' . abs($interval) . ' Seconds!';
                $status['ShouldReportDelay'] = false;
            }

            $content = [
                'Last_Update' => [
                    'AliComposer' => $lastUpdateTime,
                    'Packagist' => Carbon::parse($packagistLastModified)->toDateTimeString(),
                ],
                'Queue' => [
                    'Providers' => redis()->sCard(Code::providerQueue),
                    'Packages' => redis()->sCard(Code::packageP1Queue) + redis()->sCard(Code::packageV2Queue),
                    'Dists' => redis()->sCard(Code::distQueue),
                    'DistsRetry' => redis()->sCard(Code::distQueueRetry),
                ],
                'Today_Updated' => [
                    'Dists' => redis()->SCard(Code::distSet . '-' . date('Y-m-d')),
                    'Packages' => redis()->SCard(Code::packageV1Set . '-' . date('Y-m-d')),
                    'PackagesHashFile' => redis()->SCard(Code::packageV1SetHash . '-' . date('Y-m-d')),
                    'ProvidersHashFile' => redis()->SCard(Code::providerSet . '-' . date('Y-m-d')),
                    'Versions' => redis()->SCard(Code::versionsSet . '-' . date('Y-m-d')),
                ],
            ];
            $status['Content'] = $content;
            $status['CacheSeconds'] = 30;
            $status['UpdateAt'] = date('Y-m-d H:i:s');
            // Sync versions
            $oss = make(AliyunOss::class)->write('status.json', json_encode($status, JSON_UNESCAPED_UNICODE), [
                'Content-Type' => 'application/json',
            ]);
            if ($oss === false) {
                std_logger()->error('put status.json to OSS failed');
            }
            return true;
        }
    }
}
