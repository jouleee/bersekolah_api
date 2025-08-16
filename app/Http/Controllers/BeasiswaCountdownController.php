<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BeasiswaCountdownController extends Controller
{
    public function countdown(){
        $periode = DB::table('beasiswa_periods')
            ->where('is_active', 1)
            ->orderBy('akhir_pendaftaran', 'desc')
            ->first();

        if(!$periode) {
            return response()->json([
                'message' => 'Tidak ada periode beasiswa yang aktif saat ini.'
            ], 404);
        }

        // Hitung selisih waktu dari sekarang ke akhir pendaftaran
        $now = Carbon::now();
        $end = Carbon::parse($periode->akhir_pendaftaran);

        // Jika sudah lewat, tampilkan 0
        if ($now->gt($end)) {
            return response()->json([
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'message' => 'Pendaftaran sudah ditutup.'
            ]);
        }

        // Hitung selisih waktu
        $diff = $now->diff($end);

        return response()->json([
            'tahun'       => $periode->tahun,
            'nama'        => $periode->nama_periode,
            'days'        => $diff->d,
            'hours'       => $diff->h,
            'minutes'     => $diff->i,
            'seconds'     => $diff->s,
            'end_date'    => $periode->akhir_pendaftaran,
            'status'      => $periode->status,
        ]);
    }

   
}
