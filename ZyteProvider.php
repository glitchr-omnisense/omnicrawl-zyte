<?php

namespace Omnicrawl\Zyte;

use Omnicrawl\Capabilities;
use Omnicrawl\Exception\NotSupportedException;
use Omnicrawl\Exception\ProviderException;
use Omnicrawl\FetcherInterface;
use Omnicrawl\Http\JsonClient;
use Omnicrawl\Model\FetchRequest;
use Omnicrawl\Model\Fetched;
use Omnisense\Contracts\Policy\DeclaresUsagePolicyInterface;
use Omnisense\Contracts\Policy\UsagePolicy;

/**
 * Zyte API, as the family uses it: POST /extract for the target's HTTP
 * response - its body (httpResponseBody, base64), its headers
 * (httpResponseHeaders), its status (statusCode) and final address (url) -
 * with the family's User-Agent in customHttpRequestHeaders.
 *
 * Zyte avoids bans by itself and that cannot be switched off per request
 * (its documentation: "By default, Zyte API may use different techniques
 * to avoid bans"; changing it goes through Zyte's support), and it "may
 * override" a custom User-Agent "for certain websites if needed for ban
 * avoidance". The provider declares both as evasion: the gate lets it
 * fetch only targets declared under a licence - one's own site, or a site
 * whose owner gave written permission -, never an undeclared site or one
 * collected on its terms or robots.txt alone. Its browser mode is not
 * used: it can only override the Referer header, not send the family's
 * User-Agent, so rendering is not offered. Not used either: ipType,
 * geolocation, sessions, actions.
 */
final class ZyteProvider implements FetcherInterface, DeclaresUsagePolicyInterface
{
    public const BASE_URI = 'https://api.zyte.com/v1';

    public const POLICY = __DIR__.'/policy.json';

    private static ?UsagePolicy $policy = null;

    public function __construct(private readonly JsonClient $client, private readonly string $base = self::BASE_URI)
    {
    }

    public function getName(): string
    {
        return 'zyte';
    }

    public function getTitle(): string
    {
        return 'Zyte API';
    }

    public function usagePolicy(): UsagePolicy
    {
        return self::$policy ??= UsagePolicy::fromFile(self::POLICY);
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(pageFetch: true, headers: true, evasion: ['ban-avoidance', 'user-agent-override'], policy: $this->usagePolicy());
    }

    public function fetch(FetchRequest $request): Fetched
    {
        if ($request->render) {
            throw NotSupportedException::call('Zyte API', 'render pages with the family\'s User-Agent (its browser mode overrides the Referer only)');
        }
        $data = $this->client->request('POST', $this->base.'/extract', ['json' => [
            'url' => $request->url,
            'httpResponseBody' => true,
            'httpResponseHeaders' => true,
            'followRedirect' => true,
            'customHttpRequestHeaders' => [['name' => 'User-Agent', 'value' => $request->agent->header()]],
        ]]) ?? throw ProviderException::answered('Zyte API', 'POST /extract', 404, 'No answer.');
        $status = $data->int('statusCode') ?? throw ProviderException::answered('Zyte API', 'POST /extract', 200, 'No statusCode in the answer.');
        $body = base64_decode($data->string('httpResponseBody') ?? '', true);
        $headers = $data->get('httpResponseHeaders');
        $normalised = Fetched::normaliseHeaders(\is_array($headers) ? $headers : []);

        return new Fetched($request->url, $data->string('url') ?? $request->url, $status, false === $body ? '' : $body, $normalised, false, str_contains(strtolower($normalised['content-type'][0] ?? 'text/html'), 'html'));
    }
}
