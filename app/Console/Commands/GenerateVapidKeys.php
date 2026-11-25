<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('together:push-keys')]
#[Description('Generate VAPID keys for Together web push notifications')]
class GenerateVapidKeys extends Command
{
    public function handle(): int
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if ($key === false || ! openssl_pkey_export($key, $privatePem)) {
            $this->error('OpenSSL could not generate VAPID keys.');

            return self::FAILURE;
        }

        $details = openssl_pkey_get_details($key);
        $publicKey = rtrim(strtr(base64_encode("\x04".$details['ec']['x'].$details['ec']['y']), '+/', '-_'), '=');
        $privateKey = base64_encode($privatePem);

        $this->line('Add these values to your environment configuration:');
        $this->newLine();
        $this->line('TOGETHER_VAPID_PUBLIC_KEY='.$publicKey);
        $this->line('TOGETHER_VAPID_PRIVATE_KEY='.$privateKey);
        $this->line('TOGETHER_VAPID_SUBJECT=mailto:you@example.com');
        $this->newLine();
        $this->warn('Keep the private key secret. Do not commit it.');

        return self::SUCCESS;
    }
}
