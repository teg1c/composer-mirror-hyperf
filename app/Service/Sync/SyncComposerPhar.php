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
use App\Service\HttpRequest;
use Hyperf\Utils\Coroutine;

class SyncComposerPhar
{
    public HttpRequest $httpRequest;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function exec()
    {
        std_logger()->info('init sync composer.phar');
        while (true) {
            $this->sync();
            Coroutine::sleep(6000);
        }
    }

    private function sync(): bool
    {
        [$err,$versionsContentOrigin] = $this->httpRequest->request('https://getcomposer.org/versions');
        if ($err) {
            std_logger()->error(sprintf('get composer versions failed:%s', format_throwable($err)));
            return false;
        }
        $versionsContent = json_decode($versionsContentOrigin, true);
        if (empty($versionsContent)) {
            std_logger()->error('get composer versions failed');
            return false;
        }
        $stable = current($versionsContent['stable']);

        $localStableVersion = redis()->get(Code::localStableComposerVersion);
        if ($localStableVersion == $stable['version']) {
            std_logger()->error('The remote version is equals with local version, no need to anything');
            return true;
        }
        // about 2.4MB
        std_logger()->info('get composer.phar now');
        // Like https://getcomposer.org/download/1.9.1/composer.phar
        [$err,$composerPhar] = $this->httpRequest->request('https://getcomposer.org' . $stable['path']);
        if ($err) {
            std_logger()->error(sprintf('get composer phar failed:%s', format_throwable($err)));
            return false;
        }
        // Like https://getcomposer.org/download/1.9.1/composer.phar.sig
        [$err,$composerPharSig] = $this->httpRequest->request('https://getcomposer.org' . $stable['path'] . '.sig');
        if ($err) {
            std_logger()->error(sprintf('get stable composer.phar.sig failed:%s', format_throwable($err)));
            return false;
        }
        // Sync versions
        $oss = make(AliyunOss::class)->write('versions', $versionsContentOrigin, [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            std_logger()->error('put versions to OSS failed');
        }
        std_logger()->info('put composer.phar on OSS');
        $oss = make(AliyunOss::class)->write('composer.phar', $composerPhar);
        if ($oss === false) {
            std_logger()->error('put composer.phar failed');
        }

        $oss = make(AliyunOss::class)->write('download/' . $stable['version'] . '/composer.phar', $composerPhar);
        if ($oss === false) {
            std_logger()->error('put stable composer.phar failed');
        }
        $oss = make(AliyunOss::class)->write('composer.phar.sig', $composerPharSig, [
            'Content-Type' => 'application/json',
        ]);
        if ($oss === false) {
            std_logger()->error('put stable composer.phar.sig failed');
        }
        $oss = make(AliyunOss::class)->write('download/' . $stable['version'] . '/composer.phar.sig', $composerPharSig);
        if ($oss === false) {
            std_logger()->error('put stable(version) composer.phar.sig failed');
        }
        // The cache is updated only if the push is successful
        std_logger()->info('save stable composer version into local store');
        redis()->set(Code::localStableComposerVersion, $stable['version']);
        return true;
    }
}
