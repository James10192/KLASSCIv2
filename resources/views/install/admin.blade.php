@extends('install.layout')

@section('title', 'Administrateur')
@section('hero_title', 'Créer l’accès super admin')
@section('hero_copy', 'Créez le premier compte de gestion. Le mot de passe est envoyé une seule fois et n’est jamais réaffiché.')

@section('content')
<div id="adminForm" class="grid-2">
    <section class="card card-pad">
        <h2 class="section-title">Compte administrateur</h2>
        <p class="section-copy">Utilisez une adresse nominative et un mot de passe conservé hors de KLASSCI.</p>

        <form @submit.prevent="createAdmin">
            <div class="grid-fields" style="margin-top:18px;">
                <div class="field">
                    <label for="name">Nom complet</label>
                    <input id="name" class="input" v-model.trim="form.name" required autocomplete="name">
                </div>
                <div class="field">
                    <label for="username">Identifiant</label>
                    <input id="username" class="input" v-model.trim="form.username" required autocomplete="username">
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="email">Email</label>
                    <input id="email" class="input" type="email" v-model.trim="form.email" required autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input id="password" class="input" type="password" v-model="form.password" required autocomplete="new-password">
                    <p class="hint">Minimum 8 caractères.</p>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmation</label>
                    <input id="password_confirmation" class="input" type="password" v-model="form.password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div v-if="error" class="notice error">
                <i class="fas fa-triangle-exclamation"></i>
                <div v-html="error"></div>
            </div>
            <div v-if="success" class="notice success">
                <i class="fas fa-check"></i>
                <div>
                    <strong>@{{ success }}</strong>
                    <p class="hint">Passez à l’étape finale pour verrouiller l’installation.</p>
                </div>
            </div>

            <div class="btn-row">
                <a href="{{ route('install.migration') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'"></i>
                        Créer l’admin
                    </button>
                    <a v-if="nextUrl" :href="nextUrl" class="btn btn-success">
                        Terminer
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    </section>

    <aside class="card card-pad">
        <h2 class="section-title">Sécurité</h2>
        <div class="check-list">
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-key"></i></span>
                <div><strong>Mot de passe non affiché</strong><p class="hint">KLASSCI ne le remet pas dans la page de fin.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-user-shield"></i></span>
                <div><strong>Rôle superAdmin</strong><p class="hint">Le rôle est attribué au premier compte créé.</p></div>
            </div>
            <div class="check-item ok">
                <span class="check-icon"><i class="fas fa-lock"></i></span>
                <div><strong>Verrouillage final</strong><p class="hint">La finalisation active le verrou install.lock.</p></div>
            </div>
        </div>
    </aside>
</div>
@endsection

@section('scripts')
<script>
new Vue({
    el: '#adminForm',
    data: {
        loading: false,
        error: '',
        success: '',
        nextUrl: '',
        form: {
            name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: ''
        }
    },
    methods: {
        validateForm() {
            if (!this.form.name || !this.form.username || !this.form.email || !this.form.password || !this.form.password_confirmation) {
                this.error = 'Tous les champs sont obligatoires.';
                return false;
            }
            if (this.form.password.length < 8) {
                this.error = 'Le mot de passe doit contenir au moins 8 caractères.';
                return false;
            }
            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'Les mots de passe ne correspondent pas.';
                return false;
            }
            return true;
        },
        createAdmin() {
            this.error = '';
            this.success = '';
            this.nextUrl = '';
            if (!this.validateForm()) return;

            this.loading = true;
            axios.post('{{ route("install.setup-admin") }}', this.form)
                .then(({ data }) => {
                    this.success = data.message || 'Administrateur créé.';
                    this.nextUrl = data.next_url || data.redirect || '';
                    this.form.password = '';
                    this.form.password_confirmation = '';
                })
                .catch((error) => {
                    this.error = window.klassciInstall.message(error, 'Création impossible.');
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
});
</script>
@endsection
