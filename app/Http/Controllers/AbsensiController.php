<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Absensi;
use App\Models\KelasMq;
use App\Models\PesertaKelas;
use App\Models\Mahasiswa;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    // aktor mahasiswa dan tutor
    public function hadir(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'kelas_id'     => 'required|exists:kelas_mq,id',
            'mahasiswa_id' => 'nullable|exists:mahasiswa,id' 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $mahasiswaId = null;
        $tutorId = null;

        // --- MAHASISWA ABSEN MANDIRI ---
        if ($user->hasRole('Mahasiswa')) {
            $profilMhs = Mahasiswa::where('user_id', $user->id)->first();
            
            if (!$profilMhs) {
                return response()->json(['message' => 'Profil mahasiswa tidak ditemukan.'], 404);
            }

            $mahasiswaId = $profilMhs->id;
            
            // Validasi: Pastikan mahasiswa benar-benar peserta di kelas ini
            $cekPeserta = PesertaKelas::where('kelas_id', $request->kelas_id)
                                      ->where('mahasiswa_id', $mahasiswaId)
                                      ->exists();
            if (!$cekPeserta) {
                return response()->json(['message' => 'Anda tidak terdaftar di kelas ini.'], 403);
            }
        } 
        // --- TUTOR YANG MENGAKSES ---
        else if ($user->hasRole('Tutor')) {
            // Validasi: Pastikan tutor ini memang pengajar di kelas tersebut
            $cekKelas = KelasMq::where('id', $request->kelas_id)->where('tutor_id', $user->id)->exists();
            if (!$cekKelas) {
                return response()->json(['message' => 'Anda bukan pengajar di kelas ini.'], 403);
            }

            if ($request->filled('mahasiswa_id')) {
                //  Tutor absenkan mahasiswa manual
                $mahasiswaId = $request->mahasiswa_id;
                $tutorId = $user->id; 
            } else {
                //  Tutor absen mandiri (mencatat dirinya hadir mengajar)
                $tutorId = $user->id;
                $mahasiswaId = null; 
            }
        } else {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // --- CEK ABSEN GANDA DI HARI YANG SAMA ---
        $hariIni = Carbon::today();
        
        $queryCekAbsen = Absensi::where('kelas_id', $request->kelas_id)
                                ->whereDate('tanggal_absensi', $hariIni);

        if ($mahasiswaId) {
            // Mengecek apakah mahasiswa ini sudah diabsen hari ini
            $queryCekAbsen->where('mahasiswa_id', $mahasiswaId);
        } else {
            // Mengecek apakah Tutor ini sudah absen mengajar hari ini
            $queryCekAbsen->where('tutor_id', $tutorId)->whereNull('mahasiswa_id');
        }

        if ($queryCekAbsen->exists()) {
            return response()->json(['message' => 'Kehadiran untuk hari ini sudah tercatat.'], 400);
        }

        // --- SIMPAN DATA KE DATABASE ---
        $absen = Absensi::create([
            'kelas_id'        => $request->kelas_id,
            'mahasiswa_id'    => $mahasiswaId,
            'tutor_id'        => $tutorId,
            'tanggal_absensi' => now(),
            'status_absensi'  => 'Hadir',
            'is_valid'        => true 
        ]);

        return response()->json([
            'message' => 'Presensi kehadiran berhasil dicatat.',
            'data'    => $absen
        ], 201);
    }

    // aktor tutor
    // anulir
    public function anulirKehadiran(Request $request, $id)
    {
        $absen = Absensi::find($id);

        if (!$absen) {
            return response()->json(['message' => 'Data absensi tidak ditemukan.'], 404);
        }

        // Mencegah Tutor menganulir absen kehadirannya sendiri (hanya boleh anulir absen mahasiswa)
        if ($absen->mahasiswa_id == null && $absen->tutor_id == $request->user()->id) {
            return response()->json(['message' => 'Anda tidak bisa menganulir kehadiran Anda sendiri.'], 403);
        }

        // Ubah status
        $absen->status_absensi = 'Alpa';
        $absen->is_valid = false;
        $absen->save();

        return response()->json([
            'message' => 'Kehadiran berhasil dianulir menjadi Alpa.',
            'data'    => $absen
        ], 200);
    }
}
