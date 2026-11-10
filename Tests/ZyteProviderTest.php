<?php

namespace Omnicrawl\Zyte\Tests;

use Omnicrawl\Agent;
use Omnicrawl\Exception\NotSupportedException;
use Omnicrawl\Exception\RefusedException;
use Omnicrawl\Exception\TargetRefusedException;
use Omnicrawl\Guard\Gate;
use Omnicrawl\Registry;
use Omnicrawl\Secrets\ArraySecrets;
use Omnicrawl\Target\Targets;
use Omnicrawl\Testing\RecordedHttpClient;
use Omnicrawl\Zyte\ZyteFactory;
use PHPUnit\Framework\TestCase;

/** Zyte API's /extract as its reference writes it, answered from recordings: no live call. Its ban avoidance keeps it to licensed targets. */
final class ZyteProviderTest extends TestCase
{
    private const UA = 'ExampleBot/1.0 (+https://example.org/bot; bot@example.org)';

    private static function registry(RecordedHttpClient $api): Registry
    {
        $site = RecordedHttpClient::routes(['~/robots\.txt$~' => [404, '']]);
        $targets = new Targets([
            ['target' => 'shop.example.org', 'collection' => 'allowed', 'authority' => 'own-site', 'reference' => 'https://shop.example.org', 'date' => '2026-11-10', 'rateLimits' => [['max' => 30, 'window' => 'minute']]],
            ['target' => 'press.example.net', 'collection' => 'allowed', 'authority' => 'published-terms', 'reference' => 'https://press.example.net/terms#bots', 'date' => '2026-11-10'],
        ]);

        return new Registry([new ZyteFactory($api, new ArraySecrets(['ZYTE_API_KEY' => 'not-a-real-key']))], ['zyte' => ['factory' => 'zyte', 'options' => ['api_key' => 'ZYTE_API_KEY']]], new Gate(Agent::parse(self::UA), $site, $targets));
    }

    public function testOnesOwnPageOverHttp(): void
    {
        $api = RecordedHttpClient::routes(['~^POST https://api\.zyte\.com/v1/extract$~' => [200, '@'.__DIR__.'/Fixtures/extract.json']]);

        $record = self::registry($api)->pageFetch('zyte')->fetch('https://shop.example.org/products/1');

        $sent = json_decode(RecordedHttpClient::body($api->requests[0]['options']), true);
        self::assertSame(['url' => 'https://shop.example.org/products/1', 'httpResponseBody' => true, 'httpResponseHeaders' => true, 'followRedirect' => true, 'customHttpRequestHeaders' => [['name' => 'User-Agent', 'value' => self::UA.' omnicrawl/1']]], $sent);
        self::assertSame('Basic '.base64_encode('not-a-real-key:'), $api->header('Authorization'));
        self::assertSame(200, $record->status);
        self::assertStringContainsString('<title>Product 1</title>', $record->body);
        self::assertTrue($record->signals->noarchive());
        self::assertTrue($record->signals->tdm->reserved, 'a page fetched under a licence keeps its reservation on the record');
    }

    public function testAnythingButALicenceIsRefusedBeforeZyteIsCalled(): void
    {
        $api = RecordedHttpClient::routes([]);
        $registry = self::registry($api);

        foreach (['https://news.example.com/1', 'https://press.example.net/release'] as $url) {
            try {
                $registry->pageFetch('zyte')->fetch($url);
                self::fail('Zyte was used for '.$url);
            } catch (TargetRefusedException $e) {
                self::assertSame(RefusedException::REASON_PROVIDER, $e->reason);
                self::assertStringContainsString('ban-avoidance', $e->getMessage());
            }
        }
        self::assertSame([], $api->requests);
        self::assertSame(['ban-avoidance', 'user-agent-override'], $registry->get('zyte')->capabilities()->evasion);
    }

    public function testNoBrowserMode(): void
    {
        $this->expectException(NotSupportedException::class);
        self::registry(RecordedHttpClient::routes([]))->pageFetch('zyte')->fetch('https://shop.example.org/', render: true);
    }

    public function testThePolicy(): void
    {
        $policy = self::registry(RecordedHttpClient::routes([]))->policy('zyte');

        self::assertSame(['3000/minute/application/*'], array_map(static fn ($l) => $l->id(), $policy->rateLimits ?? []));
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
    }
}
