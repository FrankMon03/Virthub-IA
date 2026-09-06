<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

class TwoFactorService
{
    private Google2FA $totp;

    public function __construct()
    {
        $this->totp = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->totp->generateSecretKey();
    }

    public function qrCodeDataUri(string $username, string $secret): string
    {
        $uri = $this->totp->getQRCodeUrl('Virthub', $username, $secret);
        $renderer = new ImageRenderer(
            new RendererStyle(240),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($uri);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $code = trim($code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return $this->totp->verifyKey($secret, $code, 1);
    }

    public function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    public function decryptSecret(string $encryptedSecret): string
    {
        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo descifrar el secreto de 2FA.', 0, $exception);
        }
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }

        return $codes;
    }

    public function hashRecoveryCode(string $code): string
    {
        return Hash::make(strtoupper(trim($code)));
    }
}
