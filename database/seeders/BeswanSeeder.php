<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Beswan;
use App\Models\User;
use App\Models\SekolahBeswan;
use App\Models\AlamatBeswan;
use App\Models\KeluargaBeswan;

class BeswanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample users first
        $users = [
            [
                'name' => 'Ahmad Rizki Pratama',
                'email' => 'ahmad.rizki@example.com',
                'phone' => '081234567801',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@example.com',
                'phone' => '081234567802',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'phone' => '081234567803',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Maya Sari Dewi',
                'email' => 'maya.sari@example.com',
                'phone' => '081234567804',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@example.com',
                'phone' => '081234567805',
                'password' => bcrypt('password'),
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = User::create($userData);
        }

        // Create sample beswan data
        for ($i = 0; $i < 5; $i++) {
            $user = $createdUsers[$i];

            // Create Beswan
            $beswan = Beswan::create([
                'user_id' => $user->id,
                'nama_panggilan' => explode(' ', $user->name)[0],
                'tempat_lahir' => ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang'][$i],
                'tanggal_lahir' => fake()->date(),
                'jenis_kelamin' => $i % 2 == 0 ? 'Laki-laki' : 'Perempuan',
                'agama' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha'][$i],
            ]);

            // Create SekolahBeswan
            $sekolah = new SekolahBeswan([
                'asal_sekolah' => ['Universitas Indonesia', 'Institut Teknologi Bandung', 'Universitas Gadjah Mada', 'Universitas Airlangga', 'Universitas Padjadjaran'][$i],
                'daerah_sekolah' => ['Depok, Jawa Barat', 'Bandung, Jawa Barat', 'Yogyakarta', 'Surabaya, Jawa Timur', 'Bandung, Jawa Barat'][$i],
                'tingkat_kelas' => 'S1',
                'jurusan' => ['Teknik Informatika', 'Teknik Elektro', 'Ekonomi', 'Kedokteran', 'Hukum'][$i],
            ]);
            $sekolah->beswan_id = $beswan->id;
            $sekolah->save();

            // Create Address
            AlamatBeswan::create([
                'beswan_id' => $beswan->id,
                'alamat_lengkap' => 'Jl. Contoh No. ' . ($i + 1),
                'rt' => '001',
                'rw' => '002',
                'kelurahan_desa' => ['Menteng', 'Setiabudi', 'Kuningan', 'Senayan', 'Sudirman'][$i],
                'kecamatan' => ['Menteng', 'Setiabudi', 'Kuningan', 'Kebayoran', 'Tanah Abang'][$i],
                'kota_kabupaten' => ['Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Barat', 'Jakarta Utara'][$i],
                'provinsi' => 'DKI Jakarta',
                'kode_pos' => '1234' . $i,
                'nomor_telepon' => '08123456' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'kontak_darurat' => '08765432' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => $user->email
            ]);

            // Create Family
            KeluargaBeswan::create([
                'beswan_id' => $beswan->id,
                'nama_ayah' => 'Ayah ' . $user->name,
                'nama_ibu' => 'Ibu ' . $user->name,
                'pekerjaan_ayah' => ['PNS', 'Wiraswasta', 'Petani', 'Buruh', 'Guru'][$i],
                'pekerjaan_ibu' => ['Ibu Rumah Tangga', 'PNS', 'Wiraswasta', 'Guru', 'Perawat'][$i],
                'penghasilan_ayah' => ['3000000', '4000000', '2500000', '3500000', '4500000'][$i],
                'penghasilan_ibu' => ['0', '3000000', '2000000', '3000000', '3500000'][$i],
                'jumlah_saudara_kandung' => (string)random_int(0, 4),
                'jumlah_tanggungan' => (string)random_int(1, 5)
            ]);
        }
    }
}
