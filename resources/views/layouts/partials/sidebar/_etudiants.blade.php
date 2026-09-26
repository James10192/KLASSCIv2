{{-- Section « Etudiants » de la barre laterale. Incluse par layouts/app ; elle lit
     $demandesATraiter, $accueilAttendues (FileDesDemandes), $dossiersParType et
     $famillesAPrevenir (CompteursDuMenu), partages par AppServiceProvider. --}}
                    <!-- Students Section -->
                    @can('module.etudiants.access')
                    @if(!auth()->user()->can('module.caisse.access') || auth()->user()->canAny(['module.comptabilite.access', 'identity.school_manager', 'identity.direct_studies', 'identity.registrar', 'identity.registrar_clerk', 'identity.enrollment_officer', 'identity.communicate']) || auth()->user()->hasRole(['superAdmin', 'admin', 'serviceTechnique']))
                    {{-- `inscriptions.candidatures.view` fait partie de la liste : sans elle,
                         l'agent d'un role taille sur mesure — candidatures seules, ce que le
                         produit encourage — ne voyait pas la categorie qui contient sa seule
                         entree, et arrivait sur la corbeille sans que rien ne l'y situe. --}}
                    @if(auth()->user()->canAny(['students.view', 'inscriptions.view', 'inscriptions.create', 'reinscriptions.demandes.view', 'inscriptions.candidatures.view', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil', 'pieces_dossier.view', 'students.accessibility.view']))
                        <div class="menu-category">Étudiants</div>

                        {{-- Trois accordeons, dans l'ordre ou un dossier les traverse : l'etudiant
                             inscrit, la famille qui arrive (candidature, demande, rendez-vous,
                             guichet), puis le dossier d'inscription. Un seul accordeon de douze
                             entrees depassait la hauteur maximale du panneau et coupait la fin. --}}
                        @php
                            // « Aujourd'hui » couvre aussi l'Accueil du jour detaille, qu'il ouvre.
                            $_navJour = Request::routeIs('esbtp.admissions.aujourdhui') || Request::routeIs('esbtp.rendez-vous.accueil.*');
                            $_navPlanning = Request::routeIs('esbtp.rendez-vous.*') && ! Request::routeIs('esbtp.rendez-vous.accueil.*');
                            $_navDossiers = Request::routeIs('esbtp.demandes.*') || Request::routeIs('esbtp.candidatures.*') || Request::routeIs('esbtp.reinscription-demandes.*');
                            $_navTypeDossier = $_navDossiers ? (string) request()->query('type', '') : '';
                            $_navEtudiants = Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.accessibility.*') || Request::routeIs('esbtp.trash.*');
                            $_navAdmissions = $_navDossiers || $_navJour || Request::routeIs('esbtp.rendez-vous.*');
                            $_navInscriptions = Request::routeIs('esbtp.inscriptions.*') || Request::routeIs('esbtp.reinscription.*') || Request::routeIs('esbtp.pieces-dossier.*');
                            $_navBadge = fn ($n) => $n > 999 ? '999+' : (string) $n;
                        @endphp

                        @if(auth()->user()->canAny(['students.view', 'students.accessibility.view', 'trash.view']))
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ $_navEtudiants ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="menu-text">Étudiants</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ $_navEtudiants ? 'show' : '' }}">
                                @can('students.view')
                                <a href="{{ route('esbtp.etudiants.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.etudiants.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-list"></i></div>
                                    <div class="menu-text"><span class="menu-label">Liste des étudiants</span></div>
                                </a>
                                @endcan
                                @can('students.accessibility.view')
                                <a href="{{ route('esbtp.accessibility.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.accessibility.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-universal-access"></i></div>
                                    <div class="menu-text"><span class="menu-label">Accessibilité</span></div>
                                </a>
                                @endcan
                                @can('trash.view')
                                <a href="{{ route('esbtp.trash.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.trash.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-trash-restore"></i></div>
                                    <div class="menu-text"><span class="menu-label">Corbeille</span></div>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endif

                        {{-- `reinscriptions.demandes.view` ouvre aussi la section plus haut, pour
                             qu'un role d'accueil ne portant que cette permission voie le lien.
                             Deux conditions de section subsistent, plus larges que la route :
                             `module.etudiants.access`, et la garde caisse. Un role porteur de
                             `module.caisse.access` sans identite scolarite atteint donc la page
                             sans voir l'entree de menu. --}}
                        @if(auth()->user()->canAny(['inscriptions.candidatures.view', 'reinscriptions.demandes.view', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil']))
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ $_navAdmissions ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-door-open"></i></div>
                                <div class="menu-text">Admissions</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ $_navAdmissions ? 'show' : '' }}">
                                {{-- Dans l'ordre d'une journee d'accueil : qui arrive aujourd'hui, les
                                     dossiers (candidatures et reinscriptions dans une seule liste, le
                                     type n'est qu'un filtre), le planning, puis les familles a appeler.
                                     Les anciennes adresses (corbeilles, Accueil du jour) restent
                                     joignables : redirection, ou lien depuis « Aujourd'hui ». --}}
                                @can('inscriptions.rdv.accueil')
                                <a href="{{ route('esbtp.admissions.aujourdhui') }}" class="menu-sublink {{ $_navJour ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-clock"></i></div>
                                    <div class="menu-text">
                                        <span class="menu-label">Aujourd'hui</span>
                                        @if(($accueilAttendues ?? 0) > 0)
                                            <span class="menu-badge" title="{{ $accueilAttendues }} famille(s) encore attendue(s) aujourd'hui">{{ $_navBadge($accueilAttendues) }}</span>
                                        @endif
                                    </div>
                                </a>
                                @endcan
                                @canany(['inscriptions.candidatures.view', 'reinscriptions.demandes.view'])
                                <a href="{{ route('esbtp.demandes.index') }}" class="menu-sublink {{ $_navDossiers && $_navTypeDossier === '' ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-folder-open"></i></div>
                                    <div class="menu-text">
                                        <span class="menu-label">Dossiers</span>
                                        @if(($demandesATraiter ?? 0) > 0)
                                            <span class="menu-badge" title="{{ $demandesATraiter }} dossier(s) ouvert(s)">{{ $_navBadge($demandesATraiter) }}</span>
                                        @endif
                                    </div>
                                </a>
                                @foreach([
                                    \App\Domain\Admissions\FileDesDemandes::TYPE_NOUVELLE => 'Nouvelles inscriptions',
                                    \App\Domain\Admissions\FileDesDemandes::TYPE_REINSCRIPTION => 'Réinscriptions',
                                ] as $_type => $_libelle)
                                    @if(array_key_exists($_type, $dossiersParType ?? []))
                                    <a href="{{ route('esbtp.demandes.index', ['type' => $_type]) }}" class="menu-sublink menu-sublink--enfant {{ $_navTypeDossier === $_type ? 'active' : '' }}">
                                        <div class="menu-text">
                                            <span class="menu-label">{{ $_libelle }}</span>
                                            @if($dossiersParType[$_type] > 0)
                                                <span class="menu-badge menu-badge--discret" title="{{ $dossiersParType[$_type] }} ouvert(s)">{{ $_navBadge($dossiersParType[$_type]) }}</span>
                                            @endif
                                        </div>
                                    </a>
                                    @endif
                                @endforeach
                                @endcanany
                                @can('inscriptions.rdv.view')
                                <a href="{{ route('esbtp.rendez-vous.index') }}" class="menu-sublink {{ $_navPlanning ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                                    <div class="menu-text"><span class="menu-label">Planning des rendez-vous</span></div>
                                </a>
                                <a href="{{ route('esbtp.rendez-vous.index') }}#rdv-a-prevenir" class="menu-sublink">
                                    <div class="menu-icon"><i class="fas fa-phone-volume"></i></div>
                                    <div class="menu-text">
                                        <span class="menu-label">Familles à prévenir</span>
                                        @if(($famillesAPrevenir ?? 0) > 0)
                                            <span class="menu-badge menu-badge--alerte" title="{{ $famillesAPrevenir }} famille(s) sans convocation reçue par e-mail">{{ $_navBadge($famillesAPrevenir) }}</span>
                                        @endif
                                    </div>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endif

                        @if(auth()->user()->canAny(['inscriptions.view', 'inscriptions.create', 'pieces_dossier.view']))
                        <div class="menu-accordion">
                            <button class="menu-accordion-btn {{ $_navInscriptions ? 'active' : '' }}">
                                <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                                <div class="menu-text">Inscriptions</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ $_navInscriptions ? 'show' : '' }}">
                                @can('inscriptions.create')
                                <a href="{{ route('esbtp.inscriptions.create') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.create') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-user-plus"></i></div>
                                    <div class="menu-text"><span class="menu-label">Nouvelle inscription</span></div>
                                </a>
                                @endcan
                                @can('inscriptions.view')
                                <a href="{{ route('esbtp.inscriptions.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.index') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="menu-text"><span class="menu-label">Liste des inscriptions</span></div>
                                </a>
                                <a href="{{ route('esbtp.reinscription.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.reinscription.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-redo"></i></div>
                                    <div class="menu-text"><span class="menu-label">Réinscriptions</span></div>
                                </a>
                                <a href="{{ route('esbtp.inscriptions.sous-reserve') }}" class="menu-sublink {{ Request::routeIs('esbtp.inscriptions.sous-reserve') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-hourglass-half"></i></div>
                                    <div class="menu-text"><span class="menu-label">Sous réserve</span></div>
                                </a>
                                @endcan
                                {{-- Voisin de « Sous réserve » sans s'y confondre : une réserve porte
                                     sur un document pas encore délivré, une pièce à fournir sur un
                                     document qui existe et que l'école attend. Le catalogue dit ce
                                     que l'ecole reclame ; le suivi dit qui ne l'a pas encore rendu. --}}
                                @can('pieces_dossier.view')
                                <a href="{{ route('esbtp.pieces-dossier.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.pieces-dossier.index') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-list-check"></i></div>
                                    <div class="menu-text"><span class="menu-label">Pièces à fournir</span></div>
                                </a>
                                <a href="{{ route('esbtp.pieces-dossier.suivi') }}" class="menu-sublink {{ Request::routeIs('esbtp.pieces-dossier.suivi') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-folder-open"></i></div>
                                    <div class="menu-text"><span class="menu-label">Suivi des dossiers</span></div>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endif
                    @endif
                    @endif
                    @endcan
