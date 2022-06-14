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

class Code
{
    const packagesNoData = 'set:packages-nodata';

    const distsNoMetaKey = 'set:dists-meta-missing';

    const distSet = 'set:dists';

    const providerSet = 'set:providers';

    const packageV1Set = 'set:packagesV1';

    const packageV1SetHash = 'set:packagesV1-Hash';

    const packageV2Set = 'set:packagesV2';

    const versionsSet = 'set:versions';

    const distQueue = 'queue:dists';

    const distQueueRetry = 'queue:dists-Retry';

    const providerQueue = 'queue:providers';

    const packageP1Queue = 'queue:packagesV1';

    const packageV2Queue = 'queue:packagesV2';

    const lastUpdateTimeKey = 'key:last-update';

    const packagistLastModifiedKey = 'key:packagist-last-modified';

    const localStableComposerVersion = 'key:local-stable-composer-version';

    const v2LastUpdateTimeKey = 'key:v2-lastTimestamp';

    const packagesJsonKey = 'key:packages.json';
}
