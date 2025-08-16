<?php

namespace Database\Seeders;

use App\Models\User;
use Dom\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Membuat akun admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@bersekolah.com',
            'phone' => '081234567890',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
        ]);
        
        // Membuat akun superadmin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@bersekolah.com',
            'phone' => '089876543210',
            'role' => 'superadmin',
            'password' => Hash::make('superadmin123'),
        ]);

        // Membuat akun user biasa untuk testing
        User::create([
            'name' => 'Test User',
            'email' => 'user@bersekolah.com',
            'phone' => '087654321098',
            'role' => 'user',
            'password' => Hash::make('user123'),
        ]);

        User::create([
            'name' => 'Drey Beswan',
            'email' => 'Drey@bersekolah.com',
            'phone' => '087654321091',
            'role' => 'user',
            'password' => Hash::make('drey123'),
        ]);

         User::create([
            'name' => 'Drey Admin',
            'email' => 'DreyAdmin@bersekolah.com',
            'phone' => '087654321092',
            'role' => 'user',
            'password' => Hash::make('dreyadmin123'),
        ]);

         User::create([
            'name' => 'Rhea',
            'email' => 'rhea@gmail.com',
            'phone' => '087654321094',
            'role' => 'user',
            'password' => Hash::make('password'),
        ]);

        // Menjalankan seeder untuk konten bersekolah
        $this->call([
            KontenBersekolahSeeder::class,
            TestimoniSeeder::class,
            FaqSeeder::class,
            NotificationSeeder::class,
            UploadTypeSeeder::class,
            AdditionalUploadSeeder::class,
            BeasiswaApplicationSeeder::class,
            BeasiswaPeriodsSeeder::class,
            BeasiswaRecipientsSeeder::class,
            SettingSeeder::class,
            BerkasCalonBeswanSeeder::class,
            DocumentTypeSeeder::class,
            MentorSeeder::class,
            AnnouncementSeeder::class,
            MediaSosialSeeder::class,
            BeswanSeeder::class,  // Add this line
        ]);
    }
}
