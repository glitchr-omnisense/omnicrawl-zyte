# omnicrawl/zyte

Zyte API for [omnisense/omnicrawl](https://github.com/glitchr-omnisense/omnicrawl), under any PHP host.
Pages over HTTP (POST /v1/extract) with the target's headers - for targets declared as one's own site or with written permission only, because Zyte's ban avoidance cannot be turned off.

It is reached through omnicrawl's gate only: every address is admitted first (the target's
declaration, robots.txt read by the core, TDM reservations, the host's limits), the service is
called after, and what it brings back is checked again and made a record with its provenance.
Read [what omnicrawl refuses to do, and why](https://github.com/glitchr-omnisense/omnicrawl/blob/1.x/docs/refusals.md).

```php
$registry = new Registry([new ZyteFactory($http)], [
    'zyte' => ['factory' => 'zyte', 'options' => ['api_key' => 'ZYTE_API_KEY']],
], $gate);
```

The key goes by reference: `ZYTE_API_KEY` names an environment variable (or a secret of the
`SecretsInterface` given to the factory), never the key itself. Its usage policy is
`policy.json` (limits, terms, what they say of lawful use), read on the day it says. See
[docs/index.md](docs/index.md).

Tested on recorded answers written from the service's documentation; no test calls the service.

MIT licence.
