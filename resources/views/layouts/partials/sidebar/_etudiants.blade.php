{{-- Section « Etudiants » de la barre laterale. Incluse par layouts/app ; elle lit
     $candidaturesEnAttente et $reinscriptionDemandesEnAttente, partages par la mise en page. --}}
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
                            $_navAccueil = Request::routeIs('esbtp.rendez-vous.accueil.*');
                            $_navPlanning = Request::routeIs('esbtp.rendez-vous.*') && ! $_navAccueil;
                            $_navEtudiants = Request::routeIs('esbtp.etudiants.*') || Request::routeIs('esbtp.accessibility.*') || Request::routeIs('esbtp.trash.*');
                            $_navAdmissions = Request::routeIs('esbtp.candidatures.*') || Request::routeIs('esbtp.reinscription-demandes.*') || Request::routeIs('esbtp.rendez-vous.*');
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
                                <div class="menu-text">Admissions et accueil</div>
                                <div class="menu-arrow"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="menu-accordion-content {{ $_navAdmissions ? 'show' : '' }}">
                                {{-- Candidatures des NOUVEAUX etudiants, distinctes des demandes de
                                     reinscription : ce ne sont pas les memes dossiers, et la
                                     scolarite ne les traite pas au meme moment de la rentree. --}}
                                @can('inscriptions.candidatures.view')
                                <a href="{{ route('esbtp.candidatures.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.candidatures.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-address-card"></i></div>
                                    <div class="menu-text">
                                        <span class="menu-label">Candidatures en ligne</span>
                                        @if(($candidaturesEnAttente ?? 0) > 0)
                                            <span class="menu-badge" title="{{ $candidaturesEnAttente }} en attente">{{ $_navBadge($candidaturesEnAttente) }}</span>
                                        @endif
                                    </div>
                                </a>
                                @endcan
                                @can('reinscriptions.demandes.view')
                                <a href="{{ route('esbtp.reinscription-demandes.index') }}" class="menu-sublink {{ Request::routeIs('esbtp.reinscription-demandes.*') ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-inbox"></i></div>
                                    <div class="menu-text">
                                        <span class="menu-label">Demandes de réinscription</span>
                                        @if(($reinscriptionDemandesEnAttente ?? 0) > 0)
                                            <span class="menu-badge" title="{{ $reinscriptionDemandesEnAttente }} en attente">{{ $_navBadge($reinscriptionDemandesEnAttente) }}</span>
                                        @endif
                                    </div>
                                </a>
                                @endcan
                                @can('inscriptions.rdv.view')
                                <a href="{{ route('esbtp.rendez-vous.index') }}" class="menu-sublink {{ $_navPlanning ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                                    <div class="menu-text"><span class="menu-label">Rendez-vous</span></div>
                                </a>
                                @endcan
                                @can('inscriptions.rdv.accueil')
                                <a href="{{ route('esbtp.rendez-vous.accueil.index') }}" class="menu-sublink {{ $_navAccueil ? 'active' : '' }}">
                                    <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                                    <div class="menu-text"><span class="menu-label">Accueil du jour</span></div>
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
