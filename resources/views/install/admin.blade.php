@extends('install.layout')

@section('title', 'Création de l\'administrateur')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <i class="fas fa-user-shield text-5xl text-blue-600 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800">Création du compte administrateur</h2>
        <p class="text-gray-600 mt-2">Créez le compte administrateur principal pour gérer votre école</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form id="adminForm" @submit.prevent="createAdmin" class="space-y-6">
            <!-- Nom -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                    <input type="text" id="name" v-model="form.name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Jean Dupont">
                    <p class="text-xs text-gray-500 mt-1">Entrez votre nom complet</p>
                </div>

                <!-- Nom d'utilisateur -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                    <input type="text" id="username" v-model="form.username" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="admin">
                    <p class="text-xs text-gray-500 mt-1">Ce nom sera utilisé pour la connexion</p>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                <input type="email" id="email" v-model="form.email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="admin@ecole.fr">
                <p class="text-xs text-gray-500 mt-1">Cette adresse sera utilisée pour les notifications</p>
            </div>

            <!-- Mot de passe -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <div style="position: relative;">
                        <input type="password" id="password" v-model="form.password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            style="padding-right: 2.75rem;"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 caractères</p>
                </div>

                <!-- Confirmation mot de passe -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                    <div style="position: relative;">
                        <input type="password" id="password_confirmation" v-model="form.password_confirmation" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            style="padding-right: 2.75rem;"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePwdVisibility(this)" style="position: absolute; right: 0; top: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #94a3b8;" title="Afficher/masquer">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Répétez le même mot de passe</p>
                </div>
            </div>

            <!-- Messages d'erreur et de succès -->
            <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700" v-html="error"></p>
                    </div>
                </div>
            </div>

            <div v-if="success" class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">@{{ success }}</p>
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex justify-between pt-4">
                <a href="{{ route('install.migration') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center" :disabled="loading">
                    <span v-if="loading" class="mr-2"><i class="fas fa-spinner fa-spin"></i></span>
                    <span v-else class="mr-2"><i class="fas fa-user-plus"></i></span>
                    Créer l'administrateur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePwdVisibility(btn) {
        var input = btn.parentElement.querySelector('input');
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    window.debugLog = window.debugLog || function () {
        if (window.console && console.log) {
            console.log.apply(console, arguments);
        }
    };
    window.debugError = window.debugError || function () {
        if (window.console && console.error) {
            console.error.apply(console, arguments);
        }
    };

    new Vue({
        el: '#adminForm',
        data: {
            form: {
                name: '',
                username: '',
                email: '',
                password: '',
                password_confirmation: ''
            },
            loading: false,
            error: null,
            success: null
        },
        methods: {
            validateForm() {
                // Réinitialiser l'erreur
                this.error = null;
                
                // Vérifier que tous les champs sont remplis
                if (!this.form.name || !this.form.username || !this.form.email || !this.form.password || !this.form.password_confirmation) {
                    this.error = 'Tous les champs sont obligatoires';
                    return false;
                }
                
                // Vérifier que l'email est valide
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.form.email)) {
                    this.error = 'Veuillez entrer une adresse email valide';
                    return false;
                }
                
                // Vérifier que les mots de passe correspondent
                if (this.form.password !== this.form.password_confirmation) {
                    this.error = 'Les mots de passe ne correspondent pas';
                    return false;
                }
                
                // Vérifier la longueur du mot de passe
                if (this.form.password.length < 8) {
                    this.error = 'Le mot de passe doit contenir au moins 8 caractères';
                    return false;
                }
                
                return true;
            },
            createAdmin() {
                // Valider le formulaire
                if (!this.validateForm()) {
                    return;
                }
                
                // Réinitialiser les messages
                this.error = null;
                this.success = null;
                this.loading = true;
                
                // Préparation des données au format attendu par le serveur
                const formData = {
                    name: this.form.name.trim(),
                    username: this.form.username.trim(),
                    email: this.form.email.trim(),
                    password: this.form.password,
                    password_confirmation: this.form.password_confirmation
                };
                
                debugLog('Envoi des données au serveur:', formData); // Pour debug
                
                // Token CSRF
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // Envoyer la requête au serveur
                axios.post('{{ route("install.setup-admin") }}', formData, {
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    this.loading = false;
                    debugLog('Réponse du serveur:', response.data); // Pour debug
                    
                    if (response.data && (response.data.status === 'success' || response.data.success === true)) {
                        this.success = response.data.message || 'Administrateur créé avec succès!';
                        
                        // Log de succès
                        debugLog('Redirection vers:', response.data.redirect || '{{ route("install.complete") }}');
                        
                        // Rediriger vers la page suivante après 2 secondes
                        setTimeout(() => {
                            window.location.href = response.data.redirect || '{{ route("install.complete") }}';
                        }, 2000);
                    } else {
                        this.error = (response.data && response.data.message) ? response.data.message : 'Une erreur est survenue';
                        debugError('Erreur détectée dans la réponse:', this.error);
                    }
                })
                .catch(error => {
                    this.loading = false;
                    debugError('Erreur complète:', error); // Pour debug
                    debugError('Détails de la réponse d\'erreur:', error.response); // Pour debug
                    
                    if (error.response && error.response.data) {
                        if (error.response.data.errors) {
                            // Récupérer les messages d'erreur de validation
                            const errorMessages = [];
                            for (const field in error.response.data.errors) {
                                const fieldErrors = error.response.data.errors[field];
                                if (Array.isArray(fieldErrors)) {
                                    errorMessages.push(`${field}: ${fieldErrors.join('. ')}`);
                                } else {
                                    errorMessages.push(`${field}: ${fieldErrors}`);
                                }
                            }
                            this.error = errorMessages.join('<br>');
                            debugError('Erreurs de validation:', errorMessages);
                        } else if (error.response.data.message) {
                            this.error = error.response.data.message;
                            debugError('Message d\'erreur:', error.response.data.message);
                        } else if (error.response.data.error) {
                            this.error = error.response.data.error;
                            debugError('Message d\'erreur:', error.response.data.error);
                        } else {
                            this.error = 'Une erreur est survenue lors de la création de l\'administrateur. Consultez la console pour plus de détails.';
                            debugError('Erreur sans message spécifique:', error.response.data);
                        }
                    } else if (error.message) {
                        this.error = 'Erreur de connexion: ' + error.message;
                        debugError('Message d\'erreur général:', error.message);
                    } else {
                        this.error = 'Une erreur de connexion est survenue. Veuillez réessayer.';
                        debugError('Erreur non spécifiée');
                    }
                });
            }
        }
    });
</script>
@endsection 
