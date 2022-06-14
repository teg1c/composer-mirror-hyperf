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

use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\FileExistsException;

class AliyunOss
{
    public FilesystemFactory  $factory;

    public function __construct(FilesystemFactory $factory)
    {
        $this->factory = $factory;
    }

    public function write($path,$content,$config = [])
    {
        try {
            $this->factory->get(config('file.default'))->write($path, $content,$config);
        } catch (\Throwable $e) {
            std_logger()->error(sprintf("put file to OSS failed: %s",$e->getMessage()));
            return false;
        }
        return $path;
    }
    public function test()
    {
        return $this->factory->get(config('file.default'))->listContents('/');
    }
}
