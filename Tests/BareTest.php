<?php

namespace Omnicrawl\Zyte\Tests;

use Omnicrawl\Zyte\ZyteProvider;
use Omnicrawl\Testing\BareTestCase;

/** omnicrawl/zyte's classes load with omnisense/omnicrawl, omnisense/contracts and Symfony's HTTP client contracts alone. */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return ZyteProvider::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }
}
