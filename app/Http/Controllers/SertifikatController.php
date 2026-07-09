<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mahasiswa;
use App\Models\NilaiAkhir;
use App\Models\Sertifikat;

class SertifikatController extends Controller
{
    private function getProfilMahasiswa($userId)
    {
        return Mahasiswa::where('user_id', $userId)->first();
    }


    // status kelulusan
    public function cekStatusKelulusan(Request $request)
    {
        $mhs = $this->getProfilMahasiswa($request->user()->id);

        if (!$mhs) {
            return response()->json(['message' => 'Profil mahasiswa tidak ditemukan.'], 404);
        }

        // Ambil nilai terbaru yang statusnya sudah "Disahkan Pusat"
        // (Menggunakan with('kelas') agar nama kelas/tingkat ikut terbawa ke React JS)
        $nilai = NilaiAkhir::with('kelas')
                           ->where('mahasiswa_id', $mhs->id)
                           ->where('status_validasi', 'Disahkan Pusat')
                           ->latest()
                           ->first();

        if (!$nilai) {
            return response()->json([
                'message' => 'Nilai akhir Anda belum tersedia atau belum disahkan oleh Kepala Pusat.',
                'data'    => null
            ], 200); // Tetap 200 OK, tapi datanya kosong, agar React bisa menampilkan pesan "Harap Menunggu"
        }

        // Siapkan pesan kelulusan yang dinamis
        $pesanKelulusan = $nilai->status_kelulusan 
            ? "Selamat! Anda dinyatakan LULUS dengan predikat sangat baik." 
            : "Mohon maaf, Anda dinyatakan BELUM LULUS. Tetap semangat dan silakan mendaftar di periode berikutnya.";

        return response()->json([
            'message' => 'Status kelulusan berhasil diambil.',
            'data'    => [
                'kelas'            => $nilai->kelas->nama_kelas,
                'total_poin'       => $nilai->total_poin,
                'huruf_mutu'       => $nilai->huruf_mutu,
                'status_kelulusan' => $nilai->status_kelulusan,
                'pesan'            => $pesanKelulusan
            ]
        ], 200);
    }

    public function downloadSertifikat(Request $request)
    {
        $mhs = $this->getProfilMahasiswa($request->user()->id);

        // 1. Pengecekan Syarat Lulus
        $nilai = NilaiAkhir::where('mahasiswa_id', $mhs->id)
                           ->where('status_validasi', 'Disahkan Pusat')
                           ->latest()
                           ->first();

        // Jika tidak ada nilai, atau ada nilai tapi status kelulusan = false (Gagal)
        if (!$nilai || $nilai->status_kelulusan == false) {
            return response()->json([
                'message' => 'Akses ditolak. Anda belum memenuhi syarat kelulusan untuk mendapatkan sertifikat.'
            ], 403);
        }

        // 2. Mengambil Data Sertifikat
        $sertifikat = Sertifikat::where('mahasiswa_id', $mhs->id)->first();

        if (!$sertifikat) {
            return response()->json([
                'message' => 'Sertifikat Anda sedang dalam proses penerbitan. Silakan cek kembali nanti.'
            ], 404);
        }

        // 3. Menyiapkan URL Download File
        $urlFile = asset('storage/' . $sertifikat->file_sertifikat);

        return response()->json([
            'message'      => 'Sertifikat tersedia.',
            'download_url' => $urlFile,
            'data_dokumen' => [
                'nomor_sk' => $sertifikat->nomor_sk,
                'keterangan_nilai' => $sertifikat->keterangan_nilai_total,
                'keterangan_huruf' => $sertifikat->keterangan_huruf,
            ]
        ], 200);
    }

    // upload template
    // aktor admin/staff
    public function uploadTemplate(Request $request)
    {
        // 1. Validasi input: wajib berupa gambar (JPG/PNG) maksimal 2MB
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'template' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'nomor_sk' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('template');
        $ext = $file->getClientOriginalExtension();
        $namaFileStatis = 'template_aktif.' . $ext;
        $path = $file->storeAs('sertifikat', $namaFileStatis, 'public');

        // 2. Simpan Nomor SK ke dalam file teks (nomor_sk.txt)
        \Illuminate\Support\Facades\Storage::disk('public')->put('sertifikat/nomor_sk.txt', $request->nomor_sk);

        return response()->json([
            'message'      => 'Template sertifikat dan Nomor SK berhasil diperbarui.',
            'template_url' => asset('storage/' . $path),
            'nomor_sk'     => $request->nomor_sk
        ], 200);
    }
}
