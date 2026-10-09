@extends('layouts.app')

@section('title', 'Habilitations familiales')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Habilitations des responsables</h1>
    <p class="text-muted">Validation humaine obligatoire. Le lien tuteur ne suffit pas à ouvrir l'espace familial. Les références de preuve ne sont conservées que sous empreinte.</p>
    <div class="alert alert-warning">Le compte étudiant ne doit jamais être partagé avec le responsable. Les invitations sont enregistrées dans une file sécurisée. Vérifiez les contacts, le cron et les permissions avant activation générale.</div>
    <div class="row g-3">
    @forelse($relations as $relation)
        @php($grant = $grants->get($relation->parent_id.':'.$relation->etudiant_id))
        <div class="col-12 col-xl-6">
            <section class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">{{ $relation->parent_nom }} {{ $relation->parent_prenoms }}</h2>
                    <p class="mb-2">Étudiant : <strong>{{ $relation->etudiant_nom }} {{ $relation->etudiant_prenoms }}</strong></p>
                    <p class="small text-muted">Compte indépendant : {{ $relation->parent_user_id ? 'existant' : 'à créer' }}</p>
                    @if($grant && $grant->verified_at && ! $grant->revoked_at && $grant->expires_at->isFuture())
                        <p class="text-success">Accès accordé jusqu'au {{ $grant->expires_at->format('d/m/Y') }}</p>
                        @if($relation->parent_user_id)
                            <p class="small text-muted">Identité indépendante liée au responsable.</p>
                        @else
                            <p class="small text-muted">L'invitation prépare automatiquement un compte distinct, inactif jusqu'à sa validation par le responsable.</p>
                        @endif
                        @php($invitation = $invitations->get($grant->id))
                        @if($invitation)
                            <p class="small">Dernière invitation : <strong>{{ $invitation->status }}</strong> — {{ $invitation->created_at->format('d/m/Y H:i') }}. « accepted » ne garantit pas la réception.</p>
                        @endif
                        <form method="post" action="{{ route('esbtp.famille.invitation.envoyer', $grant) }}" class="mb-3">
                            @csrf
                            <label class="form-label">Ressaisir l'e-mail du responsable vérifié au guichet</label>
                            <input type="email" name="confirmed_email" class="form-control mb-2" required autocomplete="off" placeholder="Adresse confirmée personnellement">
                            <label class="form-check mb-3">
                                <input type="checkbox" name="verification_contact" value="1" required class="form-check-input">
                                <span class="form-check-label">J'atteste avoir confirmé que cette adresse appartient au responsable autorisé.</span>
                            </label>
                            <button type="submit" class="btn btn-primary btn-sm">Préparer l'invitation sécurisée</button>
                        </form>
                        <form method="post" action="{{ route('esbtp.famille.habilitations.revoquer', $grant) }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm" type="submit">Révoquer l'accès</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('esbtp.famille.habilitations.approuver') }}">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $relation->parent_id }}">
                            <input type="hidden" name="etudiant_id" value="{{ $relation->etudiant_id }}">
                            <div class="mb-2">
                                <label class="form-label">Vérification effectuée</label>
                                <select name="evidence_type" class="form-select" required>
                                    <option value="identite_guichet">Identité contrôlée au guichet</option>
                                    <option value="document_legal">Document légal vérifié</option>
                                    <option value="accord_formel">Accord formel vérifié</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Référence interne de la vérification</label>
                                <input type="text" name="reference" maxlength="120" minlength="6" class="form-control" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Référence du consentement explicite de l'étudiant (obligatoire pour un majeur ou âge inconnu)</label>
                                <input type="text" name="consent_reference" maxlength="120" minlength="6" class="form-control" autocomplete="off">
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Valider cette relation</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    @empty
        <div class="col-12"><p>Aucune relation tuteur n'a été trouvée.</p></div>
    @endforelse
    </div>
</div>
@endsection
