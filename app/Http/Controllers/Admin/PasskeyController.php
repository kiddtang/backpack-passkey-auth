<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\JsonSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Prologue\Alerts\Facades\Alert;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

class PasskeyController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $user = $this->guard()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'between:1,255'],
            'passkey' => ['required', 'json'],
        ]);

        // Deserialize the public key credential from the request
        $publicKeyCredential = JsonSerializer::deserialize($validated['passkey'], PublicKeyCredential::class);

        // Deserialize the creation options from the session
        $publicKeyCredentialCreationOptions = JsonSerializer::deserialize(
            session('passkey_register_options'),
            PublicKeyCredentialCreationOptions::class
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            return redirect()->guest(backpack_url('login'));
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create(
                (new CeremonyStepManagerFactory)->creationCeremony(),
            )->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $publicKeyCredentialCreationOptions,
                host: $request->getHost(),
            );
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'name' => 'The given passkey is invalid.',
            ])->errorBag('createPasskey');
        }

        $result = $user->passkeys()->create([
            'name' => $validated['name'],
            'data' => $publicKeyCredentialSource,
        ]);

        if ($result) {
            Alert::success('Passkey created successfully')->flash();
        } else {
            Alert::error(trans('Failed to create Passkey'))->flash();
        }

        return redirect()->back();
    }

    protected function guard()
    {
        return backpack_auth();
    }
}