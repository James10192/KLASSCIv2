@extends('layouts.app')

@section('title', 'Détails Relance #' . $relance->id)

@push('styles')
<style>
.relance-timeline {
    position: relative;
    padding-left: 30px;
}

.relance-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline-item.success::before {
    background: #28a745;
}

.timeline-item.danger::before {
    background: #dc3545;
}

.timeline-item.warning::before {
    background: #ffc107;
}

.relance-card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.info-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.badge-custom {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
}

.action-buttons .btn {
    border-radius: 20px;
    padding: 8px 20px;
    font-weight: 500;
}

.message-preview {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    max-height: 300px;
    overflow-y: auto;
}
</style>
@endpush

@section('content')
@php
    // Shell mobile actif : le DOM de bureau se cache sous 992px au profit de la
    // fiche m-* ci-dessous. Shell coupé : rien ne change.
    $rsShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
@endphp
@if($rsShell)
@php
    $rsmEtudiant = $relance->etudiant;
    $rsmNom = $rsmEtudiant?->nom_complet ?? trim(($rsmEtudiant?->nom ?? '') . ' ' . ($rsmEtudiant?->prenoms ?? '')) ?: 'Étudiant inconnu';
    $rsmInitiales = mb_strtoupper(mb_substr($rsmEtudiant?->prenoms ?? 'E', 0, 1, 'UTF-8') . mb_substr($rsmEtudiant?->nom ?? '', 0, 1, 'UTF-8'), 'UTF-8');

    $rsmCanalBrut = (string) ($relance->canal ?? '');
    $rsmCanal = match (true) {
        $rsmCanalBrut === 'whatsapp_deeplink' => 'WhatsApp',
        $rsmCanalBrut === 'tel' => 'Appel',
        $rsmCanalBrut === 'email' => 'E-mail',
        $rsmCanalBrut === 'sms' => 'SMS',
        $rsmCanalBrut === 'manuel' => 'Manuel (hors application)',
        default => $relance->type_formatte,
    };
    $rsmObjet = $relance->type === 'recouvrement' ? 'Recouvrement' : $relance->niveau_formatte;
    // statut → [puce, ton, libellé de la date]
    $rsmStatuts = [
        \App\Models\ESBTPRelance::STATUT_ENVOYEE   => ['Envoyée', 'ok', 'Envoyée le'],
        \App\Models\ESBTPRelance::STATUT_PLANIFIEE => ['Planifiée', 'info', 'Planifiée pour le'],
        \App\Models\ESBTPRelance::STATUT_ECHEC     => ['À renvoyer', 'bad', 'Échec le'],
        \App\Models\ESBTPRelance::STATUT_INTENT    => ['Non confirmée', 'warn', 'Ouverte le'],
    ];
    [$rsmPuce, $rsmTon, $rsmDateLabel] = $rsmStatuts[$relance->statut] ?? [$relance->statut_formatte, 'mute', 'Date'];
    $rsmDate = $relance->statut === \App\Models\ESBTPRelance::STATUT_ECHEC ? $relance->updated_at : $relance->date_envoi;
    $rsmErreur = is_array($relance->response_data) ? ($relance->response_data['error'] ?? $relance->response_data['error_reason'] ?? null) : null;

    $rsmPeutRenvoyer = auth()->user()?->can('comptabilite.relances.send') && $relance->peutEtreRenvoyee();
    $rsmConfig = [
        'statut'       => $relance->statut,
        'peutRenvoyer' => (bool) $rsmPeutRenvoyer,
        'renvoyerUrl'  => route('esbtp.comptabilite.relances.renvoyer', $relance->id),
    ];
@endphp
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rsm-screen" x-data="rsmRelance({{ \Illuminate\Support\Js::from($rsmConfig) }})">
    <x-m.appbar title="Relance"
                :sub="'N° ' . $relance->id . ' · ' . $rsmObjet"
                :back="route('esbtp.comptabilite.relances.index')" />

    <div class="m-body" data-m-ptr="reload">
        <div class="m-note">
            <div class="av" aria-hidden="true">{{ $rsmInitiales }}</div>
            <div>
                <div class="nm">{{ $rsmNom }}</div>
                <div class="mt">{{ $rsmEtudiant?->matricule ? $rsmEtudiant->matricule . ' · ' : '' }}{{ $rsmCanal }}</div>
            </div>
            <span class="m-chip" x-bind:class="ton()" x-text="puce()">{{ $rsmPuce }}</span>
        </div>

        @if($rsmErreur)
            <div class="rsm-note bad" role="status">
                <x-m.icon name="alert" />
                <span>{{ $rsmErreur }}</span>
            </div>
        @endif

        <dl class="m-dl">
            <dt>Canal</dt>
            <dd>{{ $rsmCanal }}</dd>
            <dt>Objet</dt>
            <dd>{{ $rsmObjet }}</dd>
            <dt x-text="dateLabel()">{{ $rsmDateLabel }}</dt>
            <dd>{{ $rsmDate ? $rsmDate->format('d/m/Y H:i') : '—' }}</dd>
            @if($relance->confirmee_a)
                <dt>Confirmée le</dt>
                <dd>{{ $relance->confirmee_a->format('d/m/Y H:i') }}</dd>
            @endif
            @if($relance->template_utilise)
                <dt>Modèle</dt>
                <dd>{{ $relance->template_utilise }}</dd>
            @endif
            @if($relance->facture)
                <dt>Facture</dt>
                <dd>{{ $relance->facture->numero ?? '—' }} · {{ number_format((float) $relance->facture->montant_total, 0, ',', ' ') }} FCFA</dd>
            @endif
            <dt>Créée le</dt>
            <dd>{{ $relance->created_at->format('d/m/Y H:i') }}</dd>
        </dl>

        @if($relance->contenu_message)
            <div class="m-sec"><b>Message</b></div>
            <div class="rsm-msg">{{ $relance->contenu_message }}</div>
        @endif

        <div class="m-sec"><b>Dossier</b></div>
        <div class="m-list one">
            @if($relance->inscription_id)
                <x-m.row :href="route('esbtp.comptabilite.relances.etudiant', $relance->inscription_id)"
                         icon="cash" title="Dossier de relance" sub="Impayés, historique et actions pour cette inscription" />
            @endif
            @if($rsmEtudiant)
                <x-m.row :href="route('esbtp.etudiants.show', $rsmEtudiant->id)"
                         :av="$rsmInitiales" :title="$rsmNom"
                         :sub="'Profil étudiant' . ($rsmEtudiant->matricule ? ' · ' . $rsmEtudiant->matricule : '')" />
            @endif
        </div>
    </div>

    @if($rsmPeutRenvoyer)
    @can('comptabilite.relances.send')
        <x-m.actionbar x-show="peutRenvoyer">
            <button type="button" class="m-btn p" x-on:click="renvoyer()" x-bind:disabled="occupe">
                <x-m.icon name="refresh" />
                <span x-show="!occupe">Renvoyer la relance</span>
                <span x-show="occupe" x-cloak>Envoi…</span>
            </button>
        </x-m.actionbar>
    @endcan
    @endif
</div>
@endif

<div class="container-fluid {{ $rsShell ? 'm-only-desktop' : '' }}">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-bell text-primary me-2"></i>
                        Relance #{{ $relance->id }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ $relance->niveau_formatte }} - {{ $relance->type_formatte }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Retour aux relances
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Informations principales -->
            <div class="card relance-card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations de la Relance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    Étudiant Concerné
                                </h6>
                                <div class="mb-2">
                                    <strong>Nom complet:</strong>
                                    {{ $relance->etudiant->nom }} {{ $relance->etudiant->prenoms }}
                                </div>
                                <div class="mb-2">
                                    <strong>Email:</strong>
                                    <a href="mailto:{{ $relance->etudiant->email }}">{{ $relance->etudiant->email }}</a>
                                </div>
                                <div class="mb-2">
                                    <strong>Téléphone:</strong>
                                    {{ $relance->etudiant->telephone ?? 'Non renseigné' }}
                                </div>
                                <div>
                                    <strong>Numéro étudiant:</strong>
                                    {{ $relance->etudiant->numero_etudiant ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-cog me-2"></i>
                                    Paramètres de la Relance
                                </h6>
                                <div class="mb-2">
                                    <strong>Type:</strong>
                                    <span class="badge bg-{{
                                        $relance->type === 'email' ? 'info' :
                                        ($relance->type === 'sms' ? 'warning' : 'secondary')
                                    }} ms-2">
                                        {{ $relance->type_formatte }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Niveau:</strong>
                                    <span class="badge bg-{{ $relance->niveau == 1 ? 'success' : ($relance->niveau == 2 ? 'warning' : 'danger') }} ms-2">
                                        {{ $relance->niveau_formatte }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Statut:</strong>
                                    <span class="badge bg-{{ $relance->statut_class }} ms-2">
                                        {{ $relance->statut_formatte }}
                                    </span>
                                </div>
                                <div>
                                    <strong>Template utilisé:</strong>
                                    {{ $relance->template_utilise ?? 'Défaut' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($relance->facture)
                    <div class="info-section mt-3">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-file-invoice me-2"></i>
                            Facture Associée
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Numéro facture:</strong> {{ $relance->facture->numero ?? 'N/A' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Montant:</strong>
                                <span class="text-danger fw-bold">{{ number_format($relance->facture->montant_total, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Date échéance:</strong> {{ $relance->facture->date_echeance ? $relance->facture->date_echeance->format('d/m/Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Contenu du message -->
            @if($relance->contenu_message)
            <div class="card relance-card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment-alt me-2"></i>
                        Contenu du Message
                    </h5>
                </div>
                <div class="card-body">
                    <div class="message-preview">
                        @if($relance->type === 'email')
                            {!! nl2br(e($relance->contenu_message)) !!}
                        @else
                            {{ $relance->contenu_message }}
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Historique et Actions -->
            <div class="card relance-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique de la Relance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="relance-timeline">
                        <!-- Création -->
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Relance créée</h6>
                                    <p class="text-muted mb-0">
                                        Planifiée pour le {{ $relance->date_envoi ? $relance->date_envoi->format('d/m/Y à H:i') : 'Non définie' }}
                                    </p>
                                </div>
                                <small class="text-muted">{{ $relance->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>

                        <!-- Envoi -->
                        @if($relance->statut === 'envoyee')
                        <div class="timeline-item success">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Relance envoyée avec succès</h6>
                                    <p class="text-muted mb-0">
                                        @if($relance->response_data && isset($relance->response_data['sms_id']))
                                            ID SMS: {{ $relance->response_data['sms_id'] }}
                                        @else
                                            Envoi réussi
                                        @endif
                                    </p>
                                </div>
                                <small class="text-muted">{{ $relance->date_envoi ? $relance->date_envoi->format('d/m/Y H:i') : 'N/A' }}</small>
                            </div>
                        </div>
                        @elseif($relance->statut === 'echec')
                        <div class="timeline-item danger">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Échec d'envoi</h6>
                                    <p class="text-muted mb-0">
                                        @if($relance->response_data && isset($relance->response_data['error']))
                                            Erreur: {{ $relance->response_data['error'] }}
                                        @else
                                            Erreur lors de l'envoi
                                        @endif
                                    </p>
                                </div>
                                <small class="text-muted">{{ $relance->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        @endif

                        <!-- En attente -->
                        @if($relance->statut === 'planifiee')
                        <div class="timeline-item warning">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">En attente d'envoi</h6>
                                    <p class="text-muted mb-0">
                                        @if($relance->est_en_retard)
                                            ⚠️ Envoi en retard
                                        @else
                                            Programmée pour bientôt
                                        @endif
                                    </p>
                                </div>
                                <small class="text-muted">Maintenant</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions disponibles -->
            <div class="card relance-card mb-4 sticky-top">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Actions Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="action-buttons d-grid gap-2">
                        @if($relance->statut === 'echec' || ($relance->statut === 'planifiee' && $relance->est_en_retard))
                            @can('comptabilite.relances.send')
                            <button type="button" class="btn btn-success" onclick="renvoyerRelance({{ $relance->id }})">
                                <i class="fas fa-redo me-1"></i>
                                Renvoyer la Relance
                            </button>
                            @endcan
                        @endif

                        @if($relance->type === 'email' && $relance->statut === 'envoyee')
                            <button type="button" class="btn btn-info" onclick="voirStatistiques({{ $relance->id }})">
                                <i class="fas fa-chart-line me-1"></i>
                                Voir Statistiques
                            </button>
                        @endif

                        @if($relance->statut === 'planifiee')
                            <button type="button" class="btn btn-warning" onclick="modifierRelance({{ $relance->id }})">
                                <i class="fas fa-edit me-1"></i>
                                Modifier la Relance
                            </button>
                        @endif

                        <button type="button" class="btn btn-outline-primary" onclick="previewRelance({{ $relance->id }})">
                            <i class="fas fa-eye me-1"></i>
                            Aperçu du Message
                        </button>

                        <hr>

                        <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-1"></i>
                            Configuration Templates
                        </a>

                        <a href="{{ $relance->inscription_id ? route('esbtp.comptabilite.relances.etudiant', $relance->inscription_id) : route('esbtp.comptabilite.relances.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-history me-1"></i>
                            Autres Relances Étudiant
                        </a>
                    </div>
                </div>
            </div>

            <!-- Informations techniques -->
            <div class="card relance-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Informations Techniques
                    </h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>ID Relance:</strong> {{ $relance->id }}
                        </div>
                        <div class="mb-2">
                            <strong>Créée le:</strong> {{ $relance->created_at->format('d/m/Y H:i:s') }}
                        </div>
                        <div class="mb-2">
                            <strong>Dernière MAJ:</strong> {{ $relance->updated_at->format('d/m/Y H:i:s') }}
                        </div>
                        @if($relance->response_data)
                        <div class="mb-2">
                            <strong>Données de réponse:</strong>
                            <pre class="bg-light p-2 mt-1 rounded"><code>{{ json_encode($relance->response_data, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aperçu -->
<div class="modal fade" id="modalApercu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Aperçu du Message
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="apercu-content">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function renvoyerRelance(id) {
    if (confirm('Êtes-vous sûr de vouloir renvoyer cette relance ?')) {
        fetch(`{{ route('esbtp.comptabilite.relances.renvoyer', '') }}/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.success ? 'success' : 'error', data.message);
            if (data.success) {
                // EXCEPTION ajax-no-reload-premium : le renvoi change le statut, la date
                // d'envoi et le bloc « Actions disponibles » rendus cote serveur. Le rendu
                // mobile, lui, met l'etat a jour sans rechargement (fabrique rsmRelance).
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            showAlert('error', 'Erreur lors du renvoi');
        });
    }
}

function voirStatistiques(id) {
    showAlert('info', 'Fonctionnalité de statistiques en développement...');
}

function modifierRelance(id) {
    showAlert('info', 'Fonctionnalité de modification en développement...');
}

function previewRelance(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalApercu'));
    const container = document.getElementById('apercu-content');

    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    modal.show();

    // Ici vous pourriez charger l'aperçu du message
    setTimeout(() => {
        container.innerHTML = `
            <div class="message-preview">
                {!! $relance->contenu_message ? nl2br(e($relance->contenu_message)) : 'Aucun contenu de message disponible' !!}
            </div>
        `;
    }, 500);
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : (type === 'info' ? 'info' : 'danger')} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush

{{-- ── Écran mobile : styles propres (namespace rsm-) ── --}}
@push('styles')
<style>
[x-cloak] { display: none !important; }
.rsm-note { display: grid; grid-template-columns: auto 1fr; gap: 10px; align-items: center; background: #fdecea; color: #a12016; border: 1px solid #f5c6c0; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
.rsm-note svg { width: 20px; height: 20px; }
.rsm-msg { background: #fff; border: 1px solid #e6eaf2; border-radius: 14px; padding: 12px 14px; font-size: 14px; line-height: 1.5; color: #0f172a; white-space: pre-wrap; word-break: break-word; font-family: var(--m-font); }
.rsm-screen .m-actionbar .m-btn svg { width: 20px; height: 20px; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.rsmRelance !== 'function') {
        window.rsmRelance = function (cfg) {
            return {
                statut: cfg.statut,
                peutRenvoyer: !!cfg.peutRenvoyer,
                occupe: false,

                puce() {
                    return { envoyee: 'Envoyée', planifiee: 'Planifiée', echec: 'À renvoyer', intent: 'Non confirmée', en_file: 'En file d\'envoi' }[this.statut] || this.statut || '—';
                },
                ton() {
                    return { envoyee: 'ok', planifiee: 'info', echec: 'bad', intent: 'warn', en_file: 'info' }[this.statut] || 'mute';
                },
                dateLabel() {
                    return { envoyee: 'Envoyée le', planifiee: 'Planifiée pour le', echec: 'Échec le', intent: 'Ouverte le', en_file: 'Dernière tentative le' }[this.statut] || 'Date';
                },
                toast(message, type) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message: message } }));
                },

                async renvoyer() {
                    if (this.occupe || !this.peutRenvoyer) { return; }
                    this.occupe = true;
                    try {
                        var res = await fetch(cfg.renvoyerUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        if (res.status === 429) {
                            this.toast('Trop de demandes, réessayez dans une minute.', 'error');
                            return;
                        }
                        var data = await res.json().catch(function () { return {}; });
                        if (!res.ok || data.success === false) {
                            throw new Error(data.message || ('Erreur ' + res.status));
                        }
                        // Le job d'envoi tourne en arrière-plan : la relance est en file, la puce le dit sans recharger.
                        this.statut = 'en_file';
                        this.peutRenvoyer = false;
                        this.toast(data.message || 'Relance mise en file d\'attente pour renvoi.', 'success');
                    } catch (err) {
                        this.toast(err.message || 'Renvoi impossible. Vérifiez votre connexion.', 'error');
                    } finally {
                        this.occupe = false;
                    }
                },
            };
        };
    }
</script>
@endpush
