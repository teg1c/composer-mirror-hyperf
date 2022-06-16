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

use App\Service\Sync\SyncComposerPhar;
use App\Service\Sync\SyncDists;
use App\Service\Sync\SyncPackagesJsonFile;
use App\Service\Sync\SyncPackagesV1;
use App\Service\Sync\SyncPackagesV2;
use App\Service\Sync\SyncProvider;
use App\Service\Sync\SyncStatus;
use App\Service\Sync\SyncV2;

class Entry
{
    public function execute()
    {
        $test = $this->test();
        if ($test === false) {
            return;
        }
        std_logger()->info('test configurations successfully');

        $co = [
            function () {
                // Synchronize composer.phar
                make(SyncComposerPhar::class)->exec();
            },
            function () {
                // Synchronize packages.json
                make(SyncPackagesJsonFile::class)->exec();
            },
            function () {
                // Synchronize Meta for V2
                make(SyncV2::class)->exec();
            },
//            function () {
//                // Update status
//                make(SyncStatus::class)->exec();
//            },
        ];
        $syncProviderCo = [];
        for ($i = 0; $i < 10; ++$i) {
            $syncProviderCo[] = function () {
                make(SyncProvider::class)->exec();
            };
        }
        $co = array_merge($co, $syncProviderCo);

        $syncPackagesV1Co = [];
        for ($i = 0; $i < 10; ++$i) {
            $syncPackagesV1Co[] = function () {
                make(SyncPackagesV1::class)->exec();
            };
        }
        $co = array_merge($co, $syncPackagesV1Co);

        $syncPackagesV2Co = [];
        for ($i = 0; $i < 10; ++$i) {
            $syncPackagesV2Co[] = function () {
                make(SyncPackagesV2::class)->exec();
            };
        }
        $co = array_merge($co, $syncPackagesV2Co);
        $syncPackagesV2Co = [];
        for ($i = 0; $i < 1; ++$i) {
            $syncPackagesV2Co[] = function () {
                make(SyncDists::class)->exec();
            };
        }
        $co = array_merge($co, $syncPackagesV2Co);
        parallel($co);
    }

    private function test()
    {
        try {
            redis()->echo('redis echo');
            std_logger()->info('[✓] test redis passed');
            make(Github::class)->test();
            std_logger()->info('[✓] test github passed');
            make(AliyunOss::class)->test();
            std_logger()->info('[✓] test OSS passed');
        } catch (\Throwable $e) {
            std_logger()->error(sprintf('test error :%s', format_throwable($e)));
            return false;
        }
        return true;
    }
}
