<?php

namespace Database\Seeders;

use App\Models\MediaSosial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MediaSosialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing records to avoid duplicates
        MediaSosial::truncate();
        
        // Create the default media sosial entry
        MediaSosial::create([
            'twibbon_link' => 'https://twb.nz/bersekolah2025',
            'instagram_link' => 'https://instagram.com/ber.sekolah',
            'link_grup_beasiswa' => 'https://chat.whatsapp.com/DBWgEhlvkz3E0SqpdvIL1q',
            'whatsapp_number' => '+6281234567890'
        ]);
    }
}
