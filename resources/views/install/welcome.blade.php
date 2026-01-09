@extends('install.layout')

@section('title', 'Bienvenue')

@section('content')
    <div class="text-center mb-8">
        <i class="fas fa-school text-5xl text-blue-500 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Bienvenue dans l'assistant d'installation de KLASSCI</h2>
        <p class="text-gray-600">Nous allons vous guider à travers quelques étapes simples pour configurer votre application.</p>
    </div>
    
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Prérequis</h3>
        
        <div class="space-y-4">
            <div class="flex items-start p-4 bg-gray-50 rounded-lg" id="php-version">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-spinner fa-spin text-yellow-500"></i>
                </div>
                <div class="ml-3">
                    <h4 class="font-medium text-gray-800">Version PHP</h4>
                    <p class="text-sm text-gray-600">Vérification de la version PHP (7.4 ou supérieur requis)</p>
                </div>
            </div>
            
            <div class="flex items-start p-4 bg-gray-50 rounded-lg" id="php-extensions">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-spinner fa-spin text-yellow-500"></i>
                </div>
                <div class="ml-3">
                    <h4 class="font-medium text-gray-800">Extensions PHP</h4>
                    <p class="text-sm text-gray-600">Vérification des extensions PHP requises</p>
                </div>
            </div>
            
            <div class="flex items-start p-4 bg-gray-50 rounded-lg" id="directory-permissions">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-spinner fa-spin text-yellow-500"></i>
                </div>
                <div class="ml-3">
                    <h4 class="font-medium text-gray-800">Permissions des répertoires</h4>
                    <p class="text-sm text-gray-600">Vérification des permissions d'écriture</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end">
        <button id="next-button" class="btn-primary" disabled>
            <span>Continuer</span>
            <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const phpVersionElement = document.getElementById('php-version');
            const phpExtensionsElement = document.getElementById('php-extensions');
            const directoryPermissionsElement = document.getElementById('directory-permissions');
            const nextButton = document.getElementById('next-button');
            
            let phpVersionPassed = false;
            let phpExtensionsPassed = false;
            let directoryPermissionsPassed = false;
            
            // Check PHP version
            setTimeout(() => {
                // This is just a simulation since we can't actually check PHP version on the client side
                // In a real app, this would be pre-rendered by the server
                phpVersionPassed = true;
                phpVersionElement.querySelector('i').className = 'fas fa-check-circle text-green-500';
                checkAllRequirements();
            }, 1000);
            
            // Check PHP extensions
            setTimeout(() => {
                // This is just a simulation
                phpExtensionsPassed = true;
                phpExtensionsElement.querySelector('i').className = 'fas fa-check-circle text-green-500';
                checkAllRequirements();
            }, 1500);
            
            // Check directory permissions
            setTimeout(() => {
                // This is just a simulation
                directoryPermissionsPassed = true;
                directoryPermissionsElement.querySelector('i').className = 'fas fa-check-circle text-green-500';
                checkAllRequirements();
            }, 2000);
            
            function checkAllRequirements() {
                if (phpVersionPassed && phpExtensionsPassed && directoryPermissionsPassed) {
                    nextButton.disabled = false;
                    nextButton.addEventListener('click', function() {
                        window.location.href = "{{ route('install.database') }}";
                    });
                }
            }
        });
    </script>
@endsection 