<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Mahasiswa;
use App\Models\User;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Mahasiswa::with('user:id,name,email');

        // Fitur Pencarian berdasarkan NIM 
        if ($request->filled('search_nim')) {
            $query->where('nim', 'LIKE', '%' . $request->search_nim . '%');
        }

        // Fitur Pencarian berdasarkan Nama (mencari ke tabel 'users')
        if ($request->filled('search_nama')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search_nama . '%');
            });
        }

        // Fitur Filter berdasarkan Program Studi
        if ($request->filled('program_studi')) {
            $query->where('program_studi', $request->program_studi);
        }

        // Ambil data terbaru dengan metode pagination agar tidak memberatkan browser
        $mahasiswa = $query->latest()->paginate(10);

        return response()->json([
            'message' => 'Daftar data mahasiswa berhasil diambil.',
            'data'    => $mahasiswa
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
            'name'          => 'required|string|max:255',
            'nim'           => 'required|string|unique:mahasiswa,nim|max:50',
            'program_studi' => 'required|string|max:100',
            'fakultas'      => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // A. Format email otomatis menggunakan kombinasi NIM agar seragam
            $emailOtomatis = $request->nim . '@student.bta.com';

            // B. Buat akun login dasar di tabel users
            $user = User::create([
                'name'     => $request->name,
                'email'    => $emailOtomatis,
                'password' => Hash::make($request->nim), 
            ]);

            $user->assignRole('Mahasiswa');

            // D. Buat profil akademiknya di tabel mahasiswa
            $mahasiswa = Mahasiswa::create([
                'user_id'       => $user->id,
                'nim'           => $request->nim,
                'program_studi' => $request->program_studi,
                'fakultas'      => $request->fakultas,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Mahasiswa baru berhasil ditambahkan secara manual oleh Staff.',
                'data'    => [
                    'user'      => $user,
                    'mahasiswa' => $mahasiswa
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal menambahkan data mahasiswa.', 'error' => $e->getMessage()], 500);
        }
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
        $mahasiswa = Mahasiswa::find($id);

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'nim'           => 'required|string|max:50|unique:mahasiswa,nim,' . $id, // Abaikan pengecekan unik untuk ID diri sendiri
            'program_studi' => 'required|string|max:100',
            'fakultas'      => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Update data di tabel profil akademik
            $mahasiswa->nim = $request->nim;
            $mahasiswa->program_studi = $request->program_studi;
            $mahasiswa->fakultas = $request->fakultas;
            $mahasiswa->save();

            // Update nama asli di tabel users login
            $user = User::find($mahasiswa->user_id);
            if ($user) {
                $user->name = $request->name;
                // Jika NIM berubah, sesuaikan juga email login-nya agar tetap konsisten
                $user->email = $request->nim . '@student.bta.com';
                $user->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Data identitas mahasiswa berhasil diperbarui.',
                'data'    => $mahasiswa->load('user')
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal memperbarui data.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mahasiswa = Mahasiswa::find($id);

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            $userId = $mahasiswa->user_id;

            // Hapus profil mahasiswa terlebih dahulu (Menghindari pelanggaran Foreign Key Restrict)
            $mahasiswa->delete();

            // Hapus akun user utamanya agar tidak menjadi data sampah di sistem
            $user = User::find($userId);
            if ($user) {
                $user->delete();
            }

            DB::commit();
            return response()->json(['message' => 'Data ganda mahasiswa beserta akun loginnya berhasil dihapus secara permanen.'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal menghapus data. Kemungkinan data ini sudah terikat dengan riwayat absensi/nilai kelas.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getMahasiswaTersedia(Request $request)
    {
        // Mendapatkan "Mahir" atau "Menengah" dari Front-End
        $tingkat = $request->query('tingkat'); 

        // Validasi input tingkat
        if (!$tingkat) {
            return response()->json(['message' => 'Parameter tingkat wajib diisi.'], 400);
        }

        // Mengambil mahasiswa berdasarkan hasil tes penempatan
        $mahasiswaTersedia = Mahasiswa::whereHas('tesPenempatan', function($query) use ($tingkat) {
            // Pastikan 'hasil_tingkat' sesuai dengan nama kolom di database kamu
            $query->where('hasil_tingkat', $tingkat);
        })
        // Memastikan mahasiswa belum tergabung di kelas periode aktif
        ->whereDoesntHave('kelas_mq') 
        // Bawa data nama dari tabel user dan nilai dari tabel tes penempatan
        ->with(['user:id,name', 'tesPenempatan:id,mahasiswa_id,nilai_tes,hasil_tingkat']) 
        ->get();

        return response()->json([
            'message' => 'Data mahasiswa tersedia berhasil diambil',
            'data' => $mahasiswaTersedia
        ], 200);
    }
}
