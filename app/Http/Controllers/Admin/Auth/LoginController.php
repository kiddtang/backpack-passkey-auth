<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Passkey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
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
}
