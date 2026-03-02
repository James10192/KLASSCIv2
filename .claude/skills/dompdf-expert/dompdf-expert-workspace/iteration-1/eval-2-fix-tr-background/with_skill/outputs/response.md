# Fix: background-color sur <tr> ne s'affiche pas dans DomPDF

## Cause racine

DomPDF (barryvdh/laravel-dompdf v2.0) ne rend pas `background-color` quand la propriété cible un élément `<tr>` — que ce soit via une classe ou en style inline. Le background doit toujours être appliqué aux enfants `<td>`.

## Ce qui était incorrect

Trois règles CSS ciblaient des éléments `<tr>` :

| Règle | Pourquoi ça échouait |
|-------|---------------------|
| `.section-header { background: ... }` | Appliqué sur `<tr class="section-header">` — DomPDF ignore background sur `<tr>` |
| `.subject-row:nth-child(even) { background: #f8fafb }` | Classe sur `<tr>` — non rendu |
| `.summary-row { background: #e5e7eb }` | Classe sur `<tr>` — non rendu |

## Le fix (pattern correct pour DomPDF)

```css
/* AVANT — background sur <tr>, ne s'affiche pas */
.section-header {
    background: {{ $pdfPrimary }};
    color: {{ $pdfHeaderText }};
}
.subject-row:nth-child(even) { background: #f8fafb; }
.summary-row {
    background: #e5e7eb;
    font-weight: 700;
}

/* APRÈS — background sur td, fonctionne dans DomPDF */
.section-header td {
    background-color: {{ $pdfPrimary }};
    color: {{ $pdfHeaderText }};
}
.subject-row:nth-child(even) td { background-color: #f8fafb; }
.summary-row td {
    background-color: #e5e7eb;
    font-weight: 700;
}
```

La correction est minimaliste : ajouter `td` à chaque sélecteur. Les éléments HTML `<tr>` ne nécessitent aucun changement.

## Pattern général

```css
/* ❌ WRONG — background ignoré par DomPDF */
.header-row { background-color: #0453cb; }

/* ✅ CORRECT — toujours cibler td */
.header-row td { background-color: #0453cb; }

/* ✅ AUSSI CORRECT — style inline directement sur td */
/* <td style="background-color: #0453cb;"> */
```

Ce pattern s'applique à tous les cas de coloration de lignes : headers de sections, zebra striping, lignes de totaux, nth-child alternants.
