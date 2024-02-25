<?php

namespace Spatie\UrlSigner;

/**
 * @deprecated Use {@see Sha256UrlSigner} instead
 */
class Md5UrlSigner extends BaseUrlSigner
{
    protected function createSignature(
        string $url,
        string $expiration,
        string $signatureKey
    ): string {
        return md5("{$url}::{$expiration}::{$signatureKey}");
    }
}
