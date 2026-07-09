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
    public function index()
    {
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'Mahasiswa');
        })->with('roles')->get();

        return response()->json([
            'message' => 'Daftar akun user',
            'data' => $users
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
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|in:Kepala Pusat,Admin,Tutor,Rektorat' 
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
            'data' => $user->load('roles')
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
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Akun pengguna tidak ditemukan'
            ], 404);
        }

        // validasi email boleh sama dengan emailnya sendiri, tapi tidak boleh sama dengan email user lain
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|unique:users,email,' . $id,
            'role'     => 'required|string|in:Super Admin,Kepala Pusat,Staff,Tutor,Rektorat'
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // update profil
        $user->name = $request->name;
        $user->email = $request->email;

        // cek jika passwrod diubah
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // update role
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Akun pengguna berhasil diperbarui',
            'data' => $user->load('roles')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Akun pengguna tidak ditemukan'
            ], 404);
        }

        // mencegah user menghapus akun sendiri
        if ($user->hasRole('Kepala Pusat')) {
            return response()->json([
                'message' => 'Akun tidak dapat di hapus dari sistem'
            ], 403);
        }

        // hapus akun
        $user->delete();

        return response()->json([
            'message' => 'Akun pengguna berhasil dihapus'
        ], 200);
    }
}
