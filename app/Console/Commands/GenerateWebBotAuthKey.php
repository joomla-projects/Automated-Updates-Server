<?php

namespace App\Console\Commands;

use App\WebBotAuth\Ed25519Key;
use Illuminate\Console\Command;

class GenerateWebBotAuthKey extends Command
{
    /**
     * @var string
     */
    protected $signature = 'web-bot-auth:generate-key {--force : Overwrite an existing key}';

    /**
     * @var string
     */
    protected $description = 'Generate the Ed25519 key used to sign outgoing requests with Web Bot Auth';

    public function handle(): int
    {
        $path = config()->string('web_bot_auth.key_path');

        if (file_exists($path) && !$this->option('force')) {
            $this->error('Key already exists at ' . $path . ', use --force to replace it');

            return self::FAILURE;
        }

        $key = Ed25519Key::generate();

        $oldUmask = umask(0077);
        $written = file_put_contents($path, json_encode($key->privateJwk(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        umask($oldUmask);

        if ($written === false) {
            $this->error('Unable to write key to ' . $path);

            return self::FAILURE;
        }

        chmod($path, 0600);

        $this->info('Private key written to ' . $path . ' - keep it secret, do not commit it');
        $this->line('keyid: ' . $key->thumbprint());
        $this->line('Public JWK: ' . json_encode($key->publicJwk(), JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
