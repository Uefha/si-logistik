<x-layouts.guest title="Masuk">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Masuk</h1>
        <p class="text-secondary mb-0">Gunakan akun Admin / Petugas Logistik.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="mb-3">
            <label for="password" class="form-label">Kata sandi</label>
            <input id="password" type="password" name="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Ingat saya</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100" x-bind:disabled="loading">
            <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;"></span>
            Masuk
        </button>
        <div class="text-center mt-3"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></div>
    </form>
</x-layouts.guest>
