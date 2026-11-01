<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelolaKasirController extends Controller
{
    public function index(): View
    {
        $kasir = User::where('role', 'kasir')
            ->withCount('cashSessions')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('kelola-kasir.index', [
            'kasirPagi' => $kasir->where('shift', 'pagi'),
            'kasirMalam' => $kasir->where('shift', 'malam'),
            'kasirTanpaShift' => $kasir->whereNull('shift'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'shift' => ['required', Rule::in(['pagi', 'malam'])],
        ], $this->pesanValidasi());

        User::create($data + ['role' => 'kasir', 'is_active' => true]);

        return back()->with('sukses', 'Kasir ' . $data['name'] . ' ditambahkan ke shift ' . $data['shift'] . '.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'kasir', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'shift' => ['required', Rule::in(['pagi', 'malam'])],
        ], $this->pesanValidasi());

        // Kata sandi hanya diganti kalau diisi
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('sukses', 'Data kasir ' . $user->name . ' sudah diperbarui.');
    }

    public function status(User $user): RedirectResponse
    {
        abort_unless($user->role === 'kasir', 404);

        if ($user->is_active && $user->cashSessions()->where('status', 'buka')->exists()) {
            return back()->with('error', $user->name . ' masih membuka kas. Minta kasir menutup kas terlebih dahulu.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('sukses', $user->is_active
            ? 'Akun ' . $user->name . ' diaktifkan kembali.'
            : 'Akun ' . $user->name . ' dinonaktifkan dan tidak bisa login.');
    }

    private function pesanValidasi(): array
    {
        return [
            'name.required' => 'Nama kasir wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak benar.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'shift.required' => 'Pilih shift kasir.',
            'shift.in' => 'Shift harus pagi atau malam.',
        ];
    }
}