@props([
    'etudiant',
    'ouvrirAuChargement' => false,
])

@php
    $_phxCaptures = app(\App\Services\Photos\CapturePhotoParTelephone::class);
    $_phxDejaUnePhoto = ! empty($etudiant->photo_url);
    $_phxCharge = [
        'etudiantId' => $etudiant->id,
        'nom' => trim(($etudiant->prenoms ?? '').' '.($etudiant->nom ?? '')),
        'photoActuelle' => $etudiant->photo_url,
        'aDejaUnePhoto' => $_phxDejaUnePhoto,
        // On ne demande confirmation que s'il y a quelque chose a remplacer. La
        // question « voulez-vous mettre a jour ? » devant un cadre vide n'a pas
        // de sens, et c'est le cas de toute premiere inscription.
        'confirmer' => $_phxDejaUnePhoto && $_phxCaptures->confirmerLeRemplacement(),
        'telephoneActif' => $_phxCaptures->actif(),
        'peutEditer' => auth()->user()?->can('students.edit') ?? false,
        'ouvrirAuChargement' => (bool) $ouvrirAuChargement,
        'urls' => [
            'televerser' => route('esbtp.etudiants.update-photo', $etudiant),
            'supprimer' => route('esbtp.etudiants.destroy-photo', $etudiant),
            'ouvrirCapture' => route('esbtp.captures-photo.ouvrir', $etudiant),
            'capture' => url('/esbtp/captures-photo'),
        ],
    ];
@endphp

{{--
    La photo d'un étudiant : trois chemins vers le même résultat.

    Téléverser un fichier, photographier avec la caméra du poste, ou passer par
    un téléphone via un code QR. Le troisième existe parce que le poste du
    guichet n'a souvent pas de caméra, ou en a une mauvaise, alors que le
    téléphone qui est dans la poche photographie très bien.

    Le dialogue s'ouvre tout seul juste après la création d'une inscription — le
    compte de l'étudiant existe à ce moment-là — et se rappelle à l'écran depuis
    la fiche à tout moment ensuite.
--}}
<div class="phx" data-phx='@json($_phxCharge)' x-data="photoEtudiant()" x-init="init()">

    <div class="phx-voile" x-show="ouvert" x-cloak
         @keydown.escape.window="fermer()"
         @click.self="fermer()">
        <div class="phx-boite">

            <div class="phx-entete">
                <div class="phx-entete-icone"><i class="fas fa-camera"></i></div>
                <div>
                    <div class="phx-titre">Photo de l'étudiant</div>
                    <div class="phx-sous" x-text="nom"></div>
                </div>
                <button type="button" class="phx-fermer" @click="fermer()" aria-label="Fermer">&times;</button>
            </div>

            {{-- Écran 0 — la question de la réinscription. --}}
            <div class="phx-corps" x-show="ecran === 'confirmer'">
                <div class="phx-actuelle">
                    <img :src="photoActuelle" alt="Photo actuelle">
                </div>
                <p class="phx-texte">
                    Cet étudiant a déjà une photo. Voulez-vous la mettre à jour ?
                </p>
                <div class="phx-pied">
                    <button type="button" class="phx-btn" @click="fermer()">Garder celle-ci</button>
                    <button type="button" class="phx-btn phx-btn--primaire" @click="ecran = 'choix'">Mettre à jour</button>
                </div>
            </div>

            {{-- Écran 1 — par où passer. --}}
            <div class="phx-corps" x-show="ecran === 'choix'">
                <div class="phx-chemins">
                    <button type="button" class="phx-chemin" @click="choisirFichier()">
                        <span class="phx-chemin-icone"><i class="fas fa-upload"></i></span>
                        <span class="phx-chemin-titre">Téléverser</span>
                        <span class="phx-chemin-aide">Une photo déjà sur cet ordinateur</span>
                    </button>

                    <button type="button" class="phx-chemin" @click="ouvrirCamera()">
                        <span class="phx-chemin-icone"><i class="fas fa-video"></i></span>
                        <span class="phx-chemin-titre">Cet appareil</span>
                        <span class="phx-chemin-aide">Avec la caméra du poste</span>
                    </button>

                    <button type="button" class="phx-chemin" x-show="telephoneActif" @click="ouvrirTelephone()">
                        <span class="phx-chemin-icone"><i class="fas fa-mobile-screen"></i></span>
                        <span class="phx-chemin-titre">Mon téléphone</span>
                        <span class="phx-chemin-aide">Scanner un code et photographier</span>
                    </button>
                </div>

                <input type="file" accept="image/*" x-ref="fichier" @change="fichierChoisi($event)" hidden>
            </div>

            {{-- Écran 2 — la caméra du poste. --}}
            <div class="phx-corps" x-show="ecran === 'camera'">
                <div class="phx-scene">
                    <video x-ref="video" autoplay playsinline muted x-show="!clichePris"></video>
                    <img :src="apercu" x-show="clichePris" x-cloak alt="Cliché">
                </div>
                <div class="phx-erreur" x-show="erreur" x-text="erreur"></div>
                <div class="phx-pied">
                    <button type="button" class="phx-btn" @click="retourAuChoix()">Retour</button>
                    <button type="button" class="phx-btn" x-show="clichePris" @click="reprendreCliche()">Reprendre</button>
                    <button type="button" class="phx-btn phx-btn--primaire" x-show="!clichePris"
                            :disabled="!cameraPrete" @click="prendreCliche()">Prendre la photo</button>
                    <button type="button" class="phx-btn phx-btn--primaire" x-show="clichePris"
                            :disabled="occupe" @click="enregistrerCliche()">Enregistrer</button>
                </div>
            </div>

            {{-- Écran 3 — le fichier choisi, avant envoi. --}}
            <div class="phx-corps" x-show="ecran === 'fichier'">
                <div class="phx-scene"><img :src="apercu" alt="Aperçu"></div>
                <div class="phx-erreur" x-show="erreur" x-text="erreur"></div>
                <div class="phx-pied">
                    <button type="button" class="phx-btn" @click="retourAuChoix()">Retour</button>
                    <button type="button" class="phx-btn" @click="choisirFichier()">Choisir une autre</button>
                    <button type="button" class="phx-btn phx-btn--primaire" :disabled="occupe"
                            @click="enregistrerFichier()">Enregistrer</button>
                </div>
            </div>

            {{-- Écran 4 — le code à scanner, puis la photo reçue. --}}
            <div class="phx-corps" x-show="ecran === 'telephone'">
                <template x-if="!captureRecue">
                    <div class="phx-qr-zone">
                        <div class="phx-qr">
                            <img :src="qr" x-show="qr" alt="Code à scanner">
                            <div class="phx-qr-absent" x-show="!qr" x-cloak>
                                Code non disponible sur cette installation.
                            </div>
                        </div>
                        <p class="phx-texte">
                            Scannez ce code avec l'appareil photo du téléphone, puis prenez la photo.
                            Elle apparaîtra ici.
                        </p>
                        <div class="phx-lien" x-show="!qr" x-cloak>
                            <span x-text="url"></span>
                        </div>
                        <div class="phx-minuteur" x-text="minuteur"></div>
                    </div>
                </template>

                <template x-if="captureRecue">
                    <div>
                        <div class="phx-scene"><img :src="apercu" alt="Photo reçue"></div>
                        <p class="phx-texte">Photo reçue. Est-elle bonne ?</p>
                    </div>
                </template>

                <div class="phx-erreur" x-show="erreur" x-text="erreur"></div>

                <div class="phx-pied">
                    <button type="button" class="phx-btn" @click="retourAuChoix()">Retour</button>
                    <button type="button" class="phx-btn" x-show="captureRecue" :disabled="occupe"
                            @click="refaireCapture()">Refaire</button>
                    <button type="button" class="phx-btn phx-btn--primaire" x-show="captureRecue" :disabled="occupe"
                            @click="accepterCapture()">Valider cette photo</button>
                    <button type="button" class="phx-btn" x-show="!captureRecue" :disabled="occupe"
                            @click="ouvrirTelephone()">Nouveau code</button>
                </div>
            </div>

            {{-- Écran 5 — c'est fait. --}}
            <div class="phx-corps" x-show="ecran === 'fini'">
                <div class="phx-scene"><img :src="photoActuelle" alt="Photo enregistrée"></div>
                <p class="phx-texte phx-texte--ok">Photo enregistrée.</p>
                <div class="phx-pied">
                    <button type="button" class="phx-btn phx-btn--primaire" @click="fermer()">Terminer</button>
                </div>
            </div>

            <canvas x-ref="toile" hidden></canvas>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .phx-voile {
        position: fixed; inset: 0; z-index: 1085;
        background: rgba(15,23,42,.5); backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center; padding: 1rem;
    }
    .phx-boite {
        background: #fff; border-radius: 16px; width: 100%; max-width: 560px;
        box-shadow: 0 24px 70px rgba(15,23,42,.30); overflow: hidden;
        max-height: 92vh; display: flex; flex-direction: column;
    }
    .phx-entete {
        display: flex; align-items: center; gap: .8rem; padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
        color: #fff;
    }
    .phx-entete-icone {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .phx-titre { font-size: 1rem; font-weight: 700; }
    .phx-sous { font-size: .8rem; color: rgba(255,255,255,.75); }
    .phx-fermer {
        margin-left: auto; background: none; border: none; color: #fff;
        font-size: 1.6rem; line-height: 1; cursor: pointer; opacity: .8; padding: 0 .2rem;
    }
    .phx-fermer:hover { opacity: 1; }

    .phx-corps { padding: 1.25rem; overflow-y: auto; }

    .phx-chemins { display: grid; grid-template-columns: repeat(3, 1fr); gap: .7rem; }
    .phx-chemin {
        display: flex; flex-direction: column; align-items: center; gap: .35rem;
        padding: 1.1rem .6rem; border: 1px solid #e2e8f0; border-radius: 12px;
        background: #fff; cursor: pointer; text-align: center; font-family: inherit;
        transition: border-color .15s, box-shadow .15s;
    }
    .phx-chemin:hover { border-color: #0453cb; box-shadow: 0 6px 20px rgba(4,83,203,.10); }
    .phx-chemin-icone {
        width: 42px; height: 42px; border-radius: 11px; color: #fff; font-size: 1rem;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
    }
    .phx-chemin-titre { font-size: .86rem; font-weight: 700; color: #1e293b; }
    .phx-chemin-aide { font-size: .7rem; color: #64748b; line-height: 1.35; }

    .phx-scene {
        width: 100%; height: min(46vh, 320px); border-radius: 12px;
        background: #0f172a; overflow: hidden; position: relative;
        display: flex; align-items: center; justify-content: center;
    }
    .phx-scene video, .phx-scene img {
        position: absolute; inset: 0;
        width: 100%; height: 100%; max-width: 100%; max-height: 100%;
        object-fit: contain;
    }
    .phx-cadre-retirer {
        position: absolute; top: -4px; right: -4px; z-index: 4;
        width: 24px; height: 24px; border-radius: 50%; padding: 0;
        background: #fff; color: #0453cb; border: 2px solid #0453cb;
        display: flex; align-items: center; justify-content: center;
        font-size: .65rem; cursor: pointer; box-shadow: 0 2px 8px rgba(15,23,42,.18);
    }
    .phx-cadre-retirer:hover { background: #0453cb; color: #fff; }

    .phx-actuelle {
        width: 130px; height: 160px; margin: 0 auto; border-radius: 12px;
        overflow: hidden; background: #f1f5f9; border: 1px solid #e2e8f0;
    }
    .phx-actuelle img { width: 100%; height: 100%; object-fit: cover; }

    .phx-texte { font-size: .88rem; color: #475569; text-align: center; margin: .9rem 0 0; line-height: 1.55; }
    .phx-texte--ok { color: #047857; font-weight: 600; }

    .phx-erreur {
        margin-top: .8rem; font-size: .82rem; line-height: 1.5;
        background: rgba(220,38,38,.08); color: #b91c1c;
        border-radius: 9px; padding: .6rem .75rem;
    }

    .phx-qr-zone { text-align: center; }
    .phx-qr {
        width: 210px; height: 210px; margin: 0 auto; padding: .6rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .phx-qr img { width: 100%; height: 100%; }
    .phx-qr-absent { font-size: .78rem; color: #64748b; padding: 1rem; line-height: 1.5; }
    .phx-lien {
        margin-top: .7rem; font-size: .7rem; color: #475569; word-break: break-all;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: .5rem;
    }
    .phx-minuteur { margin-top: .8rem; font-size: .76rem; color: #64748b; }

    .phx-pied {
        display: flex; justify-content: flex-end; gap: .5rem;
        margin-top: 1.1rem; flex-wrap: wrap;
    }
    .phx-btn {
        padding: .5rem 1rem; border-radius: 9px; font-size: .82rem; font-weight: 600;
        border: 1px solid #e2e8f0; background: #fff; color: #475569;
        cursor: pointer; font-family: inherit;
    }
    .phx-btn:hover:not(:disabled) { border-color: #cbd5e1; }
    .phx-btn--primaire { background: #0453cb; border-color: #0453cb; color: #fff; }
    .phx-btn:disabled { opacity: .55; cursor: wait; }

    [x-cloak] { display: none !important; }

    @@media (max-width: 520px) {
        .phx-chemins { grid-template-columns: 1fr; }
        .phx-chemin { flex-direction: row; justify-content: flex-start; text-align: left; gap: .7rem; }
    }
</style>
@endpush

@push('scripts')
<script>
if (typeof window.photoEtudiant !== 'function') {
    window.photoEtudiant = function () {
        return {
            ouvert: false,
            ecran: 'choix',
            nom: '',
            photoActuelle: null,
            aDejaUnePhoto: false,
            confirmer: false,
            telephoneActif: true,
            peutEditer: false,
            urls: {},

            apercu: null,
            fichier: null,
            erreur: '',
            occupe: false,

            flux: null,
            cameraPrete: false,
            clichePris: false,
            cliche: null,

            captureId: null,
            captureRecue: false,
            qr: null,
            url: '',
            minuteur: '',
            sondage: null,
            compte: null,

            init() {
                const charge = JSON.parse(this.$root.dataset.phx || '{}');
                this.nom = charge.nom || '';
                this.photoActuelle = charge.photoActuelle || null;
                this.aDejaUnePhoto = !!charge.aDejaUnePhoto;
                this.confirmer = !!charge.confirmer;
                this.telephoneActif = !!charge.telephoneActif;
                this.peutEditer = !!charge.peutEditer;
                this.urls = charge.urls || {};

                // Le dialogue s'expose pour que n'importe quel bouton de la page
                // puisse l'ouvrir, sans que chacun ait a connaitre son etat.
                window.addEventListener('photo-etudiant:ouvrir', () => this.ouvrir());

                if (charge.ouvrirAuChargement) {
                    this.$nextTick(() => this.ouvrir());
                }
                this.$nextTick(() => this.poserActionsCadre());
            },

            ouvrir() {
                this.erreur = '';
                this.ecran = this.confirmer ? 'confirmer' : 'choix';
                this.ouvert = true;
            },

            fermer() {
                this.arreterCamera();
                this.arreterSondage();
                this.ouvert = false;
                this.erreur = '';
            },

            retourAuChoix() {
                this.arreterCamera();
                this.arreterSondage();
                this.apercu = null;
                this.fichier = null;
                this.cliche = null;
                this.clichePris = false;
                this.captureRecue = false;
                this.erreur = '';
                this.ecran = 'choix';
            },

            entetes() {
                return { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            },

            async lire(reponse) {
                const donnees = await reponse.json().catch(() => ({}));
                if (!reponse.ok) {
                    const erreurs = donnees.errors ? Object.values(donnees.errors)[0][0] : null;
                    throw new Error(erreurs || donnees.message || 'Erreur ' + reponse.status);
                }
                return donnees;
            },

            /* ---------- Téléverser ---------- */

            choisirFichier() {
                this.$refs.fichier.click();
            },

            fichierChoisi(evenement) {
                const fichier = evenement.target.files && evenement.target.files[0];
                if (!fichier) { return; }
                if (this.apercu) { URL.revokeObjectURL(this.apercu); }
                this.fichier = fichier;
                this.apercu = URL.createObjectURL(fichier);
                this.erreur = '';
                this.ecran = 'fichier';
            },

            enregistrerFichier() {
                if (!this.fichier) { return; }
                return this.televerser(this.fichier);
            },

            async televerser(donnee) {
                this.occupe = true;
                this.erreur = '';
                try {
                    const corps = new FormData();
                    corps.append('photo', donnee, donnee.name || 'photo.jpg');
                    const donnees = await this.lire(await fetch(this.urls.televerser, {
                        method: 'POST', headers: this.entetes(), body: corps,
                    }));
                    this.photoActuelle = donnees.photo_url;
                    this.apresEnregistrement();
                } catch (e) {
                    this.erreur = e.message;
                } finally {
                    this.occupe = false;
                }
            },

            apresEnregistrement() {
                this.arreterCamera();
                this.arreterSondage();
                this.aDejaUnePhoto = true;
                this.ecran = 'fini';
                // Les vignettes de la page se mettent a jour sans rechargement.
                document.querySelectorAll('[data-photo-etudiant]').forEach((noeud) => {
                    if (noeud.tagName === 'IMG') { noeud.src = this.photoActuelle; }
                });

                // Et le cadre qui n'affichait que des initiales — le cas de
                // toute premiere photo — recoit son image. Sans cela, l'avatar
                // resterait aux initiales jusqu'au prochain rechargement, et
                // l'utilisateur croirait que rien ne s'est passe.
                document.querySelectorAll('[data-photo-etudiant-cadre]').forEach((cadre) => {
                    if (cadre.querySelector('[data-photo-etudiant]')) { return; }
                    const image = document.createElement('img');
                    image.src = this.photoActuelle;
                    image.alt = 'Photo';
                    image.setAttribute('data-photo-etudiant', '');
                    image.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
                    const bouton = cadre.querySelector('button');
                    cadre.innerHTML = '';
                    cadre.appendChild(image);
                    if (bouton) { cadre.appendChild(bouton); }
                });
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Photo enregistrée.' } }));
                this.poserActionsCadre();
            },

            poserActionsCadre() {
                if (!this.peutEditer) { return; }
                document.querySelectorAll('[data-photo-etudiant-cadre]').forEach((cadre) => {
                    const deja = cadre.querySelector('[data-phx-retirer]');
                    const aUnePhoto = !!cadre.querySelector('[data-photo-etudiant]');
                    if (!aUnePhoto) {
                        if (deja) { deja.remove(); }
                        return;
                    }
                    if (deja) { return; }
                    const style = window.getComputedStyle(cadre);
                    if (style.position === 'static') { cadre.style.position = 'relative'; }
                    const retirer = document.createElement('button');
                    retirer.type = 'button';
                    retirer.setAttribute('data-phx-retirer', '');
                    retirer.className = 'phx-cadre-retirer';
                    retirer.title = 'Retirer la photo';
                    retirer.setAttribute('aria-label', 'Retirer la photo');
                    retirer.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    retirer.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.demanderSuppression();
                    });
                    cadre.appendChild(retirer);
                });
            },

            async demanderSuppression() {
                if (!this.urls.supprimer) { return; }
                if (!window.confirm('Retirer la photo de cet étudiant ?')) { return; }
                this.occupe = true;
                this.erreur = '';
                try {
                    await this.lire(await fetch(this.urls.supprimer, {
                        method: 'POST', headers: this.entetes(),
                    }));
                    this.photoActuelle = null;
                    this.aDejaUnePhoto = false;
                    this.confirmer = false;
                    document.querySelectorAll('[data-photo-etudiant]').forEach((noeud) => noeud.remove());
                    this.poserActionsCadre();
                    this.fermer();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Photo retirée.' } }));
                } catch (e) {
                    this.erreur = e.message;
                    this.ouvert = true;
                    this.ecran = 'choix';
                } finally {
                    this.occupe = false;
                }
            },

            /* ---------- Caméra du poste ---------- */

            async ouvrirCamera() {
                this.erreur = '';
                this.clichePris = false;
                this.cameraPrete = false;
                this.ecran = 'camera';

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    this.erreur = 'Ce navigateur ne donne pas accès à la caméra. Utilisez le téléphone ou un fichier.';
                    return;
                }

                try {
                    this.flux = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                        audio: false,
                    });
                    this.$nextTick(() => {
                        this.$refs.video.srcObject = this.flux;
                        this.cameraPrete = true;
                    });
                } catch (e) {
                    this.erreur = 'La caméra n’est pas accessible : ' + (e.message || 'accès refusé') + '.';
                }
            },

            prendreCliche() {
                const video = this.$refs.video;
                const toile = this.$refs.toile;
                toile.width = video.videoWidth || 1280;
                toile.height = video.videoHeight || 960;
                toile.getContext('2d').drawImage(video, 0, 0, toile.width, toile.height);

                toile.toBlob((morceau) => {
                    if (!morceau) { this.erreur = 'Le cliché n’a pas pu être pris.'; return; }
                    this.cliche = new File([morceau], 'photo.jpg', { type: 'image/jpeg' });
                    if (this.apercu) { URL.revokeObjectURL(this.apercu); }
                    this.apercu = URL.createObjectURL(morceau);
                    this.clichePris = true;
                }, 'image/jpeg', 0.92);
            },

            reprendreCliche() {
                this.clichePris = false;
                this.cliche = null;
            },

            enregistrerCliche() {
                if (!this.cliche) { return; }
                return this.televerser(this.cliche);
            },

            arreterCamera() {
                if (this.flux) {
                    // Sans cela, le temoin de la camera reste allume apres la
                    // fermeture du dialogue — et sur un poste partage, personne
                    // ne comprend pourquoi.
                    this.flux.getTracks().forEach((piste) => piste.stop());
                    this.flux = null;
                }
                this.cameraPrete = false;
            },

            /* ---------- Téléphone ---------- */

            async ouvrirTelephone() {
                this.erreur = '';
                this.captureRecue = false;
                this.qr = null;
                this.ecran = 'telephone';
                this.occupe = true;

                try {
                    const donnees = await this.lire(await fetch(this.urls.ouvrirCapture, {
                        method: 'POST', headers: this.entetes(),
                    }));
                    this.captureId = donnees.capture.id;
                    this.qr = donnees.qr;
                    this.url = donnees.url;
                    this.lancerSondage(donnees.capture.expire_at);
                } catch (e) {
                    this.erreur = e.message;
                } finally {
                    this.occupe = false;
                }
            },

            lancerSondage(expireAt) {
                this.arreterSondage();
                const fin = new Date(expireAt).getTime();

                // Sondage plutot qu'un canal temps reel : KLASSCI n'en a aucun,
                // et en ouvrir un pour cette seule fonctionnalite couterait un
                // service de plus a exploiter sur un hebergement mutualise.
                this.sondage = setInterval(() => this.sonder(), 2500);

                this.compte = setInterval(() => {
                    const reste = Math.max(0, Math.round((fin - Date.now()) / 1000));
                    if (reste <= 0) {
                        this.minuteur = 'Ce code a expiré. Affichez-en un nouveau.';
                        this.arreterSondage();
                        return;
                    }
                    const m = Math.floor(reste / 60);
                    const s = reste % 60;
                    this.minuteur = 'Ce code expire dans ' + m + ' min ' + (s < 10 ? '0' : '') + s + ' s';
                }, 1000);
            },

            arreterSondage() {
                if (this.sondage) { clearInterval(this.sondage); this.sondage = null; }
                if (this.compte) { clearInterval(this.compte); this.compte = null; }
            },

            async sonder() {
                if (!this.captureId) { return; }
                try {
                    const donnees = await this.lire(await fetch(this.urls.capture + '/' + this.captureId, {
                        headers: this.entetes(),
                    }));
                    if (donnees.capture.attend_decision) {
                        this.captureRecue = true;
                        this.apercu = donnees.capture.apercu;
                        this.arreterSondage();
                    }
                } catch (e) {
                    // Un sondage qui echoue ne doit rien casser : on retentera
                    // dans deux secondes et demie.
                }
            },

            async accepterCapture() {
                this.occupe = true;
                this.erreur = '';
                try {
                    const donnees = await this.lire(await fetch(this.urls.capture + '/' + this.captureId + '/accepter', {
                        method: 'POST', headers: this.entetes(),
                    }));
                    this.photoActuelle = donnees.photo_url;
                    this.apresEnregistrement();
                } catch (e) {
                    this.erreur = e.message;
                } finally {
                    this.occupe = false;
                }
            },

            async refaireCapture() {
                this.occupe = true;
                try {
                    await fetch(this.urls.capture + '/' + this.captureId + '/refuser', {
                        method: 'POST', headers: this.entetes(),
                    });
                } catch (e) {
                    // Meme si le refus n'aboutit pas, on affiche un nouveau code :
                    // l'ancien sera de toute facon abandonne cote serveur.
                } finally {
                    this.occupe = false;
                }
                return this.ouvrirTelephone();
            },

            destroy() {
                this.arreterCamera();
                this.arreterSondage();
            },
        };
    };
}
</script>
@endpush
@endonce
