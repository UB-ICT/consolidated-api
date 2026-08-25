<?php

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Generate VAPID public and private keys for Web Push notifications';

    public function handle(): int
    {
        // Generate an EC key pair on the P-256 curve (required by the Web Push spec).
        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$key) {
            $this->error('Failed to generate EC key pair. Ensure OpenSSL is available.');

            return self::FAILURE;
        }

        $details = openssl_pkey_get_details($key);

        // The raw 32-byte private key scalar is stored in $details['ec']['d'].
        $privateKey = $this->base64UrlEncode($details['ec']['d']);

        // The uncompressed public key is 0x04 || x || y (65 bytes total).
        $publicKey = $this->base64UrlEncode("\x04" . $details['ec']['x'] . $details['ec']['y']);

        $this->line('');
        $this->info('VAPID keys generated successfully.');
        $this->line('');
        $this->line('Add the following to your <comment>.env</comment> file:');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY=' . $publicKey);
        $this->line('VAPID_PRIVATE_KEY=' . $privateKey);
        $this->line('');

        return self::SUCCESS;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
