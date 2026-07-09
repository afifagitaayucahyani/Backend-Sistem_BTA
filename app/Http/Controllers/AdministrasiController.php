<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PendaftaranBta;
use App\Models\Mahasiswa;

class AdministrasiController extends Controller
{
    // upload pembayaran
    // aktor mahasiswa
    public function uploadSlip(Request $request)
    {
        // cari id mahasiswa berdasarkan yang sedang login
        $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

        if (!$mhs) {
            return response()->json([
                'message' => 'Profil mahasiswa tidak ditemukan.'
                ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file_slip_pembayaran' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // cek apakah mahasiswa sudah pernah upload dan masih pending/valid
        $pendaftaranAktif = PendaftaranBta::where('mahasiswa_id', $mhs->id)
            ->whereIn('status_validasi', ['Pending', 'Valid'])
            ->first();

        if ($pendaftaranAktif) {
            return response()->json([
                'message' => 'Anda sudah mengunggah slip sebelumnya. Status saat ini: ' . $pendaftaranAktif->status_validasi
            ], 400);
        }

        // simpan file
        $path = $request->file('file_slip_pembayaran')->store('slip_pembayaran', 'public');

        // simpan kedatabase
        $pendaftaran = PendaftaranBta::create([
            'mahasiswa_id'         => $mhs->id,
            'file_slip_pembayaran' => $path,
            'status_validasi'      => 'Pending' 
        ]);

        return response()->json([
            'message' => 'Slip pembayaran berhasil diunggah dan menunggu validasi.',
            'data'    => $pendaftaran
        ], 201);
    }

    // antrean-slip
    // aktor admin/staff
    public function getDaftarAntrean(Request $request)
    {
        // Filter status (bisa mengambil semua, atau hanya yang 'Pending'
        $status = $request->query('status');

        $query = PendaftaranBta::with(['mahasiswa.user']);

        if ($status) {
            $query->where('status_validasi', $status);
        }

        $antrean = $query->latest()->get();

        return response()->json([
            'message' => 'Data antrean slip pembayaran berhasil diambil.',
            'data'    => $antrean
        ], 200);
    }

    // validasi slip
    // aktor admin/staff
    public function validasiSlip(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status_validasi' => 'required|in:Valid,Ditolak',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $pendaftaran = PendaftaranBta::find($id);

        if (!$pendaftaran) {
            return response()->json([
                'message' => 'Data pendaftaran tidak ditemukan.'
                ], 404);
        }

        // Update status sesuai inputan Staff (Approve/Reject)
        $pendaftaran->status_validasi = $request->status_validasi;
        $pendaftaran->save();

        return response()->json([
            'message' => 'Status validasi berhasil diperbarui menjadi ' . $request->status_validasi,
            'data'    => $pendaftaran
        ], 200);
    }
}
