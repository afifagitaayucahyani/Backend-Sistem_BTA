<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\KelasMq;
use App\Models\PesertaKelas;
use App\Models\NilaiAkhir;
use Illuminate\Support\Facades\DB;

class NilaiController extends Controller
{
    // Tutor unduh template
    public function downloadTemplate(Request $request, $kelas_id)
    {
        // Pastikan kelas tersebut milik Tutor yang sedang login
        $kelas = KelasMq::where('id', $kelas_id)->where('tutor_id', $request->user()->id)->first();
        if (!$kelas) {
            return response()->json(['message' => 'Akses ditolak. Anda bukan pengajar kelas ini.'], 403);
        }

        // Ambil daftar mahasiswa
        $peserta = PesertaKelas::with('mahasiswa.user')->where('kelas_id', $kelas_id)->get();

        // Siapkan data CSV
        $csvData = "Kelas_ID,Mahasiswa_ID,NIM,Nama_Mahasiswa,Total_Poin_Nilai\n";
        
        foreach ($peserta as $p) {
            // Kita menyisipkan Kelas_ID dan Mahasiswa_ID agar Tutor tidak perlu mengetik manual
            $csvData .= "{$kelas_id},{$p->mahasiswa_id},{$p->mahasiswa->nim},{$p->mahasiswa->user->name},0\n";
        }

        // Return sebagai file yang bisa didownload
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="Template_Nilai_' . $kelas->nama_kelas . '.csv"');
    }

    // upload nilai excel
    public function uploadNilai(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_nilai'                => 'required|array',
            'data_nilai.*.kelas_id'     => 'required|exists:kelas_mq,id',
            'data_nilai.*.mahasiswa_id' => 'required|exists:mahasiswa,id',
            'data_nilai.*.total_nilai'   => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->data_nilai as $nilai) {
                $poin = $nilai['total_nilai'];
                
                // SKALA HURUF MUTU 
                if ($poin >= 85) {
                    $hurufMutu = 'A';
                } else if ($poin >= 75) {
                    $hurufMutu = 'B+';
                } else if ($poin >= 69) {
                    $hurufMutu = 'B-'; 
                } else if ($poin >= 60) {
                    $hurufMutu = 'C';
                } else {
                    $hurufMutu = 'E';
                }

                NilaiAkhir::updateOrCreate(
                    [
                        'kelas_id'     => $nilai['kelas_id'],
                        'mahasiswa_id' => $nilai['mahasiswa_id'],
                    ],
                    [
                        'total_nilai'       => $poin,
                        'huruf_mutu'       => $hurufMutu,
                        'status_validasi'  => 0,
                        'status_kelulusan' => $poin >= 69 ? 'Lulus' : 'Tidak Lulus',
                        
                    ]
                );
            }
            DB::commit();

            return response()->json(['message' => 'Data nilai berhasil disimpan dan menunggu validasi Staff.'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal menyimpan nilai.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAntreanValidasi()
    {
        // Mengelompokkan daftar nilai yang 'Pending' berdasarkan Kelas
        // Agar Staff melihatnya per-kelas, bukan per-mahasiswa yang acak
        $antrean = KelasMq::whereHas('nilaiAkhir', function($query) {
            $query->where('status_validasi', 0);
        })
        ->withCount(['nilaiAkhir' => function($query) {
            $query->where('status_validasi', 0);
        }])
        ->with('tutor:id,name')
        ->get();

        return response()->json([
            'message' => 'Daftar antrean kelas yang menunggu validasi Staff berhasil diambil.',
            'data'    => $antrean
        ], 200);
    }

    public function validasiTahapSatu(Request $request, $kelas_id)
    {
        // Mengubah semua nilai berstatus 'Pending' di kelas tersebut menjadi 'Divalidasi Staff'
        $updateCount = NilaiAkhir::where('kelas_id', $kelas_id)
                                 ->where('status_validasi', 0)
                                 ->update(['status_validasi' => 1]);

        if ($updateCount == 0) {
            return response()->json(['message' => 'Tidak ada nilai yang berstatus Pending di kelas ini.'], 404);
        }

        return response()->json([
            'message' => "Berhasil memvalidasi tahap pertama untuk {$updateCount} data mahasiswa. Diteruskan ke Kepala Pusat."
        ], 200);
    }

    public function sahkanNilai(Request $request, $kelas_id)
    {
        $nilaiKelas = NilaiAkhir::with('mahasiswa.user')
                                ->where('kelas_id', $kelas_id)
                                ->where('status_validasi', 1)
                                ->get();

        if ($nilaiKelas->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada nilai yang melewati tahap validasi Staff untuk kelas ini.'
                ], 404);
        }

        // --- AMBIL & KONVERSI TEMPLATE BACKGROUND KE BASE64 
        $pathJpg = storage_path('app/public/sertifikat/template_aktif.jpg');
        $pathPng = storage_path('app/public/sertifikat/template_aktif.png');
        $templatePath = file_exists($pathJpg) ? $pathJpg : (file_exists($pathPng) ? $pathPng : null);

        $base64Template = '';
        if ($templatePath) {
            $type = pathinfo($templatePath, PATHINFO_EXTENSION);
            $data = file_get_contents($templatePath);
            $base64Template = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // ambil nomor sk dari file text
        $pathSk = storage_path('app/public/sertifikat/nomor_sk.txt');
        $nomorSkAktif = file_exists($pathSk) ? file_get_contents($pathSk) : 'SK-Belum-Diatur';

        DB::beginTransaction();
        try {
            foreach ($nilaiKelas as $nilai) {
                $nilai->status_validasi = 2;
                
                if ($nilai->total_nilai >= 69) {
                    $nilai->status_kelulusan = 'Lulus';
                    $nilai->save();

                    $namaMahasiswa = $nilai->mahasiswa->user->name;
                    $nimMahasiswa = $nilai->mahasiswa->nim ?? 'NIM-Belum-Ada';
                    
                    // Siapkan data pembungkus dengan variabel $nomorSkAktif
                    $dataPayload = [
                        'nomor_sk'         => $nomorSkAktif,
                        'nama_mahasiswa'   => $namaMahasiswa,
                        'nim'              => $nimMahasiswa,
                        'total_nilai'       => $nilai->total_nilai,
                        'keterangan_huruf_mutu' => $nilai->huruf_mutu,
                        'template_base64'  => $base64Template
                    ];

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sertifikat', $dataPayload);

                    $namaFilePdf = 'sertifikat_' . $nilai->mahasiswa_id . '_' . time() . '.pdf';
                    $jalurPenyimpanan = 'sertifikat/' . $namaFilePdf;

                    if (!file_exists(storage_path('app/public/sertifikat'))) {
                        mkdir(storage_path('app/public/sertifikat'), 0755, true);
                    }

                    $pdf->save(storage_path('app/public/' . $jalurPenyimpanan));

                    // Tetap simpan ke kolom 'nomor_sertifikat' di database sesuai ERD
                    \App\Models\Sertifikat::updateOrCreate(
                        ['mahasiswa_id' => $nilai->mahasiswa_id],
                        [
                            'nomor_sk'          => $nomorSkAktif, 
                            'keterangan_nilai_total'    => $nilai->total_nilai,
                            'keterangan_huruf_mutu'          => $nilai->huruf_mutu,
                            'file_sertifikat'           => $jalurPenyimpanan,
                        ]
                    );
                } else {
                    $nilai->status_kelulusan = 'Tidak Lulus';
                    $nilai->save();
                }
            }
            DB::commit();

            return response()->json([
                'message' => 'Nilai kelas berhasil disahkan! Sertifikat PDF bagi mahasiswa yang LULUS telah otomatis diterbitkan dengan Nomor SK: ' . $nomorSkAktif
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Gagal mengesahkan nilai & menerbitkan sertifikat.', 'error'
                 => $e->getMessage()], 500);
        }
    }
}
