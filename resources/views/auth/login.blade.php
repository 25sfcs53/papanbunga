<x-guest-layout>
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <div class="text-center mb-4">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" width="150">
                <h1 class="h3 mb-3 fw-normal">{{ config('Edelia Florist', 'Edelia Florist') }}</h1>
                <p class="text-muted">Masuk untuk mengelola operasional</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror" />
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" />
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
