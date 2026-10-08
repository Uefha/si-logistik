<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'recovery_keyword' => ['required', 'string', function ($attribute, $value, $fail) {
                $expected = (string) config('logistik.kata_kunci_pemulihan');
                if ($expected === '' || ! hash_equals($expected, (string) $value)) {
                    $fail('Kata kunci pemulihan tidak sesuai atau belum dikonfigurasi.');
                }
            }],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Akun dengan email tersebut tidak ditemukan.'])->withInput($request->only('email'));
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diatur ulang. Silakan masuk dengan kata sandi baru.');
    }
}
