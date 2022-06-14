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

use App\Service\Code;
use App\Service\HttpRequest;
use App\Service\Packagist;
use Hyperf\Utils\Coroutine;

class SyncV2
{
    public HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function v2()
    {
        std_logger()->info('init sync v2');
        while (true) {
            $this->sync();
            Coroutine::sleep(5);
        }
    }

    private function sync()
    {
        $lastTimestamp = redis()->get(Code::v2LastUpdateTimeKey);
        if (empty($lastTimestamp)) {
            $lastTimestamp = make(Packagist::class)->getInitTimestamp();
            if ($lastTimestamp === false) {
                return false;
            }
            $syncAll = $this->syncAll();
            if ($syncAll == false) {
                return false;
            }
        }
        $changes = make(Packagist::class)->getMetadataChanges($lastTimestamp);
        if (empty($changes)) {
            return false;
        }
        // Dispatch changes
        if ($changes['timestamp'] == $lastTimestamp || empty($changes['actions'])) {
            // No changes
            return true;
        }
        foreach ($changes['actions'] as $item) {
            $packageName = $item['package'];
            $updateTime = $item['time'];
            $storedUpdateTime = redis()->hGet(Code::packageV2Set, $packageName);
            if ($storedUpdateTime != $updateTime) {
                //push to queue:packagesV2
                redis()->sAdd(Code::packageV2Queue, json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        }
        redis()->set(Code::v2LastUpdateTimeKey,$changes['timestamp']);
        return true;
    }

    private function syncAll()
    {
        $content = make(Packagist::class)->getAllPackages();
        if (empty($content)) {
            return false;
        }
        foreach ($content['packageNames'] as $packageName) {
            redis()->sAdd(Code::packageV2Queue, json_encode([
                'Type' => 'update',
                'Package' => $packageName,
                'Time' => 0,
            ]), JSON_UNESCAPED_UNICODE);
            redis()->sAdd(Code::packageV2Queue, json_encode([
                'Type' => 'update',
                'Package' => $packageName . '~dev',
                'Time' => 0,
            ]), JSON_UNESCAPED_UNICODE);
        }
        return true;
    }
}
