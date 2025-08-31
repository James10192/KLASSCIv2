<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ESBTPDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Génie Civil',
                'code' => 'GC',
                'description' => 'Département de Génie Civil',
                'head_name' => 'Dr. Jean Dupont',
                'head_title' => 'Chef de Département',
                'email' => 'genie.civil@esbtp.edu',
                'phone' => '+123456789',
                'office_location' => 'Bâtiment A, Bureau 101',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Génie Mécanique',
                'code' => 'GM',
                'description' => 'Département de Génie Mécanique',
                'head_name' => 'Dr. Marie Martin',
                'head_title' => 'Chef de Département',
                'email' => 'genie.mecanique@esbtp.edu',
                'phone' => '+123456790',
                'office_location' => 'Bâtiment B, Bureau 201',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Génie Électrique',
                'code' => 'GE',
                'description' => 'Département de Génie Électrique',
                'head_name' => 'Dr. Pierre Dubois',
                'head_title' => 'Chef de Département',
                'email' => 'genie.electrique@esbtp.edu',
                'phone' => '+123456791',
                'office_location' => 'Bâtiment C, Bureau 301',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($departments as $department) {
            DB::table('esbtp_departments')->insert($department);
        }
    }
}
