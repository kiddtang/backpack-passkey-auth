<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Passkey;
use App\Support\JsonSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class LoginController extends \Backpack\CRUD\app\Http\Controllers\Auth\LoginController
{
    /**
     * Extend the application's login form.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showLoginForm()
    {
        $this->data['title'] = trans('backpack::base.login'); // set the page title
        $this->data['username'] = $this->username();

        $this->data['valid_passkey_challenge'] = Session::has('passkey_authentication_options');

        // Only keep passkey authentication options if username exists
        if (old($this->username())) {
            Session::keep('passkey_authentication_options');
        }

        return view(backpack_view('auth.login'), $this->data);
    }

    public function authenticateOptions(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $allowedCredentials = $request->query('email')
            ? Passkey::whereRelation('user', 'email', $validated['email'])
                ->get()
                ->map(fn (Passkey $passkey) => $passkey->data)
                ->map(fn (PublicKeyCredentialSource $publicKeyCredentialSource) => $publicKeyCredentialSource->getPublicKeyCredentialDescriptor())
                ->all()
            : [];

        $options = new PublicKeyCredentialRequestOptions(
            challenge: Str::random(),
            rpId: parse_url(config('app.url'), PHP_URL_HOST),
            allowCredentials: $allowedCredentials,
        );

        Session::flash('passkey_authentication_options', $options);

        return redirect()->back()
            ->withInput(['email' => $validated['email']]);
    }

    public function authenticatePasskey(Request $request)
    {
        $validated = $request->validate([
            'answer' => ['required', 'json'],
        ]);

        // Deserialize the answer from the request
        $publicKeyCredential = JsonSerializer::deserialize($validated['answer'], PublicKeyCredential::class);

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return redirect()->guest(backpack_url('login'));
        }

        $passkey = Passkey::firstWhere('credential_id', $publicKeyCredential->rawId);

        if (! $passkey) {
            throw ValidationException::withMessages(['email' => 'The passkey is invalid.']);
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create(
                (new CeremonyStepManagerFactory)->requestCeremony()
            )->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: Session::get('passkey_authentication_options'),
                host: $request->getHost(),
                userHandle: null,
            );
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'email' => 'The passkey is invalid.',
            ]);
        }

        $passkey->update(['data' => $publicKeyCredentialSource]);

        // Login the user
        $this->guard()->loginUsingId($passkey->user_id);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }
}
