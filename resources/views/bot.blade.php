<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About the bot - {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.6; max-width: 46rem; margin: 0 auto; padding: 2rem 1rem; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .9em; }
        ul.ips { list-style: none; padding: 0; }
        ul.ips li { padding: .15rem 0; }
    </style>
</head>
<body>
    <h1>About the bot</h1>

    <p>
        This server runs the automated core updates service of the <a href="https://www.joomla.org">Joomla! project</a>.
        It only contacts Joomla sites whose administrators have opted in to automated updates. It checks
        their health and, when a new Joomla release is available, triggers the update on the site.
    </p>

    <h2>How to identify it</h2>
    <ul>
        <li>User-Agent: <code>Joomla.org Automated Updates Server</code></li>
        @if (config('web_bot_auth.enabled'))
            <li>
                Requests are signed with <a href="https://datatracker.ietf.org/wg/webbotauth/about/">Web Bot Auth</a>,
                the public key directory is published at
                <a href="{{ url('/.well-known/http-message-signatures-directory') }}"><code>/.well-known/http-message-signatures-directory</code></a>.
            </li>
        @endif
    </ul>

    <h2>IP addresses</h2>
    @if (count(config('autoupdates.bot_ips')))
        <p>Requests from this service originate from the following addresses:</p>
        <ul class="ips">
            @foreach (config('autoupdates.bot_ips') as $ip)
                <li><code>{{ $ip }}</code></li>
            @endforeach
        </ul>
    @else
        <p>No addresses are published at the moment.</p>
    @endif

    <h2>Opting out</h2>
    <p>
        Automated updates can be turned off at any time in the administrator backend of the Joomla site,
        in the options of the Joomla Update component. The site is then unregistered and no longer contacted.
    </p>
</body>
</html>
