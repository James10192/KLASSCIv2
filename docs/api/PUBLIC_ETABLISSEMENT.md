# 📚 API - Identité publique de l'établissement

## Vue d'ensemble

Deux points d'entrée **non authentifiés** par lesquels une instance KLASSCI dit
qui elle est : son nom, son sigle, sa ville, son logo et son identité visuelle.

Ils existent pour le site vitrine `klassci.com`, qui en fait deux usages :

1. **La page d'accueil** fait défiler les logos des établissements clients.
2. **L'inscription en ligne** (`/inscription/universite/{ecole}`) affiche le logo
   de l'école choisie et habille son formulaire aux couleurs que l'école a
   réglées pour ses documents PDF.

L'école ne configure donc son identité **qu'une seule fois**, dans
`/esbtp/settings` — elle vaut pour ses bulletins comme pour sa page publique.

## Authentification

**Aucune.** C'est délibéré, et c'est la seule surface publique de KLASSCI dans ce
cas.

Les deux autres — réinscription et candidature — parlent d'un étudiant : elles
sont signées en HMAC-SHA256, limitées en débit sur des valeurs signées, et
fermées hors saison. Celle-ci ne parle que de l'établissement, et ne sert rien
qu'il n'imprime déjà en en-tête de chaque bulletin qu'il distribue. Exiger une
signature obligerait le site vitrine à en produire une pour afficher un logo,
sans rien protéger de plus.

Le débit reste borné par `throttle:api` (60 req/min/IP), non pour garder un
secret mais pour empêcher qu'on se serve du point d'entrée comme hébergeur
d'images.

## Endpoints

### Identité

**GET** `/api/public/etablissement`

Aucun paramètre.

```json
{
  "code": "esbtp-yakro",
  "nom": "École Supérieure du Bâtiment et des Travaux Publics",
  "sigle": "ESBTP",
  "ville": "Yamoussoukro",
  "pays": "Côte d'Ivoire",
  "site_web": "https://esbtp.ci",
  "logo": {
    "present": true,
    "url": "https://esbtp-yakro.klassci.com/api/public/etablissement/logo"
  },
  "identite_visuelle": {
    "couleur_principale": "#0453cb",
    "couleur_secondaire": "#64748b",
    "couleur_accent": "#f59e0b",
    "couleur_texte": "#1f2937",
    "bandeau_fond": "#0453cb",
    "bandeau_texte": "#ffffff",
    "entete": "Ministère de l'Enseignement Supérieur"
  }
}
```

```bash
curl https://esbtp-yakro.klassci.com/api/public/etablissement
```

### Logo

**GET** `/api/public/etablissement/logo`

Rend le fichier lui-même (`image/png`, `image/jpeg`, `image/gif`), avec
`Cache-Control: public, max-age=3600`, `X-Content-Type-Options: nosniff` et une
politique de sécurité de contenu qui neutralise tout script embarqué.

**404** quand l'établissement n'a pas configuré de logo. C'est volontaire : le
point d'entrée ne sert **jamais** la marque KLASSCI en remplacement, sans quoi le
site vitrine afficherait plusieurs fois la même image sous le titre « nos
établissements ». Le site affiche alors un monogramme au nom de l'école.

```bash
curl -I https://esbtp-yakro.klassci.com/api/public/etablissement/logo
```

## Réglages lus

| Champ rendu | Réglage KLASSCI | Page de configuration |
|---|---|---|
| `nom`, `sigle`, `ville`, `pays`, `site_web` | `school_name`, `school_acronym`, `school_city`, `school_country`, `school_website` | Paramètres → Établissement |
| `logo` | `school_logo` | Paramètres → Établissement |
| `couleur_principale` … `bandeau_fond` | `pdf_primary_color`, `pdf_secondary_color`, `pdf_accent_color`, `pdf_text_color`, `pdf_header_bg_color` | Paramètres → PDF |
| `bandeau_texte` | calculé par KLASSCI depuis `bandeau_fond` selon le contraste WCAG (≥ 4,5:1) | — |
| `entete` | `pdf_header_text` | Paramètres → PDF |

## Ce qui n'est jamais servi

Rien de nominatif, rien de financier, rien de contractuel : pas de téléphone, pas
d'adresse électronique, pas d'adresse postale, pas de nom de directeur, pas
d'offre, pas de quota, pas d'effectif. Ces informations existent dans les
réglages ou chez `adminKlassci` ; le site vitrine n'en a pas l'usage, et la
bonne mesure d'une surface publique est ce dont l'appelant a besoin, pas ce
qu'on a sous la main.

Verrouillé par
`tests/Feature/Vitrine/IdentiteEtablissementPubliqueTest.php`.

## Assainissement des couleurs

Les couleurs viennent de réglages libres et atterrissent dans un attribut
`style` du site vitrine. Seule une notation hexadécimale (`#abc` ou `#aabbcc`)
est servie ; toute autre valeur — y compris une tentative d'injection CSS —
retombe silencieusement sur le bleu KLASSCI `#0453cb`.

Le texte d'en-tête est réduit à une ligne (espaces normalisés) et tronqué à 120
caractères : au-delà, ce n'est plus une signature d'établissement mais un
paragraphe, et il casse la mise en page du portail.

## Codes d'erreur

| Code | Signification |
|---|---|
| `200` | Identité servie, ou fichier logo servi |
| `404` | Aucun logo configuré pour cet établissement |
| `429` | Débit dépassé (`throttle:api`, 60 req/min/IP) |

## Historique des modifications

- **02/09/2026** : création. Consommé par `klassci-landing` (page d'accueil,
  liste des établissements et formulaire d'inscription en ligne).
