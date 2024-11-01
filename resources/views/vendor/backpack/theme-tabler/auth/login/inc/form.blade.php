@section('after_scripts')
<script type="module">
    let input = document.querySelector('.password-visibility-toggler input');
    let buttons = document.querySelectorAll('.password-visibility-toggler a');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(b => b.classList.toggle('d-none'));
            input.type = input.type === 'password' ? 'text' : 'password';
            input.focus();
        });
    });
</script>
@basset('https://unpkg.com/@simplewebauthn/browser@10.0.0/dist/bundle/index.umd.min.js')
<script>
    // Setup passkey authentication
    const $emailInput = $(`input[name="{{ $username }}"]`);
    const $passkeyButton = $('#btn-passkey-auth');

    @if(!$valid_passkey_challenge)
    // Email validation for initial passkey button
    $emailInput.on('input', () => {
        const isValid = $emailInput.val().includes('@') && $emailInput.val().includes('.');
        $passkeyButton.prop('disabled', !isValid).toggleClass('d-none', !isValid);
    });

    // Initial check for pre-filled email
    $emailInput.trigger('input');

    // Handle passkey button click with form submision
    $passkeyButton.on('click', () => {
        $passkeyButton.prop('disabled', true);
        $('body').css('cursor', 'wait');

        $('<form>', {
            method: 'POST',
            action: '{{ route('passkey.login') }}',
            html: `
            <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
            <input type="hidden" name="email" value="${$emailInput.val()}">
        `
        }).appendTo('body').submit();
    });
    @else
    // Handle the actual authentication
    $passkeyButton.on('click', async () => {
        try {
            $passkeyButton.prop('disabled', true);
            $('body').css('cursor', 'wait');

            const authenticationOptions = {!! json_encode(\App\Support\JsonSerializer::serialize(session('passkey_authentication_options'))) !!};

            if (!authenticationOptions) {
                throw new Error('Authentication options not found');
            }

            // Start the authentication process
            const options = JSON.parse(authenticationOptions);
            const credential = await SimpleWebAuthnBrowser.startAuthentication(options);

            // Create and submit form with credential
            $('<form>', {
                method: 'POST',
                action: '{{ route('passkey.authenticate') }}',
                html: `
                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                <input type="hidden" name="answer" value='${JSON.stringify(credential)}'>
            `
            }).appendTo('body').submit();

        } catch (error) {
            console.error('Passkey authentication error:', error);
            $passkeyButton.prop('disabled', false);
            $('body').css('cursor', 'default');

            new Noty({
                type: 'error',
                text: 'Passkey authentication failed. Please try again.',
                timeout: 5000
            }).show();

            window.location.href = '{{ backpack_url() }}';
        }
    });
    @endif
</script>
@endsection
<h2 class="h2 text-center my-4">{{ trans('backpack::base.login') }}</h2>
<form method="POST" action="{{ route('backpack.auth.login') }}" autocomplete="off" novalidate="">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="{{ $username }}">{{ trans('backpack::base.'.strtolower(config('backpack.base.authentication_column_name'))) }}</label>
        <input autofocus tabindex="1" type="text" name="{{ $username }}" value="{{ old($username) }}" id="{{ $username }}" class="form-control {{ $errors->has($username) ? 'is-invalid' : '' }}" {{ $valid_passkey_challenge ? 'disabled' : '' }}>
        @if ($errors->has($username))
            <div class="invalid-feedback">{{ $errors->first($username) }}</div>
        @endif
    </div>
    <div class="mb-2 {{ $valid_passkey_challenge ? 'd-none' : '' }}">
        <label class="form-label" for="password">
            {{ trans('backpack::base.password') }}
        </label>
        <div class="input-group input-group-flat password-visibility-toggler">
            <input tabindex="2" type="password" name="password" id="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" value="">
            @if(backpack_theme_config('options.showPasswordVisibilityToggler'))
            <span class="input-group-text p-0 px-2">
                <a href="#" data-input-type="text" class="link-secondary p-2" data-bs-toggle="tooltip" aria-label="{{ trans('backpack.theme-tabler::theme-tabler.password-show') }}" data-bs-original-title="{{ trans('backpack.theme-tabler::theme-tabler.password-show') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                </a>
                <a href="#" data-input-type="password" class="link-secondary p-2 d-none" data-bs-toggle="tooltip" aria-label="{{ trans('backpack.theme-tabler::theme-tabler.password-hide') }}" data-bs-original-title="{{ trans('backpack.theme-tabler::theme-tabler.password-hide') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                </a>
            </span>
            @endif
        </div>
        @if ($errors->has('password'))
            <div class="invalid-feedback">{{ $errors->first('password') }}</div>
        @endif
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2 {{ $valid_passkey_challenge ? 'd-none' : '' }}">
        <label class="form-check mb-0">
            <input name="remember" tabindex="3" type="checkbox" class="form-check-input">
            <span class="form-check-label">{{ trans('backpack::base.remember_me') }}</span>
        </label>
        @if (backpack_users_have_email() && backpack_email_column() == 'email' && config('backpack.base.setup_password_recovery_routes', true))
            <div class="form-label-description">
                <a tabindex="4" href="{{ route('backpack.auth.password.reset') }}">{{ trans('backpack::base.forgot_your_password') }}</a>
            </div>
        @endif
    </div>
    <div class="form-footer">
        <button tabindex="5" id="btn-passkey-auth" type="button"
                class="btn w-100 mb-2 {{ $valid_passkey_challenge ? 'btn-primary' : 'd-none btn-success' }}">
            {{ $valid_passkey_challenge ? 'Login with passkey' : 'I\'ve passkey registered!' }}
        </button>
        <button tabindex="5" type="submit" class="btn btn-primary w-100 {{ $valid_passkey_challenge ? 'd-none' : '' }}">{{ trans('backpack::base.login') }}</button>
    </div>
</form>
