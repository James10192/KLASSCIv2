@extends('install.layout')

@section('title', 'Migration de la base de données')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Migration de la base de données</h2>
        <p class="text-gray-600">Nous allons maintenant créer les tables nécessaires dans votre base de données.</p>
    </div>
    
    <div id="app">
        <div class="space-y-6">
            <!-- Database Status -->
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="font-medium text-gray-800 mb-2">État de la base de données</h3>
                
                <div class="space-y-2">
                    <div class="flex items-center">
                        <i class="fas fa-database mr-2 text-blue-500"></i>
                        <span class="text-gray-700">Base de données : <span class="font-medium">{{ $dbStatus['connected'] ? 'Connectée' : 'Non connectée' }}</span></span>
                    </div>
                    
                    @if($dbStatus['connected'])
                        <div class="flex items-center">
                            <i class="fas fa-table mr-2 text-blue-500"></i>
                            <span class="text-gray-700">Tables existantes : <span class="font-medium">{{ $dbStatus['existing_tables_count'] ?? 0 }}</span></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-code-branch mr-2 text-blue-500"></i>
                            <span class="text-gray-700">Tables de migration : <span class="font-medium">{{ $dbStatus['migration_tables_count'] ?? 0 }}</span></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-file-code mr-2 text-blue-500"></i>
                            <span class="text-gray-700">Fichiers de migration : <span class="font-medium">{{ $dbStatus['migration_files_count'] ?? 0 }}</span></span>
                        </div>
                        
                        @if(isset($dbStatus['multi_table_migrations_count']) && $dbStatus['multi_table_migrations_count'] > 0)
                        <div class="flex items-center">
                            <i class="fas fa-layer-group mr-2 text-purple-500"></i>
                            <span class="text-gray-700">Migrations avec plusieurs tables : <span class="font-medium">{{ $dbStatus['multi_table_migrations_count'] }}</span></span>
                        </div>
                        @endif
                        
                        <div class="flex items-center">
                            <i class="fas fa-percentage mr-2 {{ $dbStatus['match_percentage'] == 100 ? 'text-green-500' : ($dbStatus['match_percentage'] >= 70 ? 'text-yellow-500' : 'text-red-500') }}"></i>
                            <span class="text-gray-700">Correspondance : <span class="font-medium">{{ $dbStatus['match_percentage'] }}%</span></span>
                        </div>
                        
                        <!-- Ajout de l'état des tables ESBTP -->
                        <div class="flex items-center mt-2">
                            <i class="fas fa-table mr-2 {{ isset($dbStatus['installation_status']['esbtp_tables_exist']) && $dbStatus['installation_status']['esbtp_tables_exist'] ? 'text-green-500' : 'text-red-500' }}"></i>
                            <span class="text-gray-700">Tables métier : 
                                <span class="font-medium {{ isset($dbStatus['installation_status']['esbtp_tables_exist']) && $dbStatus['installation_status']['esbtp_tables_exist'] ? 'text-green-600' : 'text-red-600' }}">
                                    {{ isset($dbStatus['installation_status']['esbtp_tables_exist']) && $dbStatus['installation_status']['esbtp_tables_exist'] ? 'Complètes' : 'Incomplètes' }}
                                </span>
                            </span>
                        </div>
                        
                        <!-- Ajout de l'état des modules -->
                        @if(isset($dbStatus['module_status']) && is_array($dbStatus['module_status']))
                        <div class="mt-4 border-t pt-3">
                            <h4 class="font-medium text-gray-800 mb-2">État des modules</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($dbStatus['module_status']['categories'] as $moduleKey => $module)
                                <div class="p-3 {{ $module['complete'] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }} border rounded-md relative">
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $module['complete'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $module['percentage'] }}%
                                        </span>
                                    </div>
                                    
                                    <h5 class="font-medium text-gray-800">{{ ucfirst($moduleKey) }}</h5>
                                    <p class="text-sm text-gray-600 mb-2">{{ $module['description'] ?? 'Module ' . ucfirst($moduleKey) }}</p>
                                    
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-600">{{ count($module['existing']) }}/{{ $module['total'] }} tables</span>
                                        
                                        <div class="ml-2 flex-1 bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $module['complete'] ? 'bg-green-500' : 'bg-yellow-500' }}" 
                                                 style="width: {{ $module['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                    
                                    @if(!empty($module['missing']))
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-600">Tables manquantes :</p>
                                        <p class="text-xs text-yellow-600">{{ implode(', ', array_slice($module['missing'], 0, 5)) }}{{ count($module['missing']) > 5 ? '...' : '' }}</p>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <!-- Affichage de toutes les tables manquantes -->
                        @if(isset($dbStatus['all_tables_status']) && !empty($dbStatus['all_tables_status']['missing_tables']))
                        <div class="mt-4 border-t pt-3">
                            <h4 class="font-medium text-gray-800 mb-2">Toutes les tables manquantes</h4>
                            
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <p class="text-sm text-yellow-700 mb-2">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    <strong>{{ count($dbStatus['all_tables_status']['missing_tables']) }}</strong> tables sont manquantes sur un total de 
                                    <strong>{{ count($dbStatus['all_tables_status']['existing_tables']) + count($dbStatus['all_tables_status']['missing_tables']) }}</strong>.
                                </p>
                                
                                <div class="mt-2 max-h-40 overflow-y-auto">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach($dbStatus['all_tables_status']['missing_tables'] as $missingTable)
                                        <div class="text-xs bg-white p-1.5 rounded border border-yellow-200">
                                            {{ $missingTable }}
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Explication des tables ESBTP -->
                        <div class="mt-3 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                                Les tables métier de KLASSCI sont essentielles pour le fonctionnement du système, y compris les tables de rôles et permissions
                                qui sont <strong>requises</strong> et non supplémentaires. Si elles ne sont pas complètes,
                                vous devez exécuter les migrations pour assurer le bon fonctionnement de l'application.
                            </p>
                        </div>
                        
                        <!-- Barre de progression visuelle pour le pourcentage de correspondance -->
                        <div class="mt-1 flex items-center">
                            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="h-2 rounded-full {{ $dbStatus['match_percentage'] == 100 ? 'bg-green-500' : ($dbStatus['match_percentage'] >= 70 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                     style="width: {{ $dbStatus['match_percentage'] }}%"></div>
                            </div>
                        </div>
                        
                        <!-- Messages basés sur le pourcentage de correspondance -->
                        @if($dbStatus['match_percentage'] == 100)
                            <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-green-700 font-semibold">Correspondance parfaite (100%) !</p>
                                        <p class="text-sm text-green-700 mt-1">Toutes les tables requises existent déjà dans la base de données.</p>
                                        <p class="text-sm text-green-700 mt-1"><strong>Note:</strong> Les tables additionnelles pour les rôles et permissions sont <strong>requises</strong> pour le bon fonctionnement de KLASSCI.</p>
                                    </div>
                                </div>
                            </div>
                        @elseif($dbStatus['match_percentage'] >= 70)
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-yellow-700 font-semibold">Correspondance partielle ({{ $dbStatus['match_percentage'] }}%)</p>
                                        <p class="text-sm text-yellow-700 mt-1">Certaines tables requises sont présentes, mais d'autres sont manquantes. Il est recommandé d'exécuter la migration.</p>
                                        
                                        @if(isset($dbStatus['missing_tables']) && count($dbStatus['missing_tables']) > 0)
                                        <div class="mt-2">
                                            <p class="text-xs font-semibold text-yellow-700">Tables manquantes ({{ count($dbStatus['missing_tables']) }}) :</p>
                                            <p class="text-xs text-yellow-600">{{ implode(', ', array_slice($dbStatus['missing_tables'], 0, 10)) }}{{ count($dbStatus['missing_tables']) > 10 ? '...' : '' }}</p>
                                        </div>
                                        @endif
                                        
                                        @if(isset($dbStatus['extra_tables']) && count($dbStatus['extra_tables']) > 0)
                                        <div class="mt-2">
                                            <p class="text-xs font-semibold text-yellow-700">Tables supplémentaires ({{ count($dbStatus['extra_tables']) }}) :</p>
                                            <p class="text-xs text-yellow-600">{{ implode(', ', array_slice($dbStatus['extra_tables'], 0, 10)) }}{{ count($dbStatus['extra_tables']) > 10 ? '...' : '' }}</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-times-circle text-red-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-red-700 font-semibold">Correspondance insuffisante ({{ $dbStatus['match_percentage'] }}%)</p>
                                        <p class="text-sm text-red-700 mt-1">La plupart des tables requises sont manquantes. Vous devez exécuter la migration complète.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if($dbStatus['existing_tables_count'] > 0 && !$dbStatus['all_tables_exist'])
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-yellow-700">Des tables existent déjà dans la base de données, mais certaines tables requises sont manquantes.</p>
                                        <p class="text-sm text-yellow-700 mt-1">Toutes les tables existantes seront préservées et les tables manquantes seront créées.</p>
                                        
                                        @if(isset($dbStatus['missing_tables']) && count($dbStatus['missing_tables']) > 0)
                                        <div class="mt-2">
                                            <p class="text-xs font-semibold text-yellow-700">Tables manquantes ({{ count($dbStatus['missing_tables']) }}) :</p>
                                            <p class="text-xs text-yellow-600">{{ implode(', ', array_slice($dbStatus['missing_tables'], 0, 10)) }}{{ count($dbStatus['missing_tables']) > 10 ? '...' : '' }}</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($dbStatus['existing_tables_count'] > 0 && $dbStatus['all_tables_exist'])
                            <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-green-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-green-700 font-semibold">Toutes les tables requises existent déjà dans la base de données.</p>
                                        <p class="text-sm text-green-700 mt-1">Vous pouvez passer directement à l'étape suivante sans exécuter les migrations.</p>
                                        
                                        @if($dbStatus['match_percentage'] >= 90)
                                        <div class="mt-2 p-2 bg-green-100 rounded">
                                            <p class="text-xs text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Excellente correspondance ({{ $dbStatus['match_percentage'] }}%) entre les tables de migration et les tables existantes.
                                            </p>
                                        </div>
                                        @endif
                                        
                                        @if(isset($dbStatus['extra_tables']) && count($dbStatus['extra_tables']) > 0)
                                        <div class="mt-2 p-2 bg-blue-100 rounded">
                                            <p class="text-xs text-blue-800">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                {{ count($dbStatus['extra_tables']) }} tables supplémentaires trouvées dans la base de données.
                                            </p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif(!$dbStatus['database_exists'])
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                                    <div>
                                        <p class="text-sm text-blue-700">La base de données n'existe pas encore.</p>
                                        <p class="text-sm text-blue-700 mt-1">Elle sera créée automatiquement lors de la migration.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            
            <!-- Aide sur le pourcentage de correspondance -->
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center mb-2">
                    <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                    <h3 class="font-medium text-gray-800">Comprendre le pourcentage de correspondance</h3>
                </div>
                
                <div class="text-sm text-gray-700 space-y-2">
                    <p>Le <strong>pourcentage de correspondance</strong> indique à quel point votre base de données actuelle correspond à la structure attendue par l'application.</p>
                    
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                        <p><strong>100%</strong> : Toutes les tables requises existent. Vous pouvez passer la migration.</p>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                        <p><strong>70-99%</strong> : La plupart des tables existent, mais certaines sont manquantes. Il est recommandé d'exécuter la migration.</p>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                        <p><strong>0-69%</strong> : Trop de tables manquantes. Vous devez exécuter la migration complète.</p>
                    </div>
                </div>
            </div>
            
            <!-- Migration Console -->
            <div class="bg-gray-900 rounded-lg overflow-hidden shadow-lg" id="migration-console">
                <div class="p-4 border-b border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-white">Console de migration</h3>
                    <div class="text-sm text-gray-400">@{{ progress }}%</div>
                </div>
                
                <div class="relative">
                    <!-- Messages de statut de la base de données -->
                    @if(session('database_created'))
                    <div class="absolute top-0 left-0 right-0 p-3 bg-green-900 bg-opacity-90 border-b border-green-700 z-10">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-400 mt-0.5 mr-2"></i>
                            <div>
                                <p class="text-sm text-green-200 font-medium">Base de données créée avec succès !</p>
                                <p class="text-xs text-green-300 mt-1">La base de données a été créée automatiquement avant la migration.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if(session('db_connection_error'))
                    <div class="absolute top-0 left-0 right-0 p-3 bg-red-900 bg-opacity-90 border-b border-red-700 z-10">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 mr-2"></i>
                            <div>
                                <p class="text-sm text-red-200 font-medium">Erreur de connexion à la base de données</p>
                                <p class="text-xs text-red-300 mt-1">{{ session('db_connection_error') }}</p>
                                <p class="text-xs text-red-300 mt-1">Veuillez vérifier vos paramètres de connexion.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Barre de progression -->
                    <div class="h-1 bg-gray-800">
                        <div class="h-1 bg-blue-500 transition-all duration-300" :style="{ width: progress + '%' }"></div>
                    </div>
                    
                    <!-- Console output -->
                    <div class="console" ref="console">
                        <div v-if="output" v-html="formattedOutput"></div>
                        <div v-else class="text-gray-400">En attente du démarrage des migrations...</div>
                    </div>
                </div>
            </div>
            
            <div v-if="error" class="alert alert-error">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>@{{ error }}</span>
                </div>
            </div>
            
            <div v-if="success" class="alert alert-success">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>@{{ success }}</span>
                </div>
            </div>
            
            <div class="flex justify-between">
                <a href="{{ route('install.database') }}" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Retour</span>
                </a>
                
                <div class="flex space-x-4">
                    @if($dbStatus['match_percentage'] == 100)
                    <button @click="skipMigration" class="btn-success">
                        <i class="fas fa-forward mr-2"></i>
                        <span>Passer la migration</span>
                    </button>
                    @elseif($dbStatus['existing_tables_count'] > 0 && $dbStatus['all_tables_exist'])
                    <a href="{{ route('install.admin') }}" class="btn-success">
                        <i class="fas fa-forward mr-2"></i>
                        <span>Passer à l'étape suivante</span>
                    </a>
                    @endif
                    
                    <button @click="runMigration" class="btn-primary" :disabled="loading">
                        <span v-if="!loading">Démarrer la migration</span>
                        <span v-else class="flex items-center">
                            <i class="fas fa-circle-notch fa-spin mr-2"></i>
                            <span>Migration en cours...</span>
                        </span>
                    </button>
                    
                    <button v-if="completed" @click="goToNextStep" class="btn-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>Continuer</span>
                    </button>
                </div>
            </div>
            
            <!-- Indicateur de progression amélioré -->
            <div v-if="loading" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center">
                    <i class="fas fa-spinner fa-spin text-blue-500 mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-blue-700">Migration en cours...</p>
                        <p class="text-sm text-blue-600 mt-1">Cette opération peut prendre quelques minutes. Veuillez patienter.</p>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-blue-600 mb-1">
                        <span>Progression</span>
                        <span>@{{ progress.toFixed(0) }}%</span>
                    </div>
                    <div class="h-2 bg-blue-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>
            </div>
            
            <!-- Alertes pour les erreurs de connexion à la base de données -->
            @if(session('db_connection_error'))
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                    <div>
                        <p class="font-medium text-red-700">Erreur de connexion à la base de données</p>
                        <p class="text-sm text-red-600 mt-1">{{ session('db_connection_error') }}</p>
                        <div class="mt-3 p-3 bg-white rounded border border-red-100">
                            <h4 class="text-sm font-medium text-red-700 mb-2">Solutions possibles :</h4>
                            <ul class="text-xs text-red-600 list-disc pl-4 space-y-1">
                                <li>Vérifiez que votre serveur MySQL est bien démarré</li>
                                <li>Vérifiez que les identifiants sont corrects (nom d'utilisateur, mot de passe)</li>
                                <li>Vérifiez que l'utilisateur a les droits suffisants</li>
                                <li>Assurez-vous que le port MySQL spécifié est correct (généralement 3306)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Alertes spécifiques pour les erreurs connues -->
            <div v-if="hasMissingTableError" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                    <div>
                        <p class="font-medium text-yellow-700">Attention : Table manquante détectée</p>
                        <p class="text-sm text-yellow-600 mt-1">La table "esbtp_unites_enseignement" est mentionnée dans les migrations mais a été supprimée des spécifications. Ceci est normal et n'affecte pas le fonctionnement de l'application.</p>
                        <p class="text-sm text-yellow-600 mt-2">Vous pouvez continuer l'installation normalement.</p>
                    </div>
                </div>
            </div>
            
            <!-- Options avancées -->
            <div class="mt-6 bg-white rounded-lg p-4 border border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 mb-3">Options avancées</h3>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="run_seeders" class="text-sm font-medium text-gray-700">Exécuter les seeders</label>
                            <p class="text-xs text-gray-500">Créer les données de base (rôles, utilisateurs par défaut)</p>
                        </div>
                        <div class="relative inline-block w-10 mr-2 align-middle select-none">
                            <input type="checkbox" id="run_seeders" v-model="advancedOptions.runSeeders" class="toggle-checkbox absolute block w-6 h-6 rounded-full appearance-none cursor-pointer">
                            <label for="run_seeders" class="toggle-label block overflow-hidden cursor-pointer"></label>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between" v-if="advancedOptions.runSeeders">
                        <div>
                            <label for="run_esbtp_seeders" class="text-sm font-medium text-gray-700">Exécuter les seeders métier</label>
                            <p class="text-xs text-gray-500">Créer les filières, niveaux d'études et années universitaires</p>
                            <p class="text-xs text-green-600">(Recommandé pour la première installation)</p>
                        </div>
                        <div class="relative inline-block w-10 mr-2 align-middle select-none">
                            <input type="checkbox" id="run_esbtp_seeders" v-model="advancedOptions.runESBTPSeeders" class="toggle-checkbox absolute block w-6 h-6 rounded-full appearance-none cursor-pointer">
                            <label for="run_esbtp_seeders" class="toggle-label block overflow-hidden cursor-pointer"></label>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="force_migrate" class="text-sm font-medium text-gray-700">Forcer la migration</label>
                            <p class="text-xs text-gray-500 text-red-500">⚠️ Supprime toutes les tables existantes</p>
                        </div>
                        <div class="relative inline-block w-10 mr-2 align-middle select-none">
                            <input type="checkbox" id="force_migrate" v-model="advancedOptions.forceMigrate" class="toggle-checkbox absolute block w-6 h-6 rounded-full appearance-none cursor-pointer">
                            <label for="force_migrate" class="toggle-label block overflow-hidden cursor-pointer"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        new Vue({
            el: '#app',
            data: {
                loading: false,
                started: false,
                completed: false,
                error: null,
                success: null,
                output: '',
                progress: 0,
                nextUrl: '',
                advancedOptions: {
                    runSeeders: true,
                    runESBTPSeeders: true,
                    forceMigrate: false
                },
                migrationErrors: null
            },
            computed: {
                formattedOutput() {
                    if (!this.output) return '';
                    
                    return this.output
                        .split('\n')
                        .map(line => {
                            if (line.includes('Migrating:')) {
                                return `<span class="text-yellow-400">${line}</span>`;
                            } else if (line.includes('Migrated:')) {
                                return `<span class="text-green-400">${line}</span>`;
                            } else if (line.includes('error') || line.includes('Error')) {
                                return `<span class="text-red-400">${line}</span>`;
                            } else if (line.includes('Seeding:')) {
                                return `<span class="text-blue-400">${line}</span>`;
                            } else if (line.includes('Seeded:')) {
                                return `<span class="text-green-400">${line}</span>`;
                            } else if (line.includes('Creating table:')) {
                                return `<span class="text-purple-400">${line}</span>`;
                            }
                            return line;
                        })
                        .join('<br>');
                },
                hasMissingTableError() {
                    if (!this.error) return false;
                    return this.error.includes('esbtp_unites_enseignement') || 
                           (this.migrationErrors && this.migrationErrors.includes('esbtp_unites_enseignement'));
                }
            },
            methods: {
                runMigration() {
                    this.loading = true;
                    this.started = true;
                    this.error = null;
                    this.success = null;
                    this.output = 'Démarrage des migrations...\n';
                    this.progress = 5;
                    this.migrationErrors = null;
                    
                    // Affichage des options utilisées
                    this.output += `🔧 Options sélectionnées :\n`;
                    this.output += `  • Exécuter les seeders : ${this.advancedOptions.runSeeders ? '✅ Oui' : '❌ Non'}\n`;
                    if (this.advancedOptions.runSeeders) {
                        this.output += `  • Exécuter les seeders métier : ${this.advancedOptions.runESBTPSeeders ? '✅ Oui' : '❌ Non'}\n`;
                    }
                    this.output += `  • Forcer la migration : ${this.advancedOptions.forceMigrate ? '⚠️ Oui' : '❌ Non'}\n\n`;
                    
                    // Scroll to bottom of console
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                    
                    // Simulation de progression plus réaliste
                    this.output += `⏳ Préparation de la base de données...\n`;
                    let progressSteps = [
                        { target: 10, text: 'Vérification de la connexion...' },
                        { target: 20, text: 'Préparation des migrations...' },
                        { target: 30, text: 'Création des tables...' },
                        { target: 60, text: 'Application des migrations...' },
                        { target: 80, text: 'Configuration des données...' },
                        { target: 90, text: 'Finalisation...' }
                    ];
                    
                    let currentStep = 0;
                    
                    this.progressInterval = setInterval(() => {
                        if (currentStep < progressSteps.length && this.progress < progressSteps[currentStep].target) {
                            this.progress += 0.5;
                            if (this.progress >= progressSteps[currentStep].target) {
                                this.output += `\n${progressSteps[currentStep].text}\n`;
                                this.$nextTick(() => {
                                    this.scrollToBottom();
                                });
                                currentStep++;
                            }
                        }
                    }, 300);
                    
                    axios.post('{{ route("install.run-migration") }}', {
                        runSeeders: this.advancedOptions.runSeeders,
                        runESBTPSeeders: this.advancedOptions.runESBTPSeeders,
                        forceMigrate: this.advancedOptions.forceMigrate
                    })
                        .then(response => {
                            clearInterval(this.progressInterval);
                            
                            if (response.data.status === 'success') {
                                this.success = response.data.message;
                                this.output += `\n✅ ${response.data.message}\n`;
                                
                                // Afficher un message si la base de données a été créée
                                if (response.data.database_created) {
                                    this.output += `🔄 Base de données créée automatiquement.\n`;
                                }
                                
                                // Afficher un message sur les seeders métier
                                if (response.data.esbtp_seeded) {
                                    this.output += `📊 Les données métier ont été initialisées.\n`;
                                    
                                    // Vérifier si les données métier sont complètes
                                    if (response.data.esbtp_data_check && !response.data.esbtp_data_check.success) {
                                        this.output += `⚠️ Attention : Certaines données métier n'ont pas pu être créées : ${response.data.esbtp_data_check.missing_data.join(', ')}\n`;
                                    }
                                }
                                
                                // Vérifier s'il y a des erreurs spécifiques
                                if (response.data.migration_errors) {
                                    this.migrationErrors = response.data.migration_errors;
                                    
                                    // Si c'est une erreur relative à esbtp_unites_enseignement, on la traite différemment
                                    if (this.hasMissingTableError) {
                                        this.output += `\n⚠️ Avertissement : ${response.data.migration_errors}\n`;
                                        this.output += `ℹ️ Cet avertissement est normal et ne bloque pas l'installation. La table mentionnée a été supprimée des spécifications.\n`;
                                    } else {
                                        this.output += `\n⚠️ Avertissement : ${response.data.migration_errors}\n`;
                                    }
                                }
                                
                                this.progress = 100;
                                this.completed = true;
                                this.nextUrl = response.data.redirect;
                                
                                // Réduire le loading après un délai pour montrer le 100%
                                setTimeout(() => {
                                    this.loading = false;
                                }, 1000);
                            } else {
                                this.error = response.data.message;
                                this.output += `\n❌ Erreur: ${response.data.message}\n`;
                                this.progress = 0;
                                this.loading = false;
                                
                                if (response.data.migration_errors) {
                                    this.migrationErrors = response.data.migration_errors;
                                }
                            }
                            
                            // Scroll to bottom of console
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        })
                        .catch(error => {
                            clearInterval(this.progressInterval);
                            this.loading = false;
                            
                            if (error.response && error.response.data) {
                                this.error = error.response.data.message || 'Une erreur est survenue lors de la migration.';
                                this.output += `\n❌ Erreur: ${this.error}\n`;
                                
                                // Si l'erreur concerne l'accès à la base de données
                                if (this.error.includes('Access denied') || this.error.includes('Accès refusé')) {
                                    this.output += `\n🔑 Problème d'authentification MySQL : Vérifiez que le nom d'utilisateur et le mot de passe sont corrects.\n`;
                                } else if (this.error.includes('Connection refused') || this.error.includes('Connexion refusée')) {
                                    this.output += `\n🔌 Problème de connexion au serveur MySQL : Vérifiez que le serveur est bien démarré et accessible.\n`;
                                }
                                
                                if (error.response.data.output) {
                                    this.output += '\n' + error.response.data.output;
                                }
                                
                                if (error.response.data.migration_errors) {
                                    this.migrationErrors = error.response.data.migration_errors;
                                }
                            } else {
                                this.error = 'Une erreur est survenue lors de la migration.';
                                this.output += `\n❌ Erreur: ${this.error}\n`;
                            }
                            
                            this.progress = 0;
                            
                            // Scroll to bottom of console
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        });
                },
                goToNextStep() {
                    window.location.href = this.nextUrl || '{{ route("install.admin") }}';
                },
                skipMigration() {
                    this.loading = true;
                    this.output = 'Vérification des migrations installées...\n';
                    
                    // Vérifier si toutes les migrations sont réellement exécutées
                    axios.post('{{ route("install.check-migrations") }}')
                        .then(response => {
                            this.loading = false;
                            
                            if (response.data.can_skip_migration) {
                                this.output += 'Vérification réussie : ' + response.data.match_percentage + '% des tables sont présentes.\n';
                                this.output += 'Migration ignorée - Les tables essentielles existent déjà.\n';
                                
                                // Afficher les modules complets
                                this.output += '\nStatut des modules :\n';
                                Object.entries(response.data.modules_status.categories).forEach(([module, status]) => {
                                    const icon = status.complete ? '✅' : '⚠️';
                                    this.output += `${icon} ${module}: ${status.existing.length}/${status.total} tables (${status.percentage}%)\n`;
                                });
                                
                                this.output += '\nRedirection vers l\'étape suivante...\n';
                                
                                this.progress = 100;
                                this.completed = true;
                                this.success = 'Migration ignorée avec succès. Les tables essentielles existent déjà.';
                                
                                // Rediriger après un court délai
                                setTimeout(() => {
                                    window.location.href = '{{ route("install.admin") }}';
                                }, 2000);
                            } else {
                                this.output += 'Impossible de sauter cette étape : ' + response.data.message + '\n';
                                
                                if (response.data.all_tables_status.missing_tables_count > 0) {
                                    this.output += '\nTables manquantes (' + response.data.all_tables_status.missing_tables_count + ') :\n';
                                    response.data.all_tables_status.missing_tables.forEach(table => {
                                        this.output += '- ' + table + '\n';
                                    });
                                }
                                
                                this.output += '\nVeuillez exécuter les migrations pour continuer.\n';
                                this.error = 'Impossible de sauter la migration : ' + response.data.message;
                            }
                            
                            // Scroll to bottom of console
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        })
                        .catch(error => {
                            this.loading = false;
                            this.error = 'Erreur lors de la vérification des migrations';
                            this.output += '\nErreur: ' + this.error + '\n';
                            this.output += 'Veuillez exécuter les migrations pour continuer.\n';
                            
                            // Scroll to bottom of console
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        });
                },
                scrollToBottom() {
                    const console = this.$refs.console;
                    console.scrollTop = console.scrollHeight;
                }
            }
        });
    </script>
@endsection 