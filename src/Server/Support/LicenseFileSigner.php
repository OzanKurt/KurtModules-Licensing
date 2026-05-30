<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Kurt\Modules\Licensing\Crypto\ClaimsCodec;
use Kurt\Modules\Licensing\Crypto\Ed25519;
use Kurt\Modules\Licensing\Server\Exceptions\LicensingException;

/**
 * Builds the downloadable, Ed25519-signed license file. The file is fully
 * self-contained: the client verifies it offline using only the embedded
 * public key, with no call back to the server. The signature covers the
 * canonical encoding of `claims`, so any tampering invalidates it.
 */
final class LicenseFileSigner
{
    public const FORMAT = 'licensing-file/1';

    public function __construct(
        private readonly string $secretKey,
        private readonly string $publicKey,
    ) {}

    /**
     * @param  array<string, mixed>  $claims
     * @return array{format: string, public_key: string, claims: array<string, mixed>, signature: string}
     */
    public function sign(array $claims): array
    {
        if ($this->secretKey === '') {
            throw new LicensingException('No signing key configured (licensing.signing_key).');
        }

        return [
            'format' => self::FORMAT,
            'public_key' => $this->publicKey,
            'claims' => $claims,
            'signature' => Ed25519::sign(ClaimsCodec::encode($claims), $this->secretKey),
        ];
    }

    /**
     * Encode the signed file as a single base64 blob suitable for a `.lic`
     * download or copy-paste into a config value.
     *
     * @param  array<string, mixed>  $claims
     */
    public function toBlob(array $claims): string
    {
        $json = json_encode($this->sign($claims), JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new LicensingException('Unable to encode signed license file.');
        }

        return base64_encode($json);
    }
}
