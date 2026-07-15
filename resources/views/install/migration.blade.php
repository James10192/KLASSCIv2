@extends('install.layout')

@section('title', 'Initialisation')
@section('hero_title', 'Initialiser Laravel')
@section('hero_copy', 'Cette étape exécute les migrations, les permissions, les paramètres et les seeders nécessaires au tenant UIC.')

@section('content')
<div id="app" class="grid-2">
    <section class="card card-pad">
        <h2 class="section-title">Migrations et setup</h2>
        <p class="section-copy">Le processus est idempotent : il ne supprime pas les tables et n’utilise pas de remise à zéro destructrice.</p>

        <div v-if="status === 'idle'" class="notice">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Prêt à initialiser</strong>
                <div class="muted">Lancez l’étape après validation de la base MySQL.</div>
            </div>
        </div>
        <div v-if="status === 'running'" class="notice warning">
            <i class="fas fa-spinner fa-spin"></i>
            <div>
                <strong>Initialisation en cours</strong>
                <div>Gardez cette page ouverte jusqu’au retour JSON.</div>
            </div>
        </div>
        <div v-if="status === 'success'" class="notice success">
            <i class="fas fa-check"></i>
            <div>
                <strong>@{{ message }}</strong>
                <p class="hint">Vous pouvez maintenant créer le compte administrateur.</p>
            </div>
        </div>
        <div v-if="status === 'error'" class="notice error">
            <i class="fas fa-triangle-exclamation"></i>
            <div>@{{ message }}</div>
        </div>

        <div class="terminal" style="margin-top: 16px;" aria-live="polite">
            <p v-for="(line, index) in terminal" :key="index" class="terminal-line">@{{ line }}</p>
            <p v-if="!terminal.length" class="terminal-line">Aucune commande lancée.</p>
        </div>

        <div class="btn-row">
            <a href="{{ route('install.database') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Retour
            </a>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" @click="run" :disabled="status === 'running'">
                    <i :class="status === 'running' ? 'fas fa-spinner fa-spin' : 'fas fa-terminal'"></i>
                    Lancer l’initialisation
                </button>
                <a v-if="nextUrl" :href="nextUrl" class="btn btn-success">
                    Créer l’admin
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <aside class="card card-pad">
        <h2 class="section-title">Séquence exécutée</h2>
        <div class="check-list">
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-database"></i></span>
                <div><strong>php artisan migrate --force</strong><p class="hint">Applique uniquement les migrations manquantes.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-gears"></i></span>
                <div><strong>setup.php --force</strong><p class="hint">Storage, permissions, settings et seeders critiques.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-user-shield"></i></span>
                <div><strong>Contrôle données métier</strong><p class="hint">Vérifie les données ESBTP de base avant l’admin.</p></div>
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
        status: 'idle',
        message: '',
        terminal: [],
        nextUrl: ''
    },
    methods: {
        run() {
            this.status = 'running';
            this.message = '';
            this.nextUrl = '';
            this.terminal = ['Démarrage de l’initialisation Laravel...'];

            axios.post('{{ route("install.run-migration") }}', {})
                .then(({ data }) => {
                    this.status = data.ok ? 'success' : 'error';
                    this.message = data.message || 'Initialisation terminée.';
                    this.nextUrl = data.next_url || data.redirect || '';
                    this.terminal = (data.data && data.data.terminal && data.data.terminal.length)
                        ? data.data.terminal
                        : ['Commande terminée, aucune sortie retournée.'];
                })
                .catch((error) => {
                    const data = error.response?.data;
                    this.status = 'error';
                    this.message = window.klassciInstall.message(error, 'Initialisation échouée.');
                    this.terminal = data?.data?.terminal || [this.message];
                });
        }
    }
});
</script>
@endsection
