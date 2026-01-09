@extends('install.layout')

@section('title', 'Installation terminée')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800">Installation terminée avec succès!</h2>
        <p class="text-gray-600 mt-2">Votre application KLASSCI est maintenant prête à être utilisée</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="space-y-6">
            <!-- Résumé de l'installation -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Résumé de l'installation</h3>
                
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Base de données configurée</p>
                            <p class="text-xs text-gray-500">Connexion établie avec succès</p>
                        </div>
                    </li>
                    
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Tables créées</p>
                            <p class="text-xs text-gray-500">Migrations exécutées avec succès</p>
                        </div>
                    </li>
                    
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Administrateur créé</p>
                            <p class="text-xs text-gray-500">Compte administrateur configuré</p>
                        </div>
                    </li>
                    
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Fichier d'environnement configuré</p>
                            <p class="text-xs text-gray-500">Paramètres d'application enregistrés</p>
                        </div>
                    </li>
                </ul>
            </div>
            
            <!-- Informations de connexion -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Informations de connexion</h3>
                
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm text-gray-700 mb-4">Vous pouvez vous connecter à l'application avec les identifiants suivants:</p>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                        <h4 class="text-sm font-semibold text-blue-800 mb-2">Identifiants administrateur</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Nom:</p>
                                <p class="text-sm font-medium text-gray-800">{{ session('admin_name') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nom d'utilisateur:</p>
                                <p class="text-sm font-medium text-gray-800">{{ session('admin_username') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email:</p>
                                <p class="text-sm font-medium text-blue-800" id="admin-email">{{ session('admin_email') }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-xs text-gray-500">Mot de passe:</p>
                                <p class="text-sm font-medium text-blue-800">{{ session('admin_password') }}</p>
                                <p class="text-xs text-red-500 mt-1">⚠️ Assurez-vous de conserver ces informations en lieu sûr</p>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Informations de l'école</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Nom de l'école:</p>
                            <p class="text-sm font-medium text-gray-800">{{ session('school_name') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email de contact:</p>
                            <p class="text-sm font-medium text-gray-800">{{ session('school_email') }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500">Adresse:</p>
                            <p class="text-sm font-medium text-gray-800">{{ session('school_address') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Prochaines étapes -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-4">Prochaines étapes</h3>
                
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-arrow-right text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Configurer les paramètres de l'école</p>
                            <p class="text-xs text-gray-500">Définir les années scolaires, les classes et les matières</p>
                        </div>
                    </li>
                    
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-arrow-right text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Ajouter des enseignants</p>
                            <p class="text-xs text-gray-500">Créer des comptes pour les enseignants de votre école</p>
                        </div>
                    </li>
                    
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-arrow-right text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">Ajouter des élèves</p>
                            <p class="text-xs text-gray-500">Enregistrer les élèves et les assigner à leurs classes</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Bouton de finalisation -->
        <div class="mt-8 text-center">
            <a href="{{ route('install.finalize.get') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-sign-in-alt mr-2"></i> Accéder à l'application
            </a>
        </div>
    </div>

    <!-- Avertissement pour les données ESBTP manquantes -->
    @if(session('esbtp_warning'))
    <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <span class="font-medium">Attention :</span> 
                    Certaines données ESBTP n'ont pas été correctement initialisées.
                </p>
                @if(isset(session('esbtp_missing_data')[0]))
                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                    @foreach(session('esbtp_missing_data') as $missingData)
                    <li>{{ $missingData }}</li>
                    @endforeach
                </ul>
                @endif
                <p class="mt-2 text-sm text-yellow-700">
                    Vous devrez peut-être exécuter manuellement les seeders ESBTP ou créer ces données via l'interface d'administration.
                </p>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Information sur la connexion -->
    <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <span class="font-medium">Astuce de connexion :</span> 
                    Vous pouvez vous connecter en utilisant soit votre nom d'utilisateur, soit votre adresse email.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection 