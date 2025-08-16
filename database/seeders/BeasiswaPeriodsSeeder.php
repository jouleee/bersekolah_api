<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BeasiswaPeriods;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BeasiswaPeriodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PERBAIKAN: Disable foreign key checks dulu, lalu hapus data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Hapus data dari tabel yang memiliki foreign key ke beasiswa_periods
        DB::table('beasiswa_applications')->delete();
        
        // Baru hapus data beasiswa_periods
        DB::table('beasiswa_periods')->delete();
        
        // Enable kembali foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Data periode beasiswa untuk tahun 2025
        $periods = [
            [
                'tahun' => 2025,
                'nama_periode' => 'Periode 2025',
                'deskripsi' => 'Periode beasiswa tahun 2025',
                'mulai_pendaftaran' => '2025-01-01',
                'akhir_pendaftaran' => '2025-08-31',
                'mulai_beasiswa' => '2025-09-01',
                'akhir_beasiswa' => '2026-06-30',
                'status' => 'active',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tahun' => 2026,
                'nama_periode' => 'Periode 2026',
                'deskripsi' => 'Periode beasiswa tahun 2026',
                'mulai_pendaftaran' => '2026-01-01',
                'akhir_pendaftaran' => '2026-08-31',
                'mulai_beasiswa' => '2026-09-01',
                'akhir_beasiswa' => '2027-06-30',
                'status' => 'draft',
                'is_active' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];
        
        foreach ($periods as $period) {
            BeasiswaPeriods::create($period);
        }
        
        $this->command->info('✅ Beasiswa periods seeded successfully!');
        $this->command->table(
            ['ID', 'Tahun', 'Mulai Pendaftaran', 'Akhir Pendaftaran', 'Mulai Beasiswa', 'Akhir Beasiswa'],
            BeasiswaPeriods::all()->map(function($period) {
                return [
                    $period->id,
                    $period->tahun,
                    Carbon::parse($period->mulai_pendaftaran)->format('d M Y'),
                    Carbon::parse($period->akhir_pendaftaran)->format('d M Y'),
                    Carbon::parse($period->mulai_beasiswa)->format('d M Y'),
                    Carbon::parse($period->akhir_beasiswa)->format('d M Y'),
                ];
            })->toArray()
        );
    }
}
