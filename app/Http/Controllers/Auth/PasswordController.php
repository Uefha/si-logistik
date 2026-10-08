<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Ubah kata sandi admin yang sedang masuk.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'recovery_keyword' => ['required', 'string', function ($attribute, $value, $fail) {
                $expected = (string) config('logistik.kata_kunci_pemulihan');
                if ($expected === '' || ! hash_equals($expected, (string) $value)) {
                    $fail('Kata kunci pemulihan tidak sesuai.');
                }
            }],
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
