@extends('layouts.app')

@section('title', 'Détails de l\'inscription')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de l'inscription</h5>
                    <div>
                        @if($inscription->status === 'en_attente')
                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#validationModal">
                                <i class="fas fa-check me-1"></i>Valider l'inscription
                            </button>
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#paiementModal">
                                <i class="fas fa-money-bill me-1"></i>Enregistrer un paiement
                            </button>
                        @endif
                        <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        @if($inscription->status === 'en_attente')
                            <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn btn-warning me-2">
                                <i class="fas fa-exchange-alt me-1"></i>Modifier la classe
                            </a>
                        @endif
                        <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('account_info'))
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-user-check me-2"></i>Informations de connexion générées</h6>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Nom d'utilisateur:</strong> {{ session('account_info')['username'] }}</p>
                                    <p class="mb-1"><strong>Rôle:</strong> {{ session('account_info')['role'] }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Mot de passe temporaire:</strong> <span class="badge bg-light text-dark p-2 font-monospace">{{ session('account_info')['password'] }}</span></p>
                                    <p class="mb-0 text-muted"><small>Veuillez communiquer ces informations à l'étudiant. Le mot de passe devra être changé à la première connexion.</small></p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Informations de l'étudiant -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Informations de l'étudiant</h6>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Nom:</strong> {{ $inscription->etudiant->nom }}</p>
                            <p><strong>Prénoms:</strong> {{ $inscription->etudiant->prenoms }}</p>
                            <p><strong>Matricule:</strong> {{ $inscription->etudiant->matricule }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date de naissance:</strong> {{ $inscription->etudiant->date_naissance }}</p>
                            <p><strong>Lieu de naissance:</strong> {{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</p>
                            <p><strong>Genre:</strong> {{ $inscription->etudiant->sexe === 'M' ? 'Homme' : 'Femme' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Téléphone:</strong> {{ $inscription->etudiant->telephone }}</p>
                            <p><strong>Email:</strong> {{ $inscription->etudiant->email_personnel }}</p>
                            <p><strong>Adresse:</strong> {{ $inscription->etudiant->adresse }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Ville de résidence:</strong> {{ $inscription->etudiant->ville ?? 'Non renseigné' }}</p>
                            <p><strong>Commune de résidence:</strong> {{ $inscription->etudiant->commune ?? 'Non renseigné' }}</p>
                        </div>
                    </div>

                    <!-- Informations de l'inscription -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Informations de l'inscription</h6>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Filière:</strong> {{ $inscription->filiere->name }}</p>
                            <p><strong>Niveau:</strong> {{ $inscription->niveau->name }}</p>
                            <p><strong>Classe:</strong> {{ $inscription->classe->name }}
                                @if($inscription->status === 'en_attente')
                                    <span class="text-muted small d-block">Vous pouvez modifier la classe tant que l'inscription n'est pas validée.</span>
                                @else
                                    <span class="text-muted small d-block">La classe ne peut plus être modifiée après validation.</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Année universitaire:</strong> {{ $inscription->anneeUniversitaire->name }}</p>
                            <p><strong>Date d'inscription:</strong> {{ $inscription->date_inscription }}</p>
                            <p><strong>Statut:</strong>
                                <span class="badge bg-{{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($inscription->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <!-- Frais obligatoires (dynamique) -->
                            <h5 class="card-title">Frais obligatoires</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Libellé</th>
                                            <th>Montant</th>
                                            <th>Échéance</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($mandatoryFeeCategoriesWithRules as $item)
                                        <tr>
                                            <td>{{ $item['category']->name }}</td>
                                            <td>
                                                @if($item['rule'])
                                                    {{ number_format($item['rule']->amount, 0, ',', ' ') }} FCFA
                                                @else
                                                    <span class="badge bg-warning text-dark">À configurer</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['rule'] && $item['rule']->due_date)
                                                    {{ \Carbon\Carbon::parse($item['rule']->due_date)->format('d/m/Y') }}
                                                @elseif($item['rule'])
                                                    <span class="text-muted">Non défini</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['rule'])
                                                    <a href="{{ route('esbtp.fee-categories.rules.edit', ['fee_category' => $item['category']->id, 'rule' => $item['rule']->id]) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </a>
                                                @else
                                                    <a href="{{ route('esbtp.fee-categories.edit', ['fee_category' => $item['category']->id, 'filiere_id' => $inscription->filiere_id, 'niveau_id' => $inscription->niveau_id, 'annee_universitaire_id' => $inscription->annee_universitaire_id]) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-cogs"></i> Configurer
                                                    </a>
                                                    <span class="ms-2 badge bg-danger">Obligatoire sans règle</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="alert alert-danger mb-0">
                                                    Aucun frais obligatoire n'est configuré pour cette classe. <br>
                                                    <strong>Veuillez configurer les frais d'inscription et de scolarité dans les catégories de frais.</strong>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Situation financière -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Situation financière</h6>
                        </div>
                        <div class="col-12">
                            <script>
                                // Debug: log $fees and $payments from Blade to JS console
                                console.log('DEBUG fees:', @json($fees));
                                console.log('DEBUG payments:', @json(\App\Models\ESBTP\Payment::where('inscription_id', $inscription->id)->orderBy('payment_date')->get()));
                            </script>
                            @php
                                $payments = \App\Models\ESBTP\Payment::where('inscription_id', $inscription->id)->orderBy('payment_date')->get();
                                $totalPaid = $payments->where('status', 'completed')->sum('amount');
                                $totalDue = $fees->sum('amount');
                                $soldeRestant = $totalDue - $totalPaid;
                            @endphp
                            @if($fees->count())
                                <div class="mb-3">
                                    <strong>Total à payer :</strong> {{ number_format($totalDue, 0, ',', ' ') }} FCFA<br>
                                    <strong>Total payé :</strong> {{ number_format($totalPaid, 0, ',', ' ') }} FCFA<br>
                                    <strong>Solde restant :</strong> <span class="{{ $soldeRestant > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($soldeRestant, 0, ',', ' ') }} FCFA</span>
                                </div>
                                <h6>Frais générés</h6>
                                    <table class="table table-bordered align-middle">
                                        <thead>
                                            <tr>
                                            <th>Libellé</th>
                                                <th>Montant</th>
                                                <th>Échéance</th>
                                            <th>Payé</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fees as $fee)
                                        @php
                                            $paid = $fee->payments()->where('status', 'completed')->sum('amount');
                                        @endphp
                                                <tr>
                                            <td>{{ $fee->label ?? $fee->description }}</td>
                                                    <td>{{ number_format($fee->amount, 0, ',', ' ') }} FCFA</td>
                                            <td>{{ $fee->due_date ? (is_a($fee->due_date, 'Carbon\\Carbon') ? $fee->due_date->format('d/m/Y') : \Carbon\Carbon::parse($fee->due_date)->format('d/m/Y')) : '-' }}</td>
                                            <td>{{ number_format($paid, 0, ',', ' ') }} FCFA</td>
                                            <td>
                                                @if($fee->amount <= $paid)
                                                    <span class="badge bg-success">Payé</span>
                                                @elseif($fee->due_date && \Carbon\Carbon::parse($fee->due_date)->isPast())
                                                            <span class="badge bg-danger">En retard</span>
                                                        @else
                                                    <span class="badge bg-warning text-dark">À payer</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <h6>Paiements effectués</h6>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Méthode</th>
                                            <th>Référence</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date ? (is_a($payment->payment_date, 'Carbon\\Carbon') ? $payment->payment_date->format('d/m/Y') : \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y')) : '-' }}</td>
                                            <td>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                            <td>{{ $payment->payment_method ?? '-' }}</td>
                                            <td>{{ $payment->reference_number ?? '-' }}</td>
                                            <td>
                                                @if($payment->status === 'completed')
                                                    <span class="badge bg-success">Validé</span>
                                                @elseif($payment->status === 'pending')
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                @else
                                                    <span class="badge bg-danger">{{ ucfirst($payment->status) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                            @else
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Aucun frais n'a été généré pour cette inscription.<br>
                                    <strong>Veuillez configurer les frais d'inscription et de scolarité pour cette classe dans le module de gestion des catégories de frais.</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Parents -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Parents/Tuteurs</h6>
                        </div>
                        @forelse($inscription->etudiant->parents as $parent)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $parent->nom }} {{ $parent->prenoms }}</h6>
                                        <p class="mb-1"><strong>Téléphone:</strong> {{ $parent->telephone }}</p>
                                        <p class="mb-1"><strong>Email:</strong> {{ $parent->email }}</p>
                                        <p class="mb-1"><strong>Profession:</strong> {{ $parent->profession }}</p>
                                        <p class="mb-0"><strong>Relation:</strong> {{ ucfirst($parent->pivot->relation) }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted">Aucun parent enregistré</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Paiements -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Historique des paiements</h6>
                        </div>
                        <div class="col-12">
                            @if($inscription->paiements->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Méthode</th>
                                                <th>Référence</th>
                                                <th>Commentaire</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inscription->paiements as $paiement)
                                                <tr>
                                                    <td>{{ $paiement->date }}</td>
                                                    <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                                    <td>{{ $paiement->methode }}</td>
                                                    <td>{{ $paiement->reference }}</td>
                                                    <td>{{ $paiement->commentaire }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th>{{ number_format($inscription->paiements->sum('montant'), 0, ',', ' ') }} FCFA</th>
                                                <th colspan="3"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted">Aucun paiement enregistré</p>
                            @endif
                        </div>
                    </div>

                    <h3>Paiements liés à cette inscription</h3>
                    @if($inscription->payments && $inscription->payments->count())
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscription->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '' }}</td>
                                        <td>{{ number_format($payment->amount, 2) }} F CFA</td>
                                        <td>{{ ucfirst($payment->payment_method) }}</td>
                                        <td>{{ ucfirst($payment->status) }}</td>
                                        <td>
                                            <a href="{{ route('esbtp.payments.show', $payment) }}" class="btn btn-sm btn-info">Voir</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Aucun paiement enregistré pour cette inscription.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Validation -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Valider l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('esbtp.inscriptions.valider', $inscription) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir valider cette inscription ?</p>
                    <p>L'étudiant sera automatiquement activé et pourra accéder à son compte.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider l'inscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer un paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('esbtp.paiements.store') }}" method="POST" id="paiementForm">
                @csrf
                <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
                <input type="hidden" name="etudiant_id" value="{{ $inscription->etudiant->id }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="montant">Montant</label>
                        <input type="number" class="form-control" id="montant" name="montant" required>
                    </div>
                    <div class="form-group">
                        <label for="date_paiement">Date de paiement</label>
                        <input type="date" class="form-control" id="date_paiement" name="date_paiement" required>
                    </div>
                    <div class="form-group">
                        <label for="mode_paiement">Mode de paiement</label>
                        <select class="form-control" id="mode_paiement" name="mode_paiement" required>
                            <option value="especes">Espèces</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="carte">Carte bancaire</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reference_paiement">Référence du paiement</label>
                        <input type="text" class="form-control" id="reference_paiement" name="reference_paiement">
                    </div>
                    <div class="form-group">
                        <label for="motif">Motif</label>
                        <select class="form-control" id="motif" name="motif" required>
                            <option value="inscription">Frais d'inscription</option>
                            <option value="scolarite">Frais de scolarité</option>
                            <option value="examen">Frais d'examen</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="commentaire">Commentaire</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Debug : afficher le tableau complet dans la console
    console.log('mandatoryFeeCategoriesWithRules:', @json($mandatoryFeeCategoriesWithRules));
</script>
@endpush
