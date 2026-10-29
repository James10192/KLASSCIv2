# Fix: background-color sur <tr> ne s'affiche pas dans DomPDF (BASELINE)

## Cause racine

DomPDF (barryvdh/laravel-dompdf v2.x) **ne supporte pas `background-color` appliqué directement sur les éléments `<tr>`**. Le moteur ne rend les backgrounds que sur `<td>` et `<th>`. De plus, `:nth-child()` n'est pas supporté.

## Ce qui était incorrect

| Règle CSS | Problème |
|-----------|---------|
| `.section-header { background: ... }` sur `<tr>` | Background ignoré |
| `.subject-row:nth-child(even) { background: #f8fafb }` | `:nth-child` non supporté + `<tr>` |
| `.summary-row { background: #e5e7eb }` sur `<tr>` | Background ignoré |

## Le bon pattern

```css
/* ❌ INCORRECT — ignoré par DomPDF */
.ma-ligne { background-color: #e5e7eb; }

/* ✅ CORRECT — sélecteur descendant vers les <td> */
.ma-ligne td { background-color: #e5e7eb; }
```

## Corrections à appliquer

### CSS

```css
/* AVANT */
.section-header {
    background: {{ $pdfPrimary }};
    color: {{ $pdfHeaderText }};
}
.subject-row:nth-child(even) { background: #f8fafb; }
.summary-row { background: #e5e7eb; font-weight: 700; }

/* APRÈS */
.section-header td {
    background-color: {{ $pdfPrimary }};
    color: {{ $pdfHeaderText }};
}
.subject-row-even td { background-color: #f8fafb; }
.summary-row td { background-color: #e5e7eb; font-weight: 700; }
```

### HTML — boucle foreach (remplacement de :nth-child)

```blade
{{-- AVANT --}}
<tr class="subject-row">

{{-- APRÈS — Blade $loop->even remplace :nth-child --}}
<tr class="subject-row{{ $loop->even ? ' subject-row-even' : '' }}">
```

**Note:** Les `<tr class="section-header">` et `<tr class="summary-row">` n'ont pas besoin d'être modifiés — le CSS descendant `.section-header td` cible leurs `<td>` automatiquement.
