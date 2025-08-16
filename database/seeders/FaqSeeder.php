<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'pertanyaan' => 'Apa itu Beasiswa Bersekolah?',
                'jawaban' => 'Beasiswa Bersekolah adalah program beasiswa untuk siswa berprestasi yang membutuhkan bantuan pendidikan.',
                'status' => 'published'
            ],
            [
                'pertanyaan' => 'Bagaimana cara mendaftar Beasiswa Bersekolah?',
                'jawaban' => 'Anda dapat mendaftar melalui website ini dengan mengklik tombol "Daftar" dan mengisi formulir yang tersedia.',
                'status' => 'published'
            ],
            [
                'pertanyaan' => 'Apa saja persyaratan untuk mendaftar?',
                'jawaban' => 'Persyaratan utama meliputi: status aktif sebagai siswa, prestasi akademik/non-akademik, dan kondisi ekonomi keluarga.',
                'status' => 'published'
            ]
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
