@section('after_scripts')
@basset('https://unpkg.com/@simplewebauthn/browser@10.0.0/dist/bundle/index.umd.min.js')
<script>
    const form = document.getElementById('passkey-form');
    const registrationOptions = {!! trim(json_encode(session('passkey_register_options'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!};

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Name validation with length check
        const name = document.querySelector('input[name="name"]').value.trim();
        if (!name || name.length < 1 || name.length > 255) {
            alert('Name must be between 1 and 255 characters');
            return;
        }

        try {
            const options = JSON.parse(registrationOptions);

            const attResp = await SimpleWebAuthnBrowser.startRegistration(options);

            // Add the attestation to the form
            document.getElementById('passkey').value = JSON.stringify(attResp);

            this.submit();
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to register passkey: ' + error.message);
        }
    });
</script>
@endsection

<div class="col-lg-8 mb-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Manage Passkeys</h3>
        </div>

        <div class="card-body backpack-profile-form bold-labels">
            {{-- REGISTERED PASSKEYS LIST --}}
            @if(count($passkeys ?? []) > 0)
                <div class="table-responsive">
                    <table class="table" style="width:100%">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($passkeys as $passkey)
                            <tr>
                                <td>{{ $passkey->name }}</td>
                                <td>{{ $passkey->created_at->diffForHumans() }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger">
                                        <i class="la la-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    No passkeys registered yet.
                </div>
            @endif

            {{-- REGISTER NEW PASSKEY --}}
            <form id="passkey-form" class="form mt-4" action="{{ route('backpack.passkey.create') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="required">Name</label>
                        <input required class="form-control" type="text" name="name" value="{{ old('name') }}"
                               placeholder="Enter a name for this passkey">
                    </div>
                </div>

                <input type="hidden" id="passkey" name="passkey" value="">

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="la la-key"></i> Register New Passkey
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
