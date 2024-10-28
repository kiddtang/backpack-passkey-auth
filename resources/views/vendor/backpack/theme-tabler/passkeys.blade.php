@section('after_scripts')
@basset('https://unpkg.com/@simplewebauthn/browser@10.0.0/dist/bundle/index.umd.min.js')
<script>
    const form = document.getElementById('passkey-form');
    const registrationOptions = {!! json_encode(\App\Support\JsonSerializer::serialize(session('passkey_register_options'))) !!};

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

    // Delete passkey function
    function deleteEntry(button) {
        var route = $(button).attr('data-route');

        swal({
            title: "{!! trans('backpack::base.warning') !!}",
            text: "{!! trans('backpack::crud.delete_confirm') !!}",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "{!! trans('backpack::crud.cancel') !!}",
                    value: null,
                    visible: true,
                    className: "bg-secondary",
                    closeModal: true,
                },
                delete: {
                    text: "{!! trans('backpack::crud.delete') !!}",
                    value: true,
                    visible: true,
                    className: "bg-danger",
                }
            },
            dangerMode: true,
        }).then((value) => {
            if (value) {
                $.ajax({
                    url: route,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (result) {
                        if (result == 1) {
                            // Remove the row from the table
                            $(button).closest('tr').fadeOut(function () {
                                $(this).remove();

                                // If no more rows, show the "no passkeys" message
                                if ($('table tbody tr').length === 0) {
                                    $('.table-responsive').replaceWith(
                                        '<div class="alert alert-info">You have no passkeys registered yet.</div>'
                                    );
                                }
                            });

                            // Show a success notification
                            new Noty({
                                type: "success",
                                text: "{!! '<strong>'.trans('backpack::crud.delete_confirmation_title').'</strong><br>'.trans('backpack::crud.delete_confirmation_message') !!}"
                            }).show();
                        } else {
                            // Show an error alert
                            swal({
                                title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                                text: "{!! trans('backpack::crud.delete_confirmation_not_message') !!}",
                                icon: "error",
                                timer: 4000,
                                buttons: false,
                            });
                        }
                    },
                    error: function (result) {
                        // Show an error alert
                        swal({
                            title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                            text: "{!! trans('backpack::crud.delete_confirmation_not_message') !!}",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                    }
                });
            }
        });
    }
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
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteEntry(this)"
                                            data-route="{{ route('backpack.passkey.delete', $passkey->id) }}">
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
