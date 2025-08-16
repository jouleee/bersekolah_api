<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            // Dokumen Wajib
            [
                'code' => 'student_proof',
                'name' => 'Bukti Status Siswa',
                'description' => 'Kartu pelajar atau surat keterangan dari sekolah',
                'category' => 'wajib',
                'is_required' => true,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png', 'pdf']),
                'max_file_size' => 5242880, // 5MB
                'is_active' => true,
            ],
            [
                'code' => 'identity_proof',
                'name' => 'Identitas Diri',
                'description' => 'KTP/KK atau identitas resmi lainnya',
                'category' => 'wajib',
                'is_required' => true,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png', 'pdf']),
                'max_file_size' => 5242880, // 5MB
                'is_active' => true,
            ],
            [
                'code' => 'photo',
                'name' => 'Foto Diri',
                'description' => 'Foto formal dengan latar belakang merah/biru',
                'category' => 'wajib',
                'is_required' => true,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png']),
                'max_file_size' => 2097152, // 2MB
                'is_active' => true,
            ],

            // Dokumen Sosial Media
            [
                'code' => 'instagram_follow',
                'name' => 'Bukti Follow Instagram',
                'description' => 'Screenshot bukti follow @ber.sekolah',
                'category' => 'sosial_media',
                'is_required' => true,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png']),
                'max_file_size' => 5242880,
                'is_active' => true,
            ],
            [
                'code' => 'twibbon_post',
                'name' => 'Bukti Postingan Twibbon',
                'description' => 'Screenshot postingan twibbon Anda',
                'category' => 'sosial_media',
                'is_required' => true,
                'allowed_formats' => json_encode(['jpg', 'jpeg', 'png']),
                'max_file_size' => 5242880,
                'is_active' => true,
            ],

            // Dokumen Pendukung - DIPERBAIKI
            [
                'code' => 'achievement_certificate',
                'name' => 'Sertifikat Prestasi',
                'description' => 'Sertifikat lomba, kompetisi, atau prestasi akademik/non-akademik. Jika memiliki beberapa sertifikat, harap digabungkan menjadi satu file PDF.',
                'category' => 'pendukung',
                'is_required' => false,
                'allowed_formats' => json_encode(['pdf']),
                'max_file_size' => 5242880, // 5MB
                'is_active' => true,
            ],
            [
                'code' => 'essay_motivation',
                'name' => 'Essay Motivasi',
                'description' => 'Essay atau surat motivasi beasiswa',
                'category' => 'pendukung',
                'is_required' => false,
                'allowed_formats' => json_encode(['pdf', 'doc', 'docx']),
                'max_file_size' => 5242880, // 5MB
                'is_active' => true,
            ],

        ];

        foreach ($documentTypes as $docType) {
            DocumentType::updateOrCreate(
                ['code' => $docType['code']],
                $docType
            );
        }
    }
}