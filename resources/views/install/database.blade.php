@extends('install.layout')

@section('title', 'Base de données')
@section('hero_title', 'Connecter la base UIC')
@section('hero_copy', 'Renseignez les accès MySQL créés dans cPanel. La clé APP, l’URL et le tenant sont enregistrés dans le fichier .env.')

@section('content')
<div id="app" class="grid-2">
    <section class="card card-pad">
        <h2 class="section-title">Connexion MySQL</h2>
        <p class="section-copy">KLASSCI teste la connexion avant d’écrire la configuration. Le mot de passe n’est pas stocké en session.</p>

        <form @submit.prevent="submit">
            <div class="grid-fields" style="margin-top: 18px;">
                <div class="field">
                    <label for="app_name">Nom établissement</label>
                    <input id="app_name" class="input" v-model="form.app_name" required>
                </div>
                <div class="field">
                    <label for="tenant_code">Code tenant</label>
                    <input id="tenant_code" class="input" v-model="form.tenant_code" required>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="app_url">URL publique</label>
                    <input id="app_url" class="input" type="url" v-model="form.app_url" required>
                </div>
                <div class="field">
                    <label for="host">Hôte</label>
                    <input id="host" class="input" v-model="form.host" required>
                    <p class="hint">Souvent <code>localhost</code> chez cPanel.</p>
                </div>
                <div class="field">
                    <label for="port">Port</label>
                    <input id="port" class="input" v-model="form.port" required>
                </div>
                <div class="field">
                    <label for="database">Base</label>
                    <input id="database" class="input" v-model="form.database" required>
                </div>
                <div class="field">
                    <label for="username">Utilisateur</label>
                    <input id="username" class="input" v-model="form.username" required>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="password">Mot de passe</label>
                    <input id="password" class="input" type="password" v-model="form.password" autocomplete="new-password">
                </div>
            </div>

            <div v-if="error" class="notice error">
                <i class="fas fa-triangle-exclamation"></i>
                <div>@{{ error }}</div>
            </div>
            <div v-if="success" class="notice success">
                <i class="fas fa-check"></i>
                <div>
                    <strong>@{{ success }}</strong>
                    <p class="hint" v-if="nextUrl">Passez à l’étape suivante quand vous êtes prêt.</p>
                </div>
            </div>

            <div class="btn-row">
                <a href="{{ route('install.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-plug'"></i>
                        Tester et enregistrer
                    </button>
                    <a v-if="nextUrl" :href="nextUrl" class="btn btn-success">
                        Initialiser
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    </section>

    <aside class="card card-pad">
        <h2 class="section-title">Ce que fait cette étape</h2>
        <div class="check-list">
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-shield-halved"></i></span>
                <div>
                    <strong>Validation serveur</strong>
                    <p class="hint">Test PDO réel avec les identifiants fournis.</p>
                </div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-file-lines"></i></span>
                <div>
                    <strong>Écriture .env</strong>
                    <p class="hint">APP_NAME, APP_URL, TENANT_CODE et DB_* sont persistés avec format sécurisé.</p>
                </div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-broom"></i></span>
                <div>
                    <strong>Cache config</strong>
                    <p class="hint">Le cache Laravel est vidé pour relire la nouvelle configuration.</p>
                </div>
            </div>
        </div>
    </aside>
</div>
@endsection

@section('scripts')
<script>
new Vue({
    el: '#app',
    data: {
        loading: false,
        error: '',
        success: '',
        nextUrl: '',
        form: {
            app_name: 'Université internationale de Cocody',
            app_url: 'https://uic.klassci.com',
            tenant_code: 'uic',
            host: 'localhost',
            port: '3306',
            database: '',
            username: '',
            password: ''
        }
    },
    methods: {
        submit() {
            this.loading = true;
            this.error = '';
            this.success = '';
            this.nextUrl = '';
            axios.post('{{ route("install.setup-database") }}', this.form)
                .then(({ data }) => {
                    this.success = data.message || 'Connexion validée.';
                    this.nextUrl = data.next_url || data.redirect || '';
                })
                .catch((error) => {
                    this.error = window.klassciInstall.message(error, 'Connexion impossible.');
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
});
</script>
@endsection
