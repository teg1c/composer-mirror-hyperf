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

use App\Service\Sync\ComposerPhar;
use App\Service\Sync\PackagesJsonFile;
use App\Service\Sync\Provider;
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
                make(ComposerPhar::class)->syncComposerPhar();
            },
            function () {
                // Synchronize packages.json
                make(PackagesJsonFile::class)->syncPackagesJsonFile();
            },
            function () {
                // Synchronize Meta for V2
                make(SyncV2::class)->v2();
            },
            function () {
                // Update status
                make(SyncStatus::class)->status();
            },
        ];
        $syncProviderCo = [];
        for ($i = 0; $i < 10; ++$i) {
            $syncProviderCo[] = function () {
                make(Provider::class)->syncProvider();
            };
        }
        $co = array_merge($co, $syncProviderCo);
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
