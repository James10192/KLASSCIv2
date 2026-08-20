{{--
    Formulaire premium partagé par les quatre comptes d'organigramme scolarité
    (directeur des études, responsable scolarité, service scolarité, agent
    d'inscription). Une seule source pour huit vues : create et edit de chaque
    rôle ne font que passer leur configuration.

    @param string      routeBase   ex. esbtp.agents-inscription
    @param string      icon
    @param string      heading
    @param string      subtitle
    @param object|null model       null en création
    @param array       scope       Puces « ce que ce compte peut / ne peut pas »
--}}
@php
    $isEdit = !is_null($model ?? null);
    $action = $isEdit ? route($routeBase.'.update', $model) : route($routeBase.'.store');
@endphp

<div class="main-content">

    <x-role-hero
        :icon="$icon"
        :title="$isEdit ? 'Modifier '.$model->name : $heading"
        :subtitle="$subtitle">
        <x-slot:actions>
            <a class="rdx-btn" href="{{ route('esbtp.personnel.unified.index') }}">
                <i class="fas fa-arrow-left"></i>Retour au personnel
            </a>
        </x-slot:actions>
    </x-role-hero>

    @if($errors->any())
        <div class="rf-errors">
            <span class="rf-errors-icon"><i class="fas fa-circle-exclamation"></i></span>
            <div>
                <div class="rf-errors-title">Le formulaire n'a pas pu être enregistré</div>
                <ul class="rf-errors-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="rdx-grid rf-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <x-role-panel
            icon="fa-id-card"
            title="Identité du compte"
            subtitle="Ces informations apparaissent dans l'annuaire du personnel">
            <div class="rf-fields">
                <div class="rf-field rf-field--full">
                    <label for="name">Nom complet <span class="rf-req">obligatoire</span></label>
                    <input class="form-control @error('name') rf-invalid @enderror"
                           id="name" name="name" required
                           placeholder="Ex : N'GUESSAN Marcel"
                           value="{{ old('name', $model->name ?? '') }}">
                    @error('name')<span class="rf-error">{{ $message }}</span>@enderror
                </div>
                <div class="rf-field">
                    <label for="email">Adresse e-mail</label>
                    <input class="form-control @error('email') rf-invalid @enderror"
                           type="email" id="email" name="email"
                           placeholder="prenom.nom@ecole.ci"
                           value="{{ old('email', $model->email ?? '') }}">
                    @error('email')<span class="rf-error">{{ $message }}</span>@enderror
                </div>
                <div class="rf-field">
                    <label for="telephone">Téléphone</label>
                    <input class="form-control @error('telephone') rf-invalid @enderror"
                           id="telephone" name="telephone"
                           placeholder="+225 07 00 00 00 00"
                           value="{{ old('telephone', $model->telephone ?? '') }}">
                    @error('telephone')<span class="rf-error">{{ $message }}</span>@enderror
                </div>
                <div class="rf-field rf-field--full">
                    <label for="specialite">Spécialité</label>
                    <input class="form-control @error('specialite') rf-invalid @enderror"
                           id="specialite" name="specialite"
                           placeholder="Ex : Scolarité, Génie civil, Administration"
                           value="{{ old('specialite', $model->specialite ?? '') }}">
                    @error('specialite')<span class="rf-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-shield-halved"
            title="{{ $isEdit ? 'Accès et statut' : 'Périmètre du compte' }}"
            subtitle="{{ $isEdit ? 'Laissez les champs de mot de passe vides pour ne pas le changer' : 'Ce que ce rôle pourra faire une fois le compte créé' }}">

            @if($isEdit)
                <div class="rf-fields">
                    <div class="rf-field">
                        <label for="password">Nouveau mot de passe</label>
                        <input class="form-control @error('password') rf-invalid @enderror"
                               type="password" id="password" name="password" autocomplete="new-password">
                        @error('password')<span class="rf-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="rf-field">
                        <label for="password_confirmation">Confirmation</label>
                        <input class="form-control" type="password" id="password_confirmation"
                               name="password_confirmation" autocomplete="new-password">
                    </div>
                    <div class="rf-field rf-field--full">
                        <label for="is_active">Statut du compte</label>
                        <x-au-select
                            name="is_active"
                            icon="fa-toggle-on"
                            :value="old('is_active', ($model->is_active ?? true) ? '1' : '0')"
                            :placeholder-is-first-option="false"
                            :options="['1' => 'Actif — peut se connecter', '0' => 'Inactif — connexion bloquée']" />
                    </div>
                </div>
                <p class="rf-note">
                    <i class="fas fa-circle-info"></i>
                    Désactiver un compte le conserve dans l'annuaire mais empêche toute connexion.
                </p>
            @else
                <ul class="rf-scope">
                    @foreach($scope as $line)
                        <li class="rf-scope-item rf-scope-item--{{ $line['allowed'] ? 'yes' : 'no' }}">
                            <i class="fas {{ $line['allowed'] ? 'fa-check' : 'fa-xmark' }}"></i>
                            <span>{{ $line['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="rf-note">
                    <i class="fas fa-key"></i>
                    Un mot de passe provisoire est généré. La personne devra le changer à sa première connexion.
                </p>
            @endif
        </x-role-panel>

        <div class="rf-actions">
            <button class="rdx-act rdx-act--primary rf-submit" type="submit">
                <i class="fas {{ $isEdit ? 'fa-floppy-disk' : 'fa-user-plus' }}"></i>
                {{ $isEdit ? 'Enregistrer les modifications' : 'Créer le compte' }}
            </button>
            <a class="rdx-act rdx-act--ghost rf-submit" href="{{ route('esbtp.personnel.unified.index') }}">
                Annuler
            </a>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Namespace rf-* : formulaire de compte d'organigramme */
    .rf-form { align-items: start; }
    .rf-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
    .rf-field { display: flex; flex-direction: column; gap: .3rem; }
    .rf-field--full { grid-column: 1 / -1; }
    .rf-field label {
        font-size: .74rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .3px;
        display: flex; align-items: center; gap: .4rem;
    }
    .rf-req {
        text-transform: none; letter-spacing: 0;
        font-size: .66rem; font-weight: 700;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        padding: .05rem .35rem; border-radius: 4px;
    }
    .rf-field .form-control {
        border: 1px solid #e2e8f0; border-radius: 9px;
        padding: .55rem .75rem; font-size: .86rem; color: #1e293b;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .rf-field .form-control:focus {
        border-color: #0453cb; outline: none;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .1);
    }
    .rf-field .form-control::placeholder { color: #cbd5e1; }
    .rf-invalid { border-color: #dc2626 !important; }
    .rf-error { font-size: .74rem; color: #dc2626; }

    .rf-errors {
        display: flex; align-items: flex-start; gap: .85rem;
        background: #fff; border: 1px solid #fecaca;
        border-left: 4px solid #dc2626; border-radius: 12px;
        padding: .9rem 1.1rem; margin-bottom: 1.25rem;
    }
    .rf-errors-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: rgba(220, 38, 38, .1); color: #dc2626;
        display: flex; align-items: center; justify-content: center;
    }
    .rf-errors-title { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .rf-errors-list { margin: .3rem 0 0; padding-left: 1.1rem; font-size: .8rem; color: #64748b; }

    .rf-scope { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .6rem; }
    .rf-scope-item { display: flex; align-items: flex-start; gap: .6rem; font-size: .84rem; color: #1e293b; }
    .rf-scope-item i {
        width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0; margin-top: .1rem;
        display: flex; align-items: center; justify-content: center; font-size: .65rem;
    }
    .rf-scope-item--yes i { background: rgba(16, 185, 129, .12); color: #10b981; }
    .rf-scope-item--no i { background: rgba(100, 116, 139, .12); color: #64748b; }
    .rf-scope-item--no span { color: #64748b; }

    .rf-note {
        margin: 1rem 0 0; padding: .7rem .85rem;
        background: rgba(4, 83, 203, .05); border-left: 3px solid #0453cb;
        border-radius: 0 8px 8px 0; font-size: .8rem; color: #1e293b;
        display: flex; align-items: flex-start; gap: .5rem;
    }
    .rf-note i { color: #0453cb; margin-top: .15rem; }

    .rf-actions {
        grid-column: 1 / -1;
        display: flex; gap: .6rem; flex-wrap: wrap;
        padding-top: .25rem;
    }
    .rf-submit { padding: .6rem 1.2rem; font-size: .85rem; }

    @media (max-width: 768px) {
        .rf-fields { grid-template-columns: 1fr; }
        .rf-actions { flex-direction: column; }
        .rf-submit { justify-content: center; }
    }
</style>
@endpush
