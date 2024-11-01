<?php

/**
 * WebAuthn JSON Serializer
 *
 * Originally from Laracasts "Adding Passkeys to Your Laravel App" course
 *
 * @author Luke Raymond Downing <github.com/lukeraymonddowning>
 *
 * @source https://github.com/laracasts/adding-passkeys-to-your-laravel-app/blob/v5/app/Support/JsonSerializer.php
 */

namespace App\Support;

use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

class JsonSerializer
{
    public static function serialize(object $data): string
    {
        return (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->serialize($data, 'json');
    }

    /**
     * @template TReturn
     *
     * @param  class-string<TReturn>  $into
     * @return TReturn
     */
    public static function deserialize(string $json, string $into)
    {
        return (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($json, $into, 'json');
    }
}
