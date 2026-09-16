{{--
    La page que voit le téléphone après avoir scanné le code QR.

    Autonome à dessein : ni layout, ni Bootstrap, ni Alpine. Elle s'ouvre sur un
    téléphone quelconque, souvent en 3G, sur le trottoir devant l'école. Tout ce
    qui n'est pas indispensable est du temps d'attente devant un guichet.

    Elle ne montre qu'un nom. Ni matricule, ni classe, ni dossier : le lien est
    public, et il ne doit rien apprendre à qui le trouverait.
--}}
@php $_urlEnvoi = route('photo-capture.envoyer', ['jeton' => $jeton]); @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    {{-- La page est publique mais elle est servie par Laravel, donc elle a une
         session : autant garder la protection CSRF plutot que de l'exclure. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Photo — {{ config('app.name', 'KLASSCI') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(160deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
            color: #fff; display: flex; flex-direction: column; align-items: center;
            padding: 1.5rem 1rem 2.5rem;
        }
        .marque { font-size: .72rem; letter-spacing: 2px; text-transform: uppercase; opacity: .65; }
        h1 { font-size: 1.35rem; font-weight: 700; margin: .6rem 0 .2rem; text-align: center; }
        .qui { font-size: 1rem; opacity: .8; text-align: center; margin-bottom: 1.4rem; }

        .carte {
            background: #fff; color: #1e293b; border-radius: 18px;
            width: 100%; max-width: 460px; padding: 1.25rem;
            box-shadow: 0 18px 50px rgba(4,15,40,.28);
        }

        .cible {
            width: 100%; aspect-ratio: 3 / 4; border-radius: 14px;
            background: #f1f5f9; border: 2px dashed #cbd5e1;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: .5rem; color: #64748b; overflow: hidden; position: relative;
            max-height: 70vh; min-height: 220px;
        }
        .cible img {
            position: absolute; inset: 0;
            width: 100%; height: 100%; max-width: 100%; max-height: 100%;
            object-fit: cover; display: block;
        }
        .cible-texte { font-size: .85rem; padding: 0 1.5rem; text-align: center; line-height: 1.5; }
        .cible-icone { font-size: 2.4rem; line-height: 1; }

        .bouton {
            display: block; width: 100%; text-align: center; cursor: pointer;
            border: none; border-radius: 12px; padding: .9rem 1rem;
            font-size: 1rem; font-weight: 700; font-family: inherit;
            margin-top: .9rem;
        }
        .bouton--principal { background: #0453cb; color: #fff; }
        .bouton--secondaire { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .bouton:disabled { opacity: .55; }

        input[type="file"] { display: none; }

        .message { margin-top: .9rem; font-size: .88rem; border-radius: 10px; padding: .7rem .85rem; display: none; line-height: 1.5; }
        .message--ok { background: rgba(16,185,129,.12); color: #047857; display: block; }
        .message--ko { background: rgba(220,38,38,.10); color: #b91c1c; display: block; }

        .fini { text-align: center; padding: 1rem .5rem; }
        .fini-coche { font-size: 3rem; }
        .fini h2 { font-size: 1.1rem; margin: .6rem 0 .3rem; color: #047857; }
        .fini p { font-size: .88rem; color: #64748b; margin: 0; line-height: 1.5; }

        .minuteur { margin-top: 1.1rem; font-size: .78rem; opacity: .7; text-align: center; }

        @@media (max-width: 380px) {
            h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="marque">{{ config('app.name', 'KLASSCI') }}</div>
    <h1>Photo d'identité</h1>
    <div class="qui">{{ $nom !== '' ? $nom : 'Étudiant' }}</div>

    <div class="carte">
        <div id="etape-prise">
            <label class="cible" for="fichier" id="cible">
                <span class="cible-icone" id="cible-icone">&#128247;</span>
                <span class="cible-texte" id="cible-texte">
                    Touchez ici pour prendre la photo.<br>
                    Visage de face, fond clair, sans lunettes de soleil.
                </span>
            </label>

            {{-- `capture="user"` ouvre directement la camera frontale sur un
                 telephone. Sur un ordinateur, le meme champ ouvre le selecteur
                 de fichiers : la page reste utilisable partout. --}}
            <input type="file" id="fichier" accept="image/*" capture="user">

            <button type="button" class="bouton bouton--principal" id="envoyer" disabled>Envoyer la photo</button>
            <label class="bouton bouton--secondaire" for="fichier" id="refaire" style="display:none;">Reprendre</label>

            <div class="message" id="message"></div>
        </div>

        <div class="fini" id="etape-fini" style="display:none;">
            <div class="fini-coche">&#9989;</div>
            <h2>Photo envoyée</h2>
            <p>Le guichet la regarde. Si elle ne convient pas, on vous redemandera un nouveau code.</p>
        </div>
    </div>

    <div class="minuteur" id="minuteur"></div>

<script>
(function () {
    var champ = document.getElementById('fichier');
    var cible = document.getElementById('cible');
    var icone = document.getElementById('cible-icone');
    var texte = document.getElementById('cible-texte');
    var envoyer = document.getElementById('envoyer');
    var refaire = document.getElementById('refaire');
    var message = document.getElementById('message');
    var minuteur = document.getElementById('minuteur');
    var etapePrise = document.getElementById('etape-prise');
    var etapeFini = document.getElementById('etape-fini');
    var apercu = null;

    function dire(texteDuMessage, ok) {
        message.textContent = texteDuMessage;
        message.className = 'message ' + (ok ? 'message--ok' : 'message--ko');
    }

    champ.addEventListener('change', function () {
        var fichier = champ.files && champ.files[0];
        if (!fichier) { return; }

        // L'apercu est libere avant d'en creer un autre : sur un telephone, une
        // dizaine d'essais successifs sans cela finissent par saturer la memoire
        // de l'onglet, et la page se recharge toute seule.
        if (apercu) { URL.revokeObjectURL(apercu); }
        apercu = URL.createObjectURL(fichier);

        cible.innerHTML = '';
        var img = document.createElement('img');
        img.src = apercu;
        img.alt = 'Aperçu de la photo';
        img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:cover;display:block;';
        cible.appendChild(img);

        envoyer.disabled = false;
        refaire.style.display = 'block';
        message.className = 'message';
    });

    envoyer.addEventListener('click', function () {
        var fichier = champ.files && champ.files[0];
        if (!fichier) { return; }

        envoyer.disabled = true;
        envoyer.textContent = 'Envoi…';

        var corps = new FormData();
        corps.append('photo', fichier);

        fetch(@json($_urlEnvoi), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: corps
        }).then(function (reponse) {
            return reponse.json().catch(function () { return {}; }).then(function (donnees) {
                return { ok: reponse.ok, donnees: donnees };
            });
        }).then(function (resultat) {
            if (!resultat.ok) {
                var erreurs = resultat.donnees.errors;
                var premiere = erreurs ? Object.values(erreurs)[0][0] : null;
                throw new Error(premiere || resultat.donnees.message || 'Envoi impossible.');
            }
            etapePrise.style.display = 'none';
            etapeFini.style.display = 'block';
            minuteur.textContent = '';
        }).catch(function (erreur) {
            dire(erreur.message, false);
            envoyer.disabled = false;
            envoyer.textContent = 'Envoyer la photo';
        });
    });

    var reste = {{ (int) $expireDans }};
    function battre() {
        if (etapeFini.style.display === 'block') { return; }
        if (reste <= 0) {
            minuteur.textContent = 'Ce lien a expiré. Demandez-en un nouveau au guichet.';
            envoyer.disabled = true;
            return;
        }
        var m = Math.floor(reste / 60);
        var s = reste % 60;
        minuteur.textContent = 'Ce lien expire dans ' + m + ' min ' + (s < 10 ? '0' : '') + s + ' s';
        reste--;
        setTimeout(battre, 1000);
    }
    battre();
})();
</script>
</body>
</html>
