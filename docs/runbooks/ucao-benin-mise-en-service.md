# Mise en service — `ucao-benin`, la première instance hors de Côte d'Ivoire

> **Pourquoi ce document.** Le code qui permet à une instance béninoise de
> fonctionner est livré. Il ne s'active pas tout seul : sans les gestes décrits
> ici, `ucao-benin` continue de se comporter comme une instance ivoirienne, et
> le fait **en silence** — c'est toute la difficulté de ce défaut, il ne produit
> aucune erreur.
>
> Rien ici ne se fait depuis un poste de développement : tout se passe dans
> l'écran des réglages de l'instance, dans son `.env`, ou dans le terminal cPanel.

Livré par : KLASSCIv2 branche `claude/ucao-uuc-audit-services-oi2uyn`,
adminKlassci même branche.

---

## 0. La seule question qui commande l'ordre des gestes

**`ucao-benin` a-t-elle déjà reçu une candidature, ou envoyé une relance ?**

Tant que la réponse est non, il n'y a rien à réparer : il suffit de poser les
réglages avant la première. Si la réponse est oui, faites quand même les
sections 1 et 2 d'abord — elles arrêtent l'hémorragie — puis la section 4.

Pour le savoir, dans le terminal cPanel de l'instance :

```sql
SELECT COUNT(*) FROM esbtp_candidatures;
```

---

## 1. Les deux réglages de téléphone — avant toute candidature

`/esbtp/settings`, onglet **Général**, sous le téléphone de l'établissement.

| Champ | Valeur pour le Bénin |
|---|---|
| Indicatif pays des numéros | `229` |
| Préfixes des numéros locaux | `01` |

**Les deux ensemble, dans le même enregistrement.** Poser l'indicatif seul est
refusé par l'écran depuis ce lot, et pour une raison concrète : `229` avec la
liste ivoirienne livrée (`01,02,03,05,06,07,08,09`) produit une école qui
accepte `0707123456` — un mobile parfaitement valide en Côte d'Ivoire, qui ne
désigne personne au Bénin. Le message part, n'arrive nulle part, et ne laisse
aucune trace.

Pourquoi `01` seul : l'ARCEP Bénin a préfixé `01` à **tous** les numéros du pays
le 30 novembre 2024, fixes compris, en passant de huit à dix chiffres.

### Vérifier que c'est pris

Sur la même page, après enregistrement, les deux champs doivent afficher
exactement ce que vous avez saisi. Si un avertissement orange apparaît sous l'un
d'eux (« Cette valeur n'est pas lisible… »), la saisie a été écartée et c'est
l'indicatif nommé dans l'avertissement qui s'applique réellement — corrigez
avant d'aller plus loin.

---

## 2. Le fuseau — gratuit maintenant, coûteux plus tard

Le Bénin est à **UTC+1**, sans heure d'été. Dans le `.env` de l'instance :

```env
APP_TIMEZONE=Africa/Porto-Novo
DB_TIMEZONE=+01:00
```

Puis `php artisan config:clear`.

**Les deux lignes, pas une seule.** Elles ne font pas la même chose :

- `APP_TIMEZONE` est le fuseau dans lequel Laravel écrit ses horodatages.
- `DB_TIMEZONE` est celui de la session MySQL. Sept colonnes de l'application
  sont renseignées par MySQL lui-même (`useCurrent()` dans leur migration, dont
  l'historique du parcours d'inscription). Sans cette ligne, elles prennent une
  heure de retard sur le `created_at` de la **même ligne** — un journal d'audit
  dont deux horodatages voisins divergent d'une heure ne se remarque pas, il se
  découvre le jour où on lui demande de prouver quelque chose.

Un décalage fixe plutôt que le nom du fuseau, parce que les tables de fuseaux de
MySQL ne sont pas chargées sur l'hébergement mutualisé : `SET time_zone =
'Africa/Porto-Novo'` y échoue. Exact tant que le pays n'a pas d'heure d'été, ce
qui est le cas.

### Pourquoi c'est urgent et pas seulement souhaitable

Laravel écrit les horodatages dans le fuseau de l'application. Le déplacer sur
une instance **qui a déjà des données** laisse derrière des lignes écrites dans
l'ancien, que les nouvelles ne rejoignent jamais. Le seul moment gratuit est
avant la première inscription. C'est aussi pourquoi `APP_TIMEZONE` n'est
volontairement pas dans les clés que le CLI sait modifier à distance : on ne
doit pas pouvoir déplacer le fuseau d'une instance vivante par inadvertance.

Les six instances ivoiriennes n'ont **rien** à changer : `Africa/Abidjan` vaut
UTC+0 sans heure d'été, et le défaut livré est `UTC`.

### Pour les instances créées après ce lot

`tenant:provision` pose les deux lignes lui-même :

```bash
php artisan tenant:provision --code=xxx --name="…" --timezone=Africa/Porto-Novo
```

La commande refuse un fuseau qui pratique l'heure d'été plutôt que de figer un
décalage juste la moitié de l'année.

---

## 3. Celtiis Cash

`app/Enums/ModePaiement.php` liste Wave, Djamo et Orange Money. **Celtiis Cash
manque.** `cash_counts.mode_paiement` est un `string(30)` et non un `ENUM` SQL :
l'ajout ne coûte ni `ALTER` ni verrou de table sur les huit instances.

Ne retirez pas les modes ivoiriens pour autant — l'énumération est partagée par
toutes les instances et lue par la réconciliation. Un mode inutilisé ne coûte
rien ; un mode manquant rend un encaissement invisible du rapprochement.

*Non livré dans ce lot.*

---

## 4. Si des candidatures ont déjà été déposées

### Ce que la requête peut dire, et ce qu'elle ne peut pas

`esbtp_candidatures.telephone` est le **seul** stockage canonique du dépôt
(index UNIQUE `unique_candidature_telephone_annee` sur `(telephone,
annee_universitaire_id)` — la détection de doublon en dépend).
`esbtp_etudiants.telephone` et `esbtp_parents.telephone` sont bruts : rien à
reprendre de ce côté.

**Aucune requête ne peut distinguer avec certitude un numéro corrompu d'un
numéro ivoirien légitime.** C'est exactement la non-inférence sur laquelle
repose tout ce lot : `+2250142345678` est *simultanément* le résultat de la
corruption d'un MTN Bénin `0142345678` **et** un Moov Côte d'Ivoire valide,
qu'une famille de la diaspora aurait pu déposer. Les deux plans font dix
chiffres et commencent par `01`.

Ce que la requête fait donc : **énumérer les candidats**. C'est l'école qui
tranche, dossier par dossier.

### Les recenser

```sql
SELECT
    id,
    telephone,
    annee_universitaire_id,
    created_at,
    CASE
        WHEN SUBSTRING(telephone, 5, 4) IN
             ('0140','0141','0142','0143','0150','0151','0152','0153')
        THEN 'JOIGNABLE EN CI — la relance est partie chez un tiers'
        ELSE 'serie non attribuee en CI — le message n''est arrive nulle part'
    END AS consequence
FROM esbtp_candidatures
WHERE telephone LIKE '+225%'
ORDER BY consequence, created_at;
```

Les huit séries nommées (`0140`-`0143`, `0150`-`0153`) sont attribuées **des
deux côtés de la frontière**. Pour celles-là, le numéro produit est un mobile
Moov ivoirien joignable appartenant à quelqu'un d'autre, et la relance lui est
partie avec le nom de l'étudiant et le montant dû. Ce sont les seules qui
appellent une démarche au-delà de la correction du dossier.

### Les corriger

Un par un, depuis la fiche de candidature, en resaisissant le numéro **avec son
indicatif** : `+229 01 42 34 56 78`. Depuis ce lot, cette écriture est crue et
conservée ; c'est elle qui était refusée auparavant, et c'est ce refus qui
poussait à saisir la forme nationale — donc à corrompre.

Ne faites **pas** de `UPDATE` en masse qui remplacerait `+225` par `+229` : il
écraserait les numéros ivoiriens légitimes, et l'index UNIQUE ferait échouer la
transaction entière au premier doublon créé.

---

## Ordre récapitulatif

1. Compter les candidatures existantes (section 0).
2. Poser les deux réglages de téléphone (section 1) — **avant la première
   candidature béninoise**.
3. Poser les deux lignes de fuseau (section 2) — **avant la première
   inscription**.
4. Recenser et corriger l'existant s'il y en a (section 4).
