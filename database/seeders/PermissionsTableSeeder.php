// Permissions pour les cycles de formation
Permission::create(['name' => 'view cycles', 'description' => 'Voir les cycles de formation']);
Permission::create(['name' => 'create cycles', 'description' => 'Créer des cycles de formation']);
Permission::create(['name' => 'edit cycles', 'description' => 'Modifier les cycles de formation']);
Permission::create(['name' => 'delete cycles', 'description' => 'Archiver les cycles de formation']);
Permission::create(['name' => 'restore cycles', 'description' => 'Restaurer les cycles de formation archivés']);
Permission::create(['name' => 'force delete cycles', 'description' => 'Supprimer définitivement les cycles de formation']);
