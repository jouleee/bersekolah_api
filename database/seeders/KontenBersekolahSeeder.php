<?php

namespace Database\Seeders;

use App\Models\KontenBersekolah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KontenBersekolahSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Bertumbuh', 'Bercerita', 'Berolahraga', 'Bersekolah Fest', 'Berkunjung'];

        $judulList = [
            'Kelas Bertumbuh Bulan Mei',
            'Beritikaf di Istiqlal',
            'Kelas Bercerita Bulan Juni',
            'Touring Bersama Beswan Bersekolah',
            'Kelas Bertumbuh Bulan April',
            'Senam Pagi Ceria',
            'Bincang Literasi Bersama Alumni',
            'Bersekolah Fest 2025',
            'Kunjungan ke Rumah Baca',
            'Diskusi Buku Anak',
            'Workshop Menulis Kreatif',
            'Pelatihan Olahraga Ringan',
            'Bersekolah Goes to Nature',
            'Nonton Bareng Film Edukatif',
            'Ngabuburit Produktif',
            'Berkunjung ke Museum Pendidikan',
            'Pelatihan Public Speaking',
            'Kelas Menggambar untuk Anak',
            'Talkshow Karir Inspiratif',
            'Lomba Cerdas Cermat Sains'
        ];

        $konten = [];

        foreach ($judulList as $index => $judul) {
            $konten[] = [
                'judul_halaman' => $judul,
                'slug' => Str::slug($judul) . '-' . ($index + 1),
                'deskripsi' => 'Deskripsi untuk ' . strtolower($judul),
                'category' => $categories[array_rand($categories)],
                'gambar' => 'gambar' . (($index  % 5) + 1) . '.jpg',
                'status' => 'published',
                'user_id' => 1,
            ];
        }

        foreach ($konten as $item) {
            KontenBersekolah::create($item);
        }
    }
}
