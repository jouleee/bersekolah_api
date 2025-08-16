<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Announcement;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample announcements
        $announcements = [
            [
                'title' => 'Pendaftaran Beasiswa Periode 2025',
                'content' => '<p>Pendaftaran beasiswa periode 2025 telah dibuka. Silakan mendaftar melalui website resmi kami.</p><p>Syarat dan ketentuan dapat dilihat pada halaman informasi beasiswa.</p>',
                'status' => 'published',
                'tag' => 'beasiswa',
                'published_at' => Carbon::now()->subDays(5),
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Workshop Pengembangan Diri',
                'content' => '<p>Diberitahukan kepada seluruh penerima beasiswa bahwa akan diadakan workshop pengembangan diri pada tanggal 15 Juli 2025.</p><p>Workshop ini wajib diikuti oleh seluruh penerima beasiswa.</p>',
                'status' => 'published',
                'tag' => 'event',
                'published_at' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Pengumuman Hasil Seleksi Tahap 1',
                'content' => '<p>Pengumuman hasil seleksi tahap 1 telah dirilis. Silakan cek status pendaftaran Anda melalui dashboard aplikasi.</p>',
                'status' => 'published',
                'tag' => 'pengumuman',
                'published_at' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'Maintenance Website',
                'content' => '<p>Akan dilakukan maintenance website pada tanggal 25 Juni 2025 pukul 00:00 - 03:00 WIB.</p><p>Mohon maaf atas ketidaknyamanannya.</p>',
                'status' => 'draft',
                'tag' => 'info',
                'published_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Perpanjangan Deadline Pengumpulan Dokumen',
                'content' => '<p>Deadline pengumpulan dokumen diperpanjang hingga 30 Juni 2025.</p><p>Pastikan semua dokumen telah diunggah ke sistem sebelum batas waktu yang ditentukan.</p>',
                'status' => 'draft',
                'tag' => 'beasiswa',
                'published_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }
    }
}
