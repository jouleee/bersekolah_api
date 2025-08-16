<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MentorSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = [
            [
                'name' => 'Ahmad Izuddin Azzam',
                'email' => 'ahmad.izzuddin.azzam@bersekolah.org',
                'photo' => 'Ahmad Izzuddin Azzam_Non Formal.JPG',
            ],
            [
                'name' => 'Abdurrahman Alghifari',
                'email' => 'abdurrahman.alghifari@bersekolah.org',
                'photo' => 'Ghifari.png'
            ],
            [
                'name' => 'Julian Dwi',
                'email' => 'julian.dwi@bersekolah.org',
                'photo' => 'julian.png'
            ],
            [
                'name' => 'Arya Jagadhita',
                'email' => 'arya.jagadhita@bersekolah.org',
                'photo' => 'Rhea.png',
            ],
            [
                'name' => 'Erin Armaida',
                'email' => 'erin.armaida@bersekolah.org',
                'photo' => 'Erin.png',
            ],
            [
                'name' => 'Dinal Azmi',
                'email' => 'dinal.azmi@bersekolah.org',
                'photo' => 'Dinal.png',
            ],
            [
                'name' => 'Fathir vandarvelis',
                'email' => 'fathir.vandarvelis@bersekolah.org',
                'photo' => 'Fathir.png',
            ],
            [
                'name' => 'Dedi',
                'email' => 'dedi@bersekolah.org',
                'photo' => 'Dedi.png',
            ],
            [
                'name' => 'Rifat Syafaat',
                'email' => 'rifat.syafaat@bersekolah.org',
                'photo' => 'kakRifat.png',
            ],
            [
                'name' => 'Andrian Fauzi',
                'email' => 'andrian.fauzi@bersekolah.org',
                'photo' => 'kakAndrian.png',
            ],
            [
                'name' => 'Shantika',
                'email' => 'shantika@bersekolah.org',
                'photo' => 'kakSantika.png',
            ],
            [
                'name' => 'Tyas Ningrum',
                'email' => 'tyas.ningrum@bersekolah.org',
                'photo' => 'kakTyas.png',
            ],
            [
                'name' => 'Pebi Sukamdani',
                'email' => 'pebi.sukamdani@bersekolah.org',
                'photo' => 'kakPebi.png',
            ],
        ];

        DB::table('mentors')->insert($mentors);
    }
}
