# Bulletin ESBTP Yakro

Ce projet est une reproduction en HTML et Bootstrap du bulletin de notes de l'École Supérieure du Bâtiment et des Travaux Publics (ESBTP) de Yamoussoukro.

## Fonctionnalités

- Affichage des informations de l'étudiant
- Tableau des notes par unité d'enseignement
- Calcul des moyennes et des crédits
- Résumé des résultats et observations
- Impression du bulletin
- Design responsive

## Technologies utilisées

- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Font Awesome (pour les icônes)

## Comment utiliser

1. Clonez ce dépôt sur votre machine locale
2. Ouvrez le fichier `index.html` dans votre navigateur web
3. Pour personnaliser les informations de l'étudiant, modifiez les données dans le fichier `script.js`
4. Pour imprimer le bulletin, cliquez sur le bouton "Imprimer le bulletin" en haut de la page

## Personnalisation

### Informations de l'étudiant

Pour modifier les informations de l'étudiant, ouvrez le fichier `script.js` et modifiez l'objet `studentExample` :

```javascript
const studentExample = {
    name: 'KOUASSI Jean-Marc',
    birthDate: '15/05/2000',
    birthPlace: 'Abidjan',
    id: 'ESB2023-456',
    department: 'Génie Civil',
    level: 'Licence 2'
};
```

### Résumé et observations

Pour modifier le résumé et les observations, modifiez l'objet `summaryExample` dans le fichier `script.js` :

```javascript
const summaryExample = {
    average: '14.75/20',
    credits: '34/34',
    rank: '3ème/45',
    mention: 'Bien',
    decision: 'Admis(e)',
    observations: 'Excellent travail. L\'étudiant a fait preuve d\'assiduité et de sérieux tout au long du semestre.',
    issueDate: '15/02/2024'
};
```

### Notes et matières

Pour modifier les notes et les matières, vous devez éditer directement le tableau HTML dans le fichier `index.html`.

## Logos

Pour ajouter les logos de l'école, remplacez les fichiers `logo.png` par les logos réels de l'ESBTP.

## Licence

Ce projet est libre d'utilisation pour l'ESBTP Yamoussoukro.

## Auteur

Créé par [Votre Nom] pour l'ESBTP Yamoussoukro. 