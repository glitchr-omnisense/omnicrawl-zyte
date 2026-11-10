<?php

namespace Omnicrawl\Zyte;

use Omnicrawl\Agent;
use Omnicrawl\Config;
use Omnicrawl\Http\JsonClient;
use Omnicrawl\ProviderFactory;
use Omnicrawl\ProviderInterface;
use Omnicrawl\Secrets\EnvSecrets;
use Omnicrawl\Secrets\SecretsInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * "zyte": Zyte API. Options: api_key (the REFERENCE of the key,
 * "ZYTE_API_KEY", never the key), user_agent (the registry gives the
 * gate's), base_uri (https://api.zyte.com/v1), timeout (seconds, 200);
 * policy, usage.
 */
final class ZyteFactory extends ProviderFactory
{
    private readonly SecretsInterface $secrets;

    public function __construct(private readonly ?HttpClientInterface $http = null, ?SecretsInterface $secrets = null)
    {
        $this->secrets = $secrets ?? new EnvSecrets();
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnicrawl.factory_name' => 'zyte',
            'omnicrawl.factory_title' => 'Zyte API',
            'omnicrawl.required_options' => ['api_key', 'user_agent'],
            'base_uri' => ZyteProvider::BASE_URI,
            'timeout' => 200,
        ]);
    }

    protected function build(Config $c, Agent $agent): ProviderInterface
    {
        $http = ($this->http ?? HttpClient::create())->withOptions(['timeout' => $c->float('timeout', 200.0)]);
        // HTTP Basic: the key as the user name, an empty password.
        $auth = 'Basic '.base64_encode($this->secrets->get((string) $c->string('api_key')).':');

        return new ZyteProvider(new JsonClient($http, 'Zyte API', ['Authorization' => $auth, 'Accept' => 'application/json'], $c->backoff()), rtrim((string) $c->string('base_uri'), '/'));
    }
}
