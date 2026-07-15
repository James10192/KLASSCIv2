@extends('install.layout')

@section('title', 'Préparation')
@section('hero_title', 'Préparer le tenant')
@section('hero_copy', 'Vérifiez le runtime, le dossier public et les dépendances avant de connecter la base de données.')

@section('content')
<div id="app" class="grid-2">
    <section class="card card-pad">
        <h2 class="section-title">Prérequis serveur</h2>
        <p class="section-copy">Ces contrôles évitent de lancer une installation sur un dossier incomplet ou un runtime PHP incompatible.</p>

        <div v-if="loading" class="notice">
            <i class="fas fa-spinner fa-spin"></i>
            <div>
                <strong>Vérification en cours</strong>
                <div class="muted">Lecture du runtime, des extensions et des droits d’écriture.</div>
            </div>
        </div>

        <div v-if="error" class="notice error">
            <i class="fas fa-triangle-exclamation"></i>
            <div>@{{ error }}</div>
        </div>

        <div class="check-list" v-if="checks.length">
            <div v-for="check in checks" :key="check.key" class="check-item" :class="check.ok ? 'ok' : 'fail'">
                <span class="check-icon">
                    <i :class="check.ok ? 'fas fa-check' : 'fas fa-xmark'"></i>
                </span>
                <div>
                    <strong>@{{ check.label }}</strong>
                    <p class="hint" v-if="Array.isArray(check.detail)">
                        @{{ check.detail.length ? check.detail.join(', ') : 'Tout est prêt.' }}
                    </p>
                    <p class="hint" v-else>@{{ check.detail }}</p>
                </div>
            </div>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-secondary" @click="loadRequirements" :disabled="loading">
                <i class="fas fa-rotate"></i>
                Revérifier
            </button>
            <a class="btn btn-primary" :class="{ 'is-disabled': !ready }" href="{{ route('install.database') }}" @click="guardNext">
                Continuer
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <aside class="card card-pad">
        <h2 class="section-title">À faire avant cette page</h2>
        <p class="section-copy">Ces actions restent côté cPanel tant qu’aucun token WHM ou cPanel UAPI n’est configuré.</p>
        <div class="check-list">
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-folder-tree"></i></span>
                <div>
                    <strong>Dossier serveur</strong>
                    <p class="hint">Cloner la branche du tenant dans <code>@{{ documentRoot.replace('/public', '') }}</code>.</p>
                </div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-globe"></i></span>
                <div>
                    <strong>Sous-domaine</strong>
                    <p class="hint">Pointer <strong>@{{ host }}</strong> vers <code>@{{ documentRoot }}</code>.</p>
                </div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-database"></i></span>
                <div>
                    <strong>Base MySQL</strong>
                    <p class="hint">Créer la base et l’utilisateur, puis donner tous les privilèges.</p>
                </div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-box"></i></span>
                <div>
                    <strong>Dépendances</strong>
                    <p class="hint">Exécuter <code>composer install --no-dev --optimize-autoloader</code> sur le dossier tenant.</p>
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
        loading: true,
        ready: false,
        error: '',
        checks: [],
        host: window.klassciInstall.defaults.host,
        documentRoot: window.klassciInstall.defaults.documentRoot
    },
    mounted() {
        this.loadRequirements();
    },
    methods: {
        loadRequirements() {
            this.loading = true;
            this.error = '';
            axios.get('{{ route("install.requirements") }}')
                .then(({ data }) => {
                    this.checks = data.data?.checks || [];
                    this.ready = data.ok === true;
                })
                .catch((error) => {
                    this.ready = false;
                    this.error = window.klassciInstall.message(error, 'Impossible de vérifier les prérequis.');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        guardNext(event) {
            if (!this.ready) {
                event.preventDefault();
            }
        }
    }
});
</script>
@endsection
