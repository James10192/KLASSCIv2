#!/usr/bin/env node
//
// Controle de syntaxe JavaScript des blocs <script> inline des vues Blade.
//
// Appele par .githooks/pre-commit. Ni `view:cache`, ni `php -l`, ni les tests
// ne lisent le JavaScript d'une vue : une apostrophe qui ferme une chaine
// (`'Selectionnez la classe, l'annee…'`, PR #1257) tue TOUT le script de la
// page, et tout reste vert. Seul le navigateur le voit, chez l'utilisateur.
//
// Methode : pour chaque <script> sans `src` et de type JavaScript, on remplace
// ce que Blade injecte par une valeur JS neutre, puis on demande a V8 de
// l'analyser (meme analyseur que `node --check`, sans un processus par bloc).
//
//   {{-- … --}}              -> supprime
//   {!! … !!}                 -> null
//   {{ … }}                   -> __blade (un identifiant : `window.{{ $x }} = …`)
//   @json(…) / @js(…)         -> null    (parentheses comptees, pas de regex)
//   @php … @endphp            -> vide, ou qu'il s'ouvre dans la ligne
//   ligne @directive(…)       -> vide    (@if, @foreach, @can…, arguments multilignes)
//   @verbatim … @endverbatim  -> laisse tel quel, c'est du JS brut
//
// Les retours a la ligne avales par un remplacement sont rendus au saut de
// ligne suivant : le numero rendu est celui de la vue Blade (a une ligne pres
// quand l'erreur suit un remplacement multiligne sur la meme ligne logique).
//
// Un bloc qui ne peut pas etre verifie (Blade non referme, type="module") est
// signale sur stderr, sans faire echouer le commit : jamais saute en silence.
//
// Usage : node blade-js-check.js [--index] fichier…
//   --index : lit la version mise en index (git show :fichier), pas le disque.

'use strict';

const fs = require('fs');
const vm = require('vm');
const { execFileSync } = require('child_process');

const args = process.argv.slice(2);
const index = args[0] === '--index';
const fichiers = index ? args.slice(1) : args;

// Garde les retours a la ligne d'un texte remplace, pour ne pas decaler.
const lignes = (s) => s.replace(/[^\n]/g, '');

// Position juste apres la parenthese qui ferme celle ouverte en `debut`.
// Les chaines PHP sont sautees : `@json($x ?? ')')` ne ferme pas trop tot.
function finDesParentheses(s, debut) {
  let profondeur = 0;
  for (let i = debut; i < s.length; i++) {
    const c = s[i];
    if (c === "'" || c === '"') {
      for (i++; i < s.length && s[i] !== c; i++) if (s[i] === '\\') i++;
      continue;
    }
    if (c === '(') profondeur++;
    else if (c === ')' && --profondeur === 0) return i + 1;
  }
  return -1;
}

function neutraliser(js) {
  let sortie = '';
  let i = 0;
  let verbatim = false;
  // Retours a la ligne avales par un remplacement : ils ne peuvent pas etre
  // rendus sur place (un `{{ }}` multiligne vit souvent dans une chaine, qui
  // n'admet pas de saut de ligne). On les rend au prochain saut de ligne.
  let enAttente = '';
  const remplacer = (valeur, texte) => { sortie += valeur; enAttente += lignes(texte); };

  while (i < js.length) {
    const reste = js.slice(i, i + 16);

    if (js[i] === '\n') { sortie += '\n' + enAttente; enAttente = ''; i++; continue; }

    if (verbatim) {
      if (js.startsWith('@endverbatim', i)) { verbatim = false; i += 12; continue; }
      sortie += js[i++];
      continue;
    }

    // Echappements Blade : @{{ et @@ sortent litteralement sans l'arobase.
    if (reste.startsWith('@{{')) { sortie += '{{'; i += 3; continue; }
    if (reste.startsWith('@@')) { sortie += '@'; i += 2; continue; }

    if (reste.startsWith('{!!')) {
      const fin = js.indexOf('!!}', i + 3);
      if (fin < 0) return null;
      remplacer('null', js.slice(i, fin + 3));
      i = fin + 3;
      continue;
    }
    // Un identifiant plutot qu'un nombre : `window.{{ $nom }} = …` est courant.
    if (reste.startsWith('{{')) {
      const fin = js.indexOf('}}', i + 2);
      if (fin < 0) return null;
      remplacer('__blade', js.slice(i, fin + 2));
      i = fin + 2;
      continue;
    }

    const m = /^@(json|js)\s*\(/.exec(reste);
    if (m) {
      const fin = finDesParentheses(js, i + m[0].length - 1);
      if (fin < 0) return null;
      remplacer('null', js.slice(i, fin));
      i = fin;
      continue;
    }

    // @php … @endphp s'ouvre n'importe ou, pas seulement en debut de ligne.
    if (/^@php\b(?!\s*\()/.test(reste)) {
      const ferme = js.indexOf('@endphp', i + 4);
      if (ferme < 0) return null;
      remplacer('', js.slice(i, ferme + 7));
      i = ferme + 7;
      continue;
    }

    // Directive en debut de ligne : la ligne entiere disparait, arguments
    // compris meme s'ils courent sur plusieurs lignes.
    const debutDeLigne = /^[ \t]*$/.test(js.slice(js.lastIndexOf('\n', i - 1) + 1, i));
    const d = debutDeLigne && /^@([A-Za-z]+)/.exec(reste);
    if (d) {
      let fin = i + d[0].length;
      if (d[1] === 'verbatim') { verbatim = true; i = fin; continue; }
      const p = /^\s*\(/.exec(js.slice(fin, fin + 20));
      if (p) {
        fin = finDesParentheses(js, fin + p[0].length - 1);
        if (fin < 0) return null;
      }
      remplacer('', js.slice(i, fin));
      i = fin;
      continue;
    }

    sortie += js[i++];
  }
  return sortie + enAttente;
}

function lire(f) {
  if (!index) return fs.readFileSync(f, 'utf8');
  return execFileSync('git', ['show', ':' + f], { encoding: 'utf8', maxBuffer: 64 << 20 });
}

const TYPES_JS = /^(|text\/javascript|application\/javascript|text\/ecmascript|application\/ecmascript)$/i;
let constats = 0;

for (const f of fichiers) {
  let source;
  try {
    source = lire(f);
  } catch (e) {
    console.error(`pre-commit : impossible de lire ${f}, JavaScript non verifie.`);
    continue;
  }

  // Les commentaires Blade d'abord : un <script> commente n'est jamais rendu.
  const texte = source.replace(/\{\{--[\s\S]*?--\}\}/g, lignes);

  // Balayage dans l'ordre du document, comme le navigateur : un `<script>`
  // cite dans une feuille de style ou un commentaire HTML n'est que du texte.
  const balise = /<style\b[\s\S]*?<\/style\s*>|<!--[\s\S]*?-->|<script\b([^>]*)>([\s\S]*?)<\/script\s*>/gi;
  let m;
  while ((m = balise.exec(texte)) !== null) {
    if (m[2] === undefined) continue;
    const attributs = m[1];
    if (/\bsrc\s*=/i.test(attributs)) continue;
    const type = /\btype\s*=\s*["']?([^"'\s>]*)/i.exec(attributs);
    const ligneDuBloc = texte.slice(0, m.index + m[0].indexOf('>') + 1).split('\n').length;
    if (type && /^module$/i.test(type[1])) {
      console.error(`${f}:${ligneDuBloc}  <script type="module"> non verifie (analyse de module non prise en charge)`);
      continue;
    }
    if (!TYPES_JS.test(type ? type[1] : '')) continue;

    const js = neutraliser(m[2]);
    if (js === null) {
      // Un {{, {!!, @json( ou @php sans fermeture : on ne sait pas ou reprendre
      // le JavaScript. Le dire plutot que de laisser croire le bloc verifie.
      console.error(`${f}:${ligneDuBloc}  <script> non verifie : construction Blade non refermee dans le bloc`);
      continue;
    }

    try {
      new vm.Script(js, { filename: 'bloc' });
    } catch (e) {
      if (!(e instanceof SyntaxError)) continue;
      const pos = /^bloc:(\d+)/.exec(e.stack || '');
      const ligne = ligneDuBloc + (pos ? Number(pos[1]) - 1 : 0);
      console.log(`${f}:${ligne}  JavaScript invalide dans un <script> : ${e.message}`);
      console.log('        -> le navigateur ignore tout ce <script> ; cause frequente : une apostrophe');
      console.log("           qui ferme la chaine (ecrire \\' ou changer de guillemets), une accolade manquante");
      constats++;
    }
  }
}

process.exit(constats ? 1 : 0);
