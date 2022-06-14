<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Entry;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class MirrorSyncCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('composer:sync');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('composer packagist sync Command');
    }

    public function handle()
    {

        $entry = new Entry();
        $entry->execute();
        $this->line('Done !', 'info');
    }
}
