<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Mahasiswa;
use App\Models\TesPenempatan;

class TesPenempatanController extends Controller
{
    public function index()
    {
        // Eloquent 'whereDoesntHave' akan memfilter tabel mahasiswa
        // dan HANYA mengambil mereka yang datanya belum ada di tabel 'tes_penempatan'
        $mahasiswaBelumTes = Mahasiswa::whereDoesntHave('tesPenempatan')
            ->with('user:id,name,email')
            ->get();

        return response()->json([
            'message' => 'Daftar mahasiswa yang belum mengikuti tes berhasil diambil.',
            'data'    => $mahasiswaBelumTes
        ], 200);
    }

    public function inputNilai(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'nilai_tes'    => 'required|integer|min:50|max:100', 
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Proteksi Ganda: Cek apakah mahasiswa ini sudah pernah diinput nilainya
        $cekTes = TesPenempatan::where('mahasiswa_id', $request->mahasiswa_id)->first();
        if ($cekTes) {
            return response()->json([
                'message' => 'Mahasiswa ini sudah memiliki nilai tes penempatan di sistem.'
            ], 400);
        }

        // penentuan tingkat kelas
        $hasilTingkat = $request->nilai_tes >= 50 ? 'Mahir' : 'Menegah';
        // Simpan ke database
        $tes = TesPenempatan::create([
            'mahasiswa_id'  => $request->mahasiswa_id,
            'admin_id'      => $request->user()->id, 
            'nilai_tes'     => $request->nilai_tes,
            'hasil_tingkat' => $hasilTingkat,
        ]);

        return response()->json([
            'message' => 'Nilai tes berhasil disimpan. Mahasiswa ditempatkan di tingkat: ' . $hasilTingkat,
            'data'    => $tes
        ], 201);
    }
}
