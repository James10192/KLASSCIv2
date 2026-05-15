            @php
                $formIdentifier = $formId ?? ('inscription-edit-form-' . $inscription->id);
                $placesInfoId = $placesInfoId ?? ($formIdentifier . '-places');
                $isEmbedded = $isEmbedded ?? false;
                $formWrapperId = $formIdentifier . '-wrapper';
                $successNoticeId = $formIdentifier . '-success';
                $embeddedSuccessMessage = $isEmbedded ? session('embedded_success_inscription') : null;

                // LMD switch : déterminer le mode initial depuis la classe en cours.
                // Source de vérité = classe.systeme_academique (rule classe-lmd-filiere-as-mention).
                // Si pas de classe : utiliser le type du niveau de l'inscription comme indicateur.
                $mentionsCollection = isset($mentions) ? $mentions : collect();
                $parcoursCollection = isset($parcours) ? $parcours : collect();
                $niveauTypesJson = $niveaux->mapWithKeys(fn($n) => [$n->id => $n->type])->toJson();

                $insRenderedMode = '';
                $insInitialMentionId = '';
                $insInitialParcoursId = '';
                if ($inscription->classe?->systeme_academique === 'LMD') {
                    $insRenderedMode = 'LMD';
                    if ($inscription->classe->parcours) {
                        $insInitialParcoursId = $inscription->classe->parcours_id;
                        $insInitialMentionId = $inscription->classe->parcours->mention_id ?? '';
                    } else {
                        // Tronc commun mention : convention Option A → classe.filiere_id stocke mention_id
                        $insInitialMentionId = $inscription->classe->filiere_id ?? '';
                    }
                } elseif ($inscription->classe?->systeme_academique === 'BTS' || ($inscription->niveau && in_array($inscription->niveau->type, ['BTS', 'BAC+2', 'DUT'], true))) {
                    $insRenderedMode = 'BTS';
                } elseif ($inscription->niveau && in_array($inscription->niveau->type, ['Licence', 'Master', 'Doctorat'], true)) {
                    $insRenderedMode = 'LMD';
                }
            @endphp

            @if($isEmbedded && $embeddedSuccessMessage)
                <div class="alert alert-success d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3" id="{{ $successNoticeId }}">
                    <div>
                        <strong class="d-block mb-1"><i class="fas fa-check-circle me-1"></i>Inscription mise à jour</strong>
                        <span>{{ $embeddedSuccessMessage }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-embedded-toggle="form" data-target="{{ $formWrapperId }}" data-notice="{{ $successNoticeId }}">
                            <i class="fas fa-edit me-1"></i>Modifier cette inscription
                        </button>
                        <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>Voir la fiche
                        </a>
                    </div>
                </div>
            @endif

            <div id="{{ $formWrapperId }}" class="{{ $isEmbedded && $embeddedSuccessMessage ? 'd-none' : '' }}">
            <form method="POST" action="{{ route('esbtp.inscriptions.update', $inscription->id) }}" id="{{ $formIdentifier }}" data-inscription-id="{{ $inscription->id }}" data-places-info-target="{{ $placesInfoId }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="embedded_mode" value="{{ $isEmbedded ? 1 : 0 }}">

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Informations de l'étudiant (lecture seule) -->
                <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:rgba(4,83,203,0.04);border:1px solid rgba(4,83,203,0.1);border-radius:12px;margin-bottom:20px;">
                    <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:0.85rem;color:#334155;line-height:1.6;">
                            <span><span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;display:block;">Étudiant</span><strong style="color:#1e293b;">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</strong></span>
                            <span><span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;display:block;">Matricule</span><strong style="color:#1e293b;">{{ $inscription->etudiant->matricule }}</strong></span>
                            <span><span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;display:block;">Date de naissance</span><strong style="color:#1e293b;">{{ $inscription->etudiant->date_naissance?->format('d/m/Y') ?? 'Non renseignée' }}</strong></span>
                        </div>
                        <a href="{{ route('esbtp.etudiants.show', $inscription->etudiant) }}" style="font-size:0.78rem;color:#0453cb;font-weight:600;margin-top:6px;display:inline-flex;align-items:center;gap:4px;">
                            <i class="fas fa-external-link-alt" style="font-size:0.65rem;"></i> Modifier le profil étudiant
                        </a>
                    </div>
                </div>

                <!-- Informations de l'inscription -->
                <div style="display:flex;align-items:center;gap:10px;font-size:0.88rem;font-weight:700;color:#1e293b;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #f1f5f9;">
                    <span style="width:28px;height:28px;border-radius:8px;background:rgba(4,83,203,0.08);color:#0453cb;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"><i class="fas fa-graduation-cap"></i></span>
                    Informations de l'inscription
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_inscription">Date d'inscription <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_inscription') is-invalid @enderror" id="date_inscription" name="date_inscription" value="{{ old('date_inscription', $inscription->date_inscription->format('Y-m-d')) }}" required>
                            @error('date_inscription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="type_inscription">Type d'inscription <span class="text-danger">*</span></label>
                            <select class="form-control @error('type_inscription') is-invalid @enderror" id="type_inscription" name="type_inscription" required>
                                <option value="première_inscription" {{ old('type_inscription', $inscription->type_inscription) == 'première_inscription' ? 'selected' : '' }}>Première inscription</option>
                                <option value="réinscription" {{ old('type_inscription', $inscription->type_inscription) == 'réinscription' ? 'selected' : '' }}>Réinscription</option>
                                <option value="transfert" {{ old('type_inscription', $inscription->type_inscription) == 'transfert' ? 'selected' : '' }}>Transfert</option>
                            </select>
                            @error('type_inscription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Statut <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="en_attente" {{ old('status', $inscription->status) == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                <option value="active" {{ old('status', $inscription->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="annulée" {{ old('status', $inscription->status) == 'annulée' ? 'selected' : '' }}>Annulée</option>
                                <option value="terminée" {{ old('status', $inscription->status) == 'terminée' ? 'selected' : '' }}>Terminée</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section Transfert (visible seulement si type_inscription = 'première_inscription') -->
                <div class="row mb-3" id="transfert-section" style="display: {{ old('type_inscription', $inscription->type_inscription) == 'première_inscription' ? 'flex' : 'none' }}">
                    <div class="col-md-12">
                        <div style="display:flex;align-items:center;gap:10px;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">
                            <span style="width:28px;height:28px;border-radius:8px;background:rgba(245,158,11,0.1);color:#d97706;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"><i class="fas fa-exchange-alt"></i></span>
                            Informations de transfert
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="est_transfert">L'étudiant vient-il d'un autre établissement ?</label>
                            <select class="form-control @error('est_transfert') is-invalid @enderror" id="est_transfert" name="est_transfert">
                                <option value="0" {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '0' ? 'selected' : '' }}>Non</option>
                                <option value="1" {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '1' ? 'selected' : '' }}>Oui</option>
                            </select>
                            @error('est_transfert')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="etablissement-origine-field" style="display: {{ old('est_transfert', $inscription->est_transfert ? '1' : '0') == '1' ? 'block' : 'none' }}">
                        <div class="form-group">
                            <label for="etablissement_origine">Nom de l'établissement d'origine</label>
                            <input type="text" class="form-control @error('etablissement_origine') is-invalid @enderror" id="etablissement_origine" name="etablissement_origine" value="{{ old('etablissement_origine', $inscription->etablissement_origine) }}" placeholder="Ex: Lycée Technique d'Abidjan">
                            <small class="form-text text-muted">Optionnel - Indiquez l'établissement d'où vient l'étudiant</small>
                            @error('etablissement_origine')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @if($inscription->status !== 'active' || auth()->user()->can('admin.access'))
                    {{-- ZONE LMD-AWARE :
                         - Niveau d'études (commun) — son type détermine BTS|LMD
                         - Mode BTS : <select name="filiere_id"> classique
                         - Mode LMD : <x-au-mention-picker> + <x-au-parcours-picker> (filtres uniquement)
                         - <select name="classe_id"> filtré par JS selon mode + filtres LMD
                         - filiere_id final : en BTS depuis le select, en LMD dérivé serveur-side
                           depuis classe.filiere_id (cf controller update + rule classe-lmd-filiere-as-mention).
                    --}}
                    <div class="row mb-3" id="inscription-academic-section"
                         data-mode="{{ strtolower($insRenderedMode ?: 'unknown') }}"
                         data-niveau-types='{!! $niveauTypesJson !!}'>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="niveau_id">Niveau d'études <span class="text-danger">*</span></label>
                                <select class="form-control @error('niveau_id') is-invalid @enderror" id="niveau_id" name="niveau_id" required>
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}" data-type="{{ $niveau->type }}" {{ old('niveau_id', $inscription->niveau_id) == $niveau->id ? 'selected' : '' }}>
                                            {{ $niveau->name }} ({{ $niveau->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('niveau_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Mode :
                                    <span id="ins-mode-badge" class="badge ms-1" style="background:{{ $insRenderedMode === 'LMD' ? '#0453cb' : ($insRenderedMode === 'BTS' ? '#64748b' : '#cbd5e1') }};color:#fff;">
                                        {{ $insRenderedMode ?: '—' }}
                                    </span>
                                </small>
                            </div>
                        </div>

                        {{-- BTS : Filière classique. Wrappé en <fieldset disabled> pour que le browser
                             n'inclue PAS filiere_id dans le POST quand on est en mode LMD. --}}
                        <fieldset class="col-md-4 ins-bts-fieldset"
                                  {{ $insRenderedMode !== 'BTS' ? 'disabled' : '' }}
                                  style="border:0;padding:0;margin:0;{{ $insRenderedMode !== 'BTS' ? 'display:none;' : '' }}">
                            <div class="form-group">
                                <label for="filiere_id">Filière <span class="text-danger">*</span></label>
                                <select class="form-control @error('filiere_id') is-invalid @enderror" id="filiere_id" name="filiere_id">
                                    <option value="">Sélectionner une filière</option>
                                    @foreach($filieres as $filiere)
                                        <option value="{{ $filiere->id }}" {{ old('filiere_id', $inscription->filiere_id) == $filiere->id ? 'selected' : '' }}>
                                            {{ $filiere->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('filiere_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </fieldset>

                        {{-- LMD : Mention picker + Parcours picker. Pickers servent UNIQUEMENT à filtrer
                             les classes affichées dans le <select classe_id>. Pas de submit (name avec
                             prefix lmd_filter_*). filiere_id final dérivé serveur-side depuis classe.filiere_id. --}}
                        <fieldset class="col-md-8 ins-lmd-fieldset"
                                  {{ $insRenderedMode !== 'LMD' ? 'disabled' : '' }}
                                  style="border:0;padding:0;margin:0;{{ $insRenderedMode !== 'LMD' ? 'display:none;' : '' }}">
                            @if($mentionsCollection->isEmpty())
                                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
                                    <i class="fas fa-exclamation-triangle mt-1"></i>
                                    <div>
                                        <strong>Aucune mention LMD configurée pour cette instance.</strong>
                                        <div class="small mt-1">L'utilisateur peut quand même choisir une classe LMD ci-dessous ; mais le filtrage par mention/parcours est désactivé.</div>
                                    </div>
                                </div>
                            @else
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mention LMD <small class="text-muted">(filtre)</small></label>
                                        <x-au-mention-picker
                                            name="lmd_filter_mention_id"
                                            :value="$insInitialMentionId"
                                            :mentions="$mentionsCollection"
                                            placeholder="Toutes les mentions"
                                        />
                                        <small class="form-text text-muted">Filtre les classes LMD affichées ci-dessous.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Parcours <small class="text-muted">(filtre, optionnel)</small></label>
                                        <x-au-parcours-picker
                                            name="lmd_filter_parcours_id"
                                            :value="$insInitialParcoursId"
                                            :parcours="$parcoursCollection"
                                            :mention-filter="$insInitialMentionId"
                                        />
                                        <small class="form-text text-muted">Spécialisation au sein de la mention.</small>
                                    </div>
                                </div>
                            @endif
                        </fieldset>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="classe_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <select name="classe_id" id="classe_id" class="form-select" required data-places-indicator="{{ $placesInfoId }}">
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}"
                                                data-filiere-id="{{ $classe->filiere_id }}"
                                                data-niveau-id="{{ $classe->niveau_etude_id }}"
                                                data-systeme="{{ $classe->systeme_academique ?? 'BTS' }}"
                                                data-mention-id="{{ optional($classe->parcours)->mention_id ?? '' }}"
                                                data-parcours-id="{{ $classe->parcours_id ?? '' }}"
                                                @if($inscription->classe_id == $classe->id) selected @endif>
                                            {{ $classe->name }}
                                            @if($classe->filiere && $classe->niveauEtude)
                                                ({{ $classe->filiere->name }} - {{ $classe->niveauEtude->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div id="{{ $placesInfoId }}" class="mt-2 small text-muted"></div>
                                <div class="form-text text-muted">Vous pouvez changer la classe tant que l'inscription n'est pas activée.</div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Status = active + non-admin : tout est en lecture seule --}}
                    <input type="hidden" name="filiere_id" value="{{ $inscription->filiere_id }}">
                    <input type="hidden" name="niveau_id" value="{{ $inscription->niveau_id }}">
                    <input type="hidden" name="classe_id" value="{{ $inscription->classe_id }}">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Filière</label>
                                <input type="text" class="form-control" value="{{ $inscription->filiere->name ?? '—' }}" disabled>
                                <div class="alert alert-warning mt-2">La filière ne peut plus être modifiée après activation de l'inscription.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Niveau d'études</label>
                                <input type="text" class="form-control" value="{{ $inscription->niveau->name ?? '—' }}" disabled>
                                <div class="alert alert-warning mt-2">Le niveau ne peut plus être modifié après activation de l'inscription.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Classe</label>
                                <input type="text" class="form-control" value="{{ $inscription->classe?->name ?? 'Non affecté' }}" disabled>
                                <div id="{{ $placesInfoId }}" class="mt-2 small text-muted"></div>
                                <div class="alert alert-warning mt-2">La classe ne peut plus être modifiée après activation de l'inscription.</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="affectation_status">Statut d'affectation</label>
                            <select class="form-control @error('affectation_status') is-invalid @enderror" id="affectation_status" name="affectation_status">
                                <option value="">-- Sélectionner le statut --</option>
                                <option value="affecté" {{ old('affectation_status', $inscription->affectation_status) == 'affecté' ? 'selected' : '' }}>Affecté</option>
                                <option value="réaffecté" {{ old('affectation_status', $inscription->affectation_status) == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                                <option value="non_affecté" {{ old('affectation_status', $inscription->affectation_status) == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                            </select>
                            <div class="form-text text-muted">
                                Le statut d'affectation détermine les frais de scolarité selon les subventions gouvernementales ivoiriennes.
                            </div>
                            @error('affectation_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="montant_scolarite">Montant scolarité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('montant_scolarite') is-invalid @enderror" id="montant_scolarite" name="montant_scolarite" value="{{ old('montant_scolarite', $inscription->montant_scolarite) }}" min="0" required>
                            @error('montant_scolarite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="observations">Observations</label>
                            <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3">{{ old('observations', $inscription->observations) }}</textarea>
                            @error('observations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
            </div>
