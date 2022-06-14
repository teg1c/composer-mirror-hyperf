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
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Psr\Log\LoggerInterface;

if (! function_exists('di')) {
    /**
     * Finds an entry of the container by its identifier and returns it.
     * @param null $id
     * @return mixed|\Psr\Container\ContainerInterface
     */
    function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }

        return $container;
    }
}
if (! function_exists('logger')) {
    /**
     * @param string $name
     * @param string $group
     */
    function logger($name = 'info', $group = 'default'): LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }
}

if (! function_exists('std_logger')) {
    function std_logger()
    {
        return ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
    }
}
if (! function_exists('format_throwable')) {
    /**
     * Format a throwable to string.
     */
    function format_throwable(Throwable $throwable): string
    {
        return ApplicationContext::getContainer()->get(FormatterInterface::class)->format($throwable);
    }
}
if (! function_exists('redis')) {
    /**
     * RedisFactory get redis.
     * @param string $pullName
     * @return null|\Hyperf\Redis\RedisProxy|Redis
     */
    function redis(string $pullName = 'default')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($pullName);
    }
}
