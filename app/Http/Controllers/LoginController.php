<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Mahasiswa;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi form (Ubah 'email' menjadi 'login')
    $validator = Validator::make($request->all(), [
        'login'    => 'required|string', 
        'password' => 'required',
    ]);
    
    // Kembalikan pesan error jika validasi gagal
    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $loginInput = $request->login;
    $user = null;

    // 2. Cek apakah input adalah NIM Mahasiswa
    $mahasiswa = Mahasiswa::where('nim', $loginInput)->first();

    if ($mahasiswa) {
        // JIKA KETEMU: Ambil data User berdasarkan user_id dari tabel mahasiswa
        $user = User::find($mahasiswa->user_id);
    } else {
        // JIKA TIDAK KETEMU: Cek apakah inputnya berupa email atau name
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        
        // Cari user berdasarkan email atau name
        $user = User::where($fieldType, $loginInput)->first();
    }

    // 3. Pengecekan User dan Password (menggunakan gaya kodemu)
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Nim/Email atau password yang anda masukkan salah'
        ], 401);
    }

    // 4. Ambil data dari role (Menggunakan Spatie)
    Auth::login($user);
    
    // Wajib: Regenerate session untuk mengunci cookie secara aman
    $request->session()->regenerate();

    // Buat token Sanctum agar mendukung lintas domain (Vercel Frontend <-> cPanel Backend)
    $token = $user->createToken('auth_token')->plainTextToken;

    // ==========================================

    // 5. Ambil data dari role (Menggunakan Spatie)
    $role = $user->getRoleNames()->first();

    // 6. Return response sukses dengan token & user
    return response()->json([
        'message' => 'Login berhasil',
        'token'   => $token,
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $role
        ]
    ], 200);
    }

    // logout user
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }

    // ambil data user yang sedang login
    public function me(Request $request)
    {
        $user = $request->user();

        return  response()->json([
            'message' => 'Profil berhasil diambil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first()
            ]
        ], 200);
    }
}
