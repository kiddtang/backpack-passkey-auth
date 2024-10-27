<?php

namespace App\Http\Controllers\Admin;

use App\Support\JsonSerializer;
use Illuminate\Support\Str;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class MyAccountController extends \Backpack\CRUD\app\Http\Controllers\MyAccountController
{
    /**
     * @throws InvalidDataException
     */
    public function getAccountInfoForm()
    {
        $this->data['title'] = trans('backpack::base.my_account');
        $this->data['user'] = $this->guard()->user();

        $this->data['passkeys'] = $this->guard()->user()->passkeys()->select(['id', 'name', 'created_at'])->get();

        session(['passkey_register_options' => $this->getRegisterOptions()]);

        return view(backpack_view('my_account'), $this->data);
    }

    /**
     * Generate WebAuthn registration options for credential creation.
     * Necessary data including relying party details, user information, and a random challenge.
     *
     * @return string JSON serialized PublicKeyCredentialCreationOptions
     *
     * @throws InvalidDataException
     */
    private function getRegisterOptions(): string
    {
        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST),
            ),
            user: new PublicKeyCredentialUserEntity(
                name: $this->guard()->user()->email,
                id: $this->guard()->user()->id,
                displayName: $this->guard()->user()->name,
            ),
            challenge: Str::random(),
        );

        return JsonSerializer::serialize($options);
    }
}
