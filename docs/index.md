# omnicrawl/zyte

Zyte API (https://docs.zyte.com/zyte-api/usage/reference.html), read on 2026-11-10.

| Contract | Call | Body |
|---|---|---|
| page-fetch, extract | `POST https://api.zyte.com/v1/extract`, HTTP Basic (the key as user name, no password) | `url`, `httpResponseBody: true`, `httpResponseHeaders: true`, `followRedirect: true`, `customHttpRequestHeaders: [{"name": "User-Agent", "value": <the family's>}]` |

Read back: `statusCode` (the target's), `url` (the address that answered), `httpResponseBody`
(base64), `httpResponseHeaders` (a list of name and value).

- **Licensed targets only.** Zyte avoids bans by itself - CAPTCHA handling and residential IPs on
  by default, changed only through Zyte's support - and "may override" a custom User-Agent "for
  certain websites if needed for ban avoidance". The provider declares
  `evasion: ["ban-avoidance", "user-agent-override"]`; the gate refuses it
  (`provider-not-allowed`) for any target not declared as one's own site or with the owner's
  written permission, before Zyte is called.
- **No browser mode:** in it, only the Referer header can be overridden, so the family's
  User-Agent cannot be sent. Rendering is not offered.
- **Not exposed:** `browserHtml`, `screenshot`, `actions`, `ipType`, `geolocation`, sessions.
- **No crawl runs:** Zyte API documents no batch or asynchronous endpoint.
- **Policy** (`policy.json`): 3,000 requests a minute per key (and per-site limits Zyte does not
  publish); for internal business purposes; the legality of each use is the customer's.
