# Mise en service — chiffrement des sauvegardes, double authentification, chiffres de terrain

> **Pourquoi ce document.** Quatre lots de travail sont livrés et fusionnés, mais
> leur valeur est nulle jusqu'à ce que quelques gestes soient posés sur les
> serveurs. Ces gestes sont écrits dans quatre descriptions de pull request qui
> vont défiler et disparaître. Ils sont ici, une fois, en entier.
>
> Rien dans ce document ne peut être fait depuis un poste de développement :
> tout se passe dans le terminal cPanel de chaque instance, ou dans son fichier
> `.env`.

Livré par : adminKlassci #97, KLASSCIv2 #924, KLASSCIv2 #928, klassci-landing #17.

---

## 1. adminKlassci — chiffrer les sauvegardes et les sortir du serveur

Aujourd'hui les sauvegardes nocturnes sont **en clair, sur le serveur qu'elles
sauvegardent**. Un accès au serveur donne l'état civil de plus de quatre mille
élèves. C'est le point le plus urgent de cette liste.

### 1.1 Fabriquer la clé

À faire **sur votre poste**, pas sur le serveur :

```bash
openssl rand -base64 48
```

Trente-deux caractères au minimum, sinon la commande refuse de chiffrer. Cette
sortie en fait davantage.

### 1.2 Où elle ne doit pas être

**La clé ne doit pas vivre uniquement sur le serveur qu'elle protège.** Une
sauvegarde chiffrée dont la clé est dans le `.env` du même serveur protège
contre le vol de l'archive, pas contre l'accès au serveur — et c'est justement le
scénario contre lequel on chiffre.

Rangez-la ailleurs : un gestionnaire de mots de passe, ou un papier dans un
coffre. Une sauvegarde qu'on ne peut plus déchiffrer n'est pas une sauvegarde.

### 1.3 Poser les réglages

Dans `/home/c2569688c/public_html/admin/.env` :

```env
SAUVEGARDE_CLE=<la sortie de openssl ci-dessus>
SAUVEGARDE_DISQUE_HORS_SITE=<nom d'un disque de config/filesystems.php>
```

`SAUVEGARDE_DISQUE_HORS_SITE` vide, les archives restent sur place : elles sont
chiffrées, mais elles brûlent avec le serveur. Un disque S3, Backblaze ou
équivalent déclaré dans `config/filesystems.php` les met à l'abri.

### 1.4 Appliquer et vérifier

```bash
cd /home/c2569688c/public_html/admin
git pull origin main
php artisan migrate --force
php artisan config:clear

# Une sauvegarde de contrôle, sur une petite instance
php artisan tenant:backup presentation --type=database_only
```

L'archive produite doit se terminer par **`.enc`**. Un fichier sans ce suffixe
signifie que `SAUVEGARDE_CLE` n'est pas lue — reprenez le `config:clear`.

### 1.5 La vérification hebdomadaire

`tenant:verifier-restauration` est planifiée le dimanche à 05h00. Elle restaure
une sauvegarde dans une base d'essai, compte les tables, puis la supprime. Une
sauvegarde qu'on n'a jamais restaurée n'est pas une sauvegarde : c'est un
fichier.

Pour ne pas attendre dimanche :

```bash
php artisan tenant:verifier-restauration presentation
```

Le résultat s'enregistre dans `verifications_restauration`. Une vérification en
échec est le seul signal qui vaut : le reste n'est que du volume d'archive.

---

## 2. Chaque instance KLASSCI — double authentification

**Activer ce réglage n'enferme personne dehors.** Tant qu'une personne n'a pas
confirmé son second facteur depuis son propre téléphone, elle se connecte
exactement comme avant. C'était la condition pour pouvoir le déployer sur une
école en pleine rentrée.

### 2.1 Déployer

Pour chaque instance (`presentation`, `esbtp-abidjan`, `esbtp-yakro`, `ephrata`,
`hetec`, `rostan`, `usat`) :

```bash
klassci pull <instance>
klassci cache:clear <instance>     # toujours : le pull ne purge ni les vues ni l'opcache
klassci migrate <instance>
```

La migration ajoute les colonnes du second facteur à `users`. Sans elle, la page
« Double authentification » du menu de compte tombe en erreur.

### 2.2 Choisir les fonctions concernées

Dans le `.env` de l'instance, rien pour l'ouvrir à tout le monde, ou :

```env
SECURITE_DOUBLE_AUTH_ROLES=superAdmin,comptable
```

Vide, aucune fonction n'est tenue d'ajouter un second facteur — chacun peut
toujours l'activer de son propre chef depuis son compte.

Le bon usage : protéger la direction et la comptabilité **sans** imposer un
téléphone à deux mille étudiants, dont beaucoup se connectent depuis un appareil
partagé.

### 2.3 Vérifier

Ouvrez `/securite/double-authentification` avec un compte concerné. Vous devez
voir un code à scanner, puis huit codes de secours à imprimer. Imprimez-les :
c'est ce qui reste le jour où le téléphone se perd.

---

## 3. Chaque instance KLASSCI — relever les chiffres de terrain

Sept articles du site annoncent des chiffres qui ne sont pas encore mesurés. Ils
sont en commentaire, invisibles pour les visiteurs, en attendant d'être vrais.

Sur chaque instance, une fois les instances à jour :

```bash
cd ~/public_html/<instance>
php artisan vitrine:donnees-terrain
```

En lecture seule, sans jeton, sans migration. Pour transmettre :

```bash
php artisan vitrine:donnees-terrain --json > terrain-<instance>.json
```

⚠️ **Attention au dossier de ROSTAN** : c'est `~/public_html/islg-rostan`, pas
`~/public_html/rostan`.

La commande termine par « Ce que cette instance ne peut pas dire » : neuf des
dix-neuf chiffres ne sont dans aucune table — une durée de migration, un nombre
d'allers-retours avec la DEEP, un taux de consommation de jours-développement.
Ceux-là ne sortiront pas d'une base ; ils sont dans l'expérience de l'équipe.

---

## 4. Deux réglages à corriger au passage

| Instance | Constat | Geste |
|---|---|---|
| `usat` | `TENANT_CODE` ne vaut pas `usat` dans son `.env` | Corriger, puis `php artisan config:clear` |
| `ephrata` | L'établissement n'a pas renseigné son nom dans `/esbtp/settings` | Le renseigner : ce nom coiffe ses bulletins **et** sa page d'inscription en ligne |

Le second n'est pas cosmétique : la page d'inscription publique lit ce réglage.
Une école sans nom réglé s'y présente sans nom, à des familles qui y saisissent
leur état civil.

---

## 5. Ce que ce document ne couvre pas

- Le contenu des pages **Mentions légales**, **Confidentialité** et **À propos** :
  raison sociale, siège, registre du commerce, délégué à la protection des
  données. Personne d'autre que vous ne peut les écrire.
- Les trois points encore ouverts de la page **Sécurité** : ils y figurent
  explicitement comme à compléter, plutôt que comblés par une affirmation.
- L'hébergement : la page Sécurité dit où vivent les données parce que la
  question a été posée au registre régional, pas parce qu'on l'a supposé. Si
  l'hébergeur change, cette page change.

---

## 6. Dans quel ordre

1. §1 — le chiffrement des sauvegardes. C'est le seul point de cette liste où
   l'attente a un coût qui augmente chaque nuit.
2. §4 — les deux réglages, une minute chacun.
3. §2 — la double authentification, instance par instance.
4. §3 — le relevé des chiffres, quand tout le reste est en place.

Voir aussi : [`.claude/rules/adminklassci-tenant-management.md`](../../.claude/rules/adminklassci-tenant-management.md) pour les commandes de déploiement multi-instance, et [`docs/VERSIONING.md`](../VERSIONING.md) pour les notes de version.
