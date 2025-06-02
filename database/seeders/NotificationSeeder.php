<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (usually the admin)
        $user = User::first();

        if (!$user) {
            $this->command->info('No users found. Please create a user first.');
            return;
        }

        // Create some test notifications
        $notifications = [
            [
                'user_id' => $user->id,
                'title' => 'Bienvenue dans ESBTP',
                'message' => 'Votre compte a été créé avec succès. Explorez toutes les fonctionnalités disponibles.',
                'type' => 'success',
                'is_read' => false,
                'link' => '/dashboard',
                'sent_by' => null,
            ],
            [
                'user_id' => $user->id,
                'title' => 'Nouvelle année universitaire',
                'message' => 'L\'année universitaire 2024-2025 a été activée. Vous pouvez maintenant créer des classes.',
                'type' => 'info',
                'is_read' => false,
                'link' => '/esbtp/classes',
                'sent_by' => null,
            ],
            [
                'user_id' => $user->id,
                'title' => 'Mise à jour système',
                'message' => 'Le système a été mis à jour avec de nouvelles fonctionnalités. Consultez les notes de version.',
                'type' => 'warning',
                'is_read' => true,
                'link' => '#',
                'sent_by' => null,
            ],
            [
                'user_id' => $user->id,
                'title' => 'Sauvegarde automatique',
                'message' => 'La sauvegarde automatique des données a été effectuée avec succès.',
                'type' => 'success',
                'is_read' => true,
                'link' => '#',
                'sent_by' => null,
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }

        $this->command->info('Test notifications created successfully!');
    }
}
