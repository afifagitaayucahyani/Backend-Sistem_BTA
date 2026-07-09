<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodeAkademik;

class PeriodeAkademikController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Menampilkan daftar periode dari yang paling baru dibuat
        $periode = PeriodeAkademik::latest()->get();

        return response()->json([
            'message' => 'Daftar periode akademik berhasil diambil.',
            'data'    => $periode
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
            'nama_periode'            => 'required|string|max:100|unique:periode_akademik,nama_periode',
            'batas_waktu_input_nilai' => 'required|date',
            'is_active'               => 'boolean' 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Jika Admin langsung mencentang "Jadikan Aktif" saat membuat periode baru
            if ($request->is_active) {
                // Matikan semua periode lain terlebih dahulu
                PeriodeAkademik::where('is_active', true)->update(['is_active' => false]);
            }

            $periode = PeriodeAkademik::create([
                'nama_periode'            => $request->nama_periode,
                'batas_waktu_input_nilai' => $request->batas_waktu_input_nilai,
                'is_active'               => $request->is_active ?? false,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Periode akademik baru berhasil ditambahkan.',
                'data'    => $periode
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal membuat periode.', 'error' => $e->getMessage()], 500);
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
    public function update(Request $request, string $id)
    {
        // 
    }

    public function setActive($id)
    {
        $periodeBaru = PeriodeAkademik::find($id);

        if (!$periodeBaru) {
            return response()->json(['message' => 'Periode akademik tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            // Matikan semua periode yang saat ini sedang aktif
            PeriodeAkademik::where('is_active', true)->update(['is_active' => false]);

            // Aktifkan periode yang dipilih
            $periodeBaru->is_active = true;
            $periodeBaru->save();

            DB::commit();

            return response()->json([
                'message' => "Sistem sekarang berjalan pada periode: {$periodeBaru->nama_periode}.",
                'data'    => $periodeBaru
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal mengubah status periode.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
