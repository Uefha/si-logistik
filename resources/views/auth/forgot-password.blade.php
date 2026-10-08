<x-layouts.guest title="Lupa Kata Sandi">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Lupa kata sandi</h1>
        <p class="text-secondary mb-0">Masukkan email akun, kata kunci pemulihan, lalu buat kata sandi baru.</p>
    </div>

    <form method="POST" action="{{ route('password.recovery') }}" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email akun</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="recovery_keyword" class="form-label">Kata kunci pemulihan</label>
            <input id="recovery_keyword" type="password" name="recovery_keyword"
                   class="form-control @error('recovery_keyword') is-invalid @enderror" required autocomplete="off">
            @error('recovery_keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata sandi baru</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Minimal 8 karakter.</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100" x-bind:disabled="loading">
            <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;"></span>
            Atur ulang kata sandi
        </button>
        <div class="text-center mt-3"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></div>
    </form>
</x-layouts.guest>
