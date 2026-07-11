<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // menampilkan daftar akun user
    public function index(Request $request)
    {
        $aktor = $request->user();

        $query = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Mahasiswa');
        });

        // Jika yang mengakses BUKAN Kepala Pusat (berarti Admin/Staff), 
        // filter data HANYA untuk menampilkan Tutor saja.
        if (!$aktor->hasRole('Kepala Pusat')) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'Tutor');
            });
        }

        $users = $query->get();

        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil',
            'data'    => $users
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $aktor = $request->user();

        // Tentukan Role apa saja yang boleh dibuat berdasarkan jabatan Aktor
        $allowedRoles = $aktor->hasRole('Kepala Pusat') 
            ? ['Super Admin', 'Staff', 'Tutor', 'Rektorat'] 
            : ['Tutor'];

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            // Validasi dinamis: menolak jika Admin mencoba membuat akun Rektorat/Kepala Pusat
            'role'     => 'required|string|in:' . implode(',', $allowedRoles) 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Akun pengguna berhasil dibuat',
            'data'    => $user->load('roles')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $targetUser = User::find($id);
        $aktor = $request->user();

        if (!$targetUser) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        // --- PROTEKSI KEAMANAN EDIT ---
        if (!$aktor->hasRole('Kepala Pusat')) {
            // Admin tidak boleh mengedit akun selain Tutor
            if (!$targetUser->hasRole('Tutor')) {
                return response()->json(['message' => 'Akses ditolak. Anda hanya berhak mengubah data Tutor.'], 403);
            }
            $allowedRoles = ['Tutor'];
        } else {
            $allowedRoles = ['Super Admin', 'Staff', 'Tutor', 'Rektorat'];
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|unique:users,email,' . $id,
            'role'     => 'required|string|in:' . implode(',', $allowedRoles)
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $targetUser->name = $request->name;
        $targetUser->email = $request->email;

        if ($request->filled('password')) {
            $targetUser->password = Hash::make($request->password);
        }

        $targetUser->save();
        $targetUser->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui',
            'data'    => $targetUser->load('roles')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $targetUser = User::find($id);
        $aktor = $request->user();

        if (!$targetUser) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        // --- PROTEKSI KEAMANAN HAPUS ---
        // 1. Cegah siapapun menghapus dirinya sendiri
        if ($targetUser->id === $aktor->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 403);
        }

        // 2. Batasan wewenang hapus
        if (!$aktor->hasRole('Kepala Pusat')) {
            if (!$targetUser->hasRole('Tutor')) {
                return response()->json(['message' => 'Akses ditolak. Anda hanya berhak menghapus data Tutor.'], 403);
            }
        } else {
            // Walaupun Kepala Pusat, tetap dicegah menghapus Super Admin Utama (opsional, sebagai pengaman ganda)
            if ($targetUser->hasRole('Super Admin')) {
                return response()->json(['message' => 'Akun Super Admin tidak boleh dihapus secara sistem.'], 403);
            }
        }

        $targetUser->delete();

        return response()->json([
            'message' => 'Akun berhasil dihapus dari sistem.'
        ], 200);
    }
}
