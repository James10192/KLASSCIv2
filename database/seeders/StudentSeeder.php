<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Trouver l'utilisateur étudiant
        $studentUser = User::where('email', 'student@smartschool.com')->first();

        if ($studentUser) {
            // Vérifier si l'étudiant existe déjà
            $existingStudent = Student::where('user_id', $studentUser->id)->first();

            if (!$existingStudent) {
                // Créer un enregistrement étudiant
                Student::create([
                    'user_id' => $studentUser->id,
                    'admission_no' => 'ADM-' . date('Y') . '-001',
                    'roll_no' => '001',
                    'class_id' => 1, // Assurez-vous que cette classe existe
                    'section_id' => 1, // Assurez-vous que cette section existe
                    'session_id' => 1, // Assurez-vous que cette session existe
                    'father_name' => 'John Doe',
                    'mother_name' => 'Jane Doe',
                    'date_of_birth' => '2000-01-01',
                    'gender' => 'male',
                    'address' => '123 Main St',
                    'city' => 'Anytown',
                    'state' => 'State',
                    'country' => 'Country',
                    'pincode' => '12345',
                    'religion' => 'Not specified',
                    'admission_date' => now(),
                    'blood_group' => 'O+',
                    'height' => '175',
                    'weight' => '70',
                ]);

                $this->command->info('Student record created successfully.');
            } else {
                $this->command->info('Student record already exists.');
            }
        } else {
            $this->command->error('Student user not found.');
        }
    }
}
