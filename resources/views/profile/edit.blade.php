<x-layouts.app title="Profil Admin" :breadcrumbs="['Pengaturan' => null, 'Profil Admin' => null]">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-person-vcard me-1"></i> Informasi akun</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" x-data="{ loading: false }" x-on:submit="loading = true">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" x-bind:disabled="loading">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="display: none;"></span>
                            Simpan perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-shield-lock me-1"></i> Ubah kata sandi</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}" x-data="{ loading: false }" x-on:submit="loading = true">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="recovery_keyword" class="form-label">Kata kunci pemulihan</label>
                            <input id="recovery_keyword" type="password" name="recovery_keyword"
                                   class="form-control @error('recovery_keyword', 'updatePassword') is-invalid @enderror"
                                   required autocomplete="off">
                            @error('recovery_keyword', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Kata sandi saat ini</label>
                            <input id="current_password" type="password" name="current_password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata sandi baru</label>
                            <input id="password" type="password" name="password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                   required autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="form-control" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary" x-bind:disabled="loading">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="display: none;"></span>
                            Perbarui kata sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
