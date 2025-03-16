@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Register') }}</div>

                <div class="card-body">
                    <form id="register-form">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>
                            <div class="col-md-6">
                                <input id="name" type="text" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                <span class="invalid-feedback" role="alert" id="error-name"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>
                            <div class="col-md-6">
                                <input id="email" type="email" 
                                    class="form-control @error('email') is-invalid @enderror" 
                                    name="email" value="{{ old('email') }}" required autocomplete="email">
                                <span class="invalid-feedback" role="alert" id="error-email"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>
                            <div class="col-md-6">
                                <input id="password" type="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    name="password" required autocomplete="new-password">
                                <span class="invalid-feedback" role="alert" id="error-password"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>
                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" 
                                    name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="success-message" class="alert alert-success mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX script -->
<script>
document.getElementById('register-form').addEventListener('submit', async function(e) {
    e.preventDefault(); // Prevent the default form submission

    // Clear previous errors
    document.getElementById('error-name').innerText = '';
    document.getElementById('error-email').innerText = '';
    document.getElementById('error-password').innerText = '';
    document.getElementById('success-message').style.display = 'none';

    // Prepare form data
    const formData = new FormData(this);

    // Send the POST request to your API endpoint
    const response = await fetch("{{ route('api.register') }}", {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    });

    const data = await response.json();

    if(response.ok) {
        // Registration successful; display a success message
        document.getElementById('success-message').innerText = data.message;
        document.getElementById('success-message').style.display = 'block';
        // Optionally, redirect or update the UI
    } else {
        // Display validation errors if any
        const errors = data.errors;
        if(errors) {
            if(errors.name) {
                document.getElementById('error-name').innerText = errors.name[0];
            }
            if(errors.email) {
                document.getElementById('error-email').innerText = errors.email[0];
            }
            if(errors.password) {
                document.getElementById('error-password').innerText = errors.password[0];
            }
        }
    }
});
</script>
@endsection
