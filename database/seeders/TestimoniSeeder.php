<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestimoniSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('testimoni')->insert([
            [
                'nama' => 'Dedi Prasetya Nugraha',
                'angkatan_beswan' => 'Beswan 2023',
                'sekarang_dimana' => 'Mahasiswa S1 Ilmu Komputer UPI',
                'isi_testimoni' => 'Beasiswa Bersekolah telah menjadi cahaya di tengah keterbatasan. Saya bisa melanjutkan pendidikan tanpa khawatir soal biaya, dan kini saya makin semangat menggapai cita-cita.',
                'foto_testimoni' => 'Dedi.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Erin Kartika Maheswari',
                'angkatan_beswan' => 'Beswan 2022',
                'sekarang_dimana' => 'Bekerja di PT Telkom Indonesia',
                'isi_testimoni' => 'Lewat Beasiswa Bersekolah, saya mendapatkan kesempatan untuk terus belajar, memperluas jaringan, dan menemukan potensi terbaik dalam diri saya.',
                'foto_testimoni' => 'Erin.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Azzam Alfarezi Ramadhan',
                'angkatan_beswan' => 'Beswan 2021',
                'sekarang_dimana' => 'Magister Teknik Informatika ITB',
                'isi_testimoni' => 'Beasiswa Bersekolah membuka banyak pintu bagi saya. Dengan dukungan ini, saya dapat fokus pada riset dan pengembangan diri tanpa beban biaya.',
                'foto_testimoni' => 'Azzam.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Fathir Rizqullah Maulana',
                'angkatan_beswan' => 'Beswan 2020',
                'sekarang_dimana' => 'Wirausahawan di bidang edukasi digital',
                'isi_testimoni' => 'Dukungan dari Beasiswa Bersekolah tidak hanya finansial, tetapi juga membentuk karakter saya untuk menjadi wirausahawan yang berdampak.',
                'foto_testimoni' => 'Fathir.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Rifatul Hadi Saputra',
                'angkatan_beswan' => 'Beswan 2023',
                'sekarang_dimana' => 'Freelancer & Content Creator',
                'isi_testimoni' => 'Beasiswa Bersekolah memberi saya kebebasan untuk belajar hal-hal baru tanpa terbebani biaya pendidikan.',
                'foto_testimoni' => 'KakRifat.png',
                'status' => 'inactive',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Ghifari Alamsyah Putra',
                'angkatan_beswan' => 'Beswan 2022',
                'sekarang_dimana' => 'Staf pengajar di SMA Negeri 1 Bandung',
                'isi_testimoni' => 'Beasiswa Bersekolah adalah jembatan bagi saya untuk menjadi pendidik yang terus menginspirasi. Saya sangat berterima kasih.',
                'foto_testimoni' => 'Ghifari.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Julian Dwi Satrio',
                'angkatan_beswan' => 'Beswan 2021',
                'sekarang_dimana' => 'Karyawan BUMN',
                'isi_testimoni' => 'Saya merasa sangat didukung secara moral dan finansial lewat Beasiswa Bersekolah. Lingkungannya sangat suportif dan positif.',
                'foto_testimoni' => 'Julian.png',
                'status' => 'inactive',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Rhea Paramitha Ayuningtyas',
                'angkatan_beswan' => 'Beswan 2020',
                'sekarang_dimana' => 'Mahasiswa S2 Psikologi UNPAD',
                'isi_testimoni' => 'Melalui Beasiswa Bersekolah, saya mendapatkan banyak pelatihan dan bimbingan yang menunjang perkembangan akademik dan personal saya.',
                'foto_testimoni' => 'Rhea.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Tyas Maharani Puspitasari',
                'angkatan_beswan' => 'Beswan 2023',
                'sekarang_dimana' => 'Asisten dosen UPI',
                'isi_testimoni' => 'Terima kasih Beasiswa Bersekolah, saya bisa fokus belajar dan mencapai banyak prestasi tanpa terbebani kondisi ekonomi keluarga.',
                'foto_testimoni' => 'KakTyas.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
            [
                'nama' => 'Santika Nur Azzahra',
                'angkatan_beswan' => 'Beswan 2022',
                'sekarang_dimana' => 'Volunteer NGO Pendidikan',
                'isi_testimoni' => 'Beasiswa Bersekolah menginspirasi saya untuk berbagi manfaat pendidikan kepada lebih banyak orang di komunitas saya.',
                'foto_testimoni' => 'KakSantika.png',
                'status' => 'active',
                'tanggal_input' => $now,
            ],
        ]);
    }
}
