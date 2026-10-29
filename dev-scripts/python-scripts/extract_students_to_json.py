#!/usr/bin/env python3
"""
Script pour extraire tous les étudiants depuis Excel et les sauvegarder en JSON
"""
import pandas as pd
import json
import re
from datetime import datetime

def extract_filiere_from_classe(classe_name):
    """Détermine la filière à partir du nom de la classe"""
    classe_upper = str(classe_name).upper()
    
    if 'BATIMENT' in classe_upper or 'BÂTIMENT' in classe_upper:
        return 'BATIMENT'
    elif 'TRAVAUX PUBLICS' in classe_upper or 'TP' in classe_upper:
        return 'TRAVAUX_PUBLICS'
    elif 'GÉOMÈTRE' in classe_upper or 'TOPOGRAPHE' in classe_upper:
        return 'GEOMETRE_TOPOGRAPHE'
    elif 'TRANSPORT' in classe_upper or 'INFRASTRUCTURE' in classe_upper:
        return 'TRANSPORT'
    elif 'ARCHITECTURE' in classe_upper:
        return 'ARCHITECTURE'
    else:
        return 'AUTRES'

def separer_nom_prenoms(nom_complet):
    """Sépare le nom complet en nom et prénoms"""
    parts = str(nom_complet).strip().split()
    if len(parts) <= 2:
        return {
            'nom': parts[0] if len(parts) > 0 else '',
            'prenoms': parts[1] if len(parts) > 1 else ''
        }
    
    # Prendre les 2 premiers mots comme nom, le reste comme prénoms
    nom = ' '.join(parts[:2])
    prenoms = ' '.join(parts[2:])
    
    return {
        'nom': nom,
        'prenoms': prenoms
    }

def main():
    excel_file = "DATA/LISTE ETUIANTS2425 OKKK.xlsx"
    
    print("📊 Extraction des étudiants depuis Excel...")
    
    try:
        # Lire le fichier Excel
        df = pd.read_excel(excel_file, sheet_name=0)
        
        print(f"✅ {len(df)} étudiants trouvés")
        
        # Initialiser la liste des étudiants
        etudiants = []
        compteurs = {
            'total': 0,
            'valides': 0,
            'erreurs': 0,
            'classes_manquantes': set()
        }
        
        # Colonnes attendues
        colonnes_requises = ['MAT', 'NOMP', 'Libelle_classe']
        
        # Vérifier les colonnes
        for col in colonnes_requises:
            if col not in df.columns:
                print(f"❌ Colonne manquante: {col}")
                return
        
        print("\n🔄 Traitement des étudiants...")
        
        for index, row in df.iterrows():
            compteurs['total'] += 1
            
            try:
                # Données essentielles
                matricule = str(row['MAT']).strip() if pd.notna(row['MAT']) else ''
                nom_complet = str(row['NOMP']).strip() if pd.notna(row['NOMP']) else ''
                libelle_classe = str(row['Libelle_classe']).strip() if pd.notna(row['Libelle_classe']) else ''
                
                # Vérifier les données essentielles
                if not matricule or not nom_complet or not libelle_classe:
                    print(f"⚠️ Ligne {index + 2}: Données essentielles manquantes")
                    compteurs['erreurs'] += 1
                    continue
                
                # Séparer nom et prénoms
                nom_prenoms = separer_nom_prenoms(nom_complet)
                
                # Extraire filière
                filiere = extract_filiere_from_classe(libelle_classe)
                
                # Préparer les données étudiant
                etudiant = {
                    'matricule': matricule,
                    'nom': nom_prenoms['nom'],
                    'prenoms': nom_prenoms['prenoms'],
                    'nom_complet': nom_complet,
                    'libelle_classe': libelle_classe,
                    'filiere_deduite': filiere,
                    'niveau': str(row['Code_niveau']).strip() if pd.notna(row['Code_niveau']) else '',
                    'sexe': str(row['Genre_El']).strip() if pd.notna(row['Genre_El']) else 'M',
                    'nationalite': str(row['Code_Nte']).strip() if pd.notna(row['Code_Nte']) else 'IV',
                    'is_redoublant': 1 if pd.notna(row.get('Redoublant')) and row['Redoublant'] == 1.0 else 0,
                    'email': f"{matricule.lower()}@esbtp.edu.ci"
                }
                
                # Données optionnelles
                if pd.notna(row.get('Datenais_El')):
                    etudiant['date_naissance'] = str(row['Datenais_El'])
                
                if pd.notna(row.get('Lieunais_El')):
                    etudiant['lieu_naissance'] = str(row['Lieunais_El']).strip()
                
                if pd.notna(row.get('Contact')) and str(row['Contact']).strip():
                    etudiant['telephone'] = str(row['Contact']).strip()
                
                etudiants.append(etudiant)
                compteurs['valides'] += 1
                
                if compteurs['valides'] % 250 == 0:
                    print(f"✅ {compteurs['valides']} étudiants traités...")
                
            except Exception as e:
                print(f"❌ Erreur ligne {index + 2}: {e}")
                compteurs['erreurs'] += 1
        
        # Analyser les filières et classes
        filieres_stats = {}
        classes_stats = {}
        niveaux_stats = {}
        
        for etudiant in etudiants:
            # Stats filières
            filiere = etudiant['filiere_deduite']
            filieres_stats[filiere] = filieres_stats.get(filiere, 0) + 1
            
            # Stats classes
            classe = etudiant['libelle_classe']
            classes_stats[classe] = classes_stats.get(classe, 0) + 1
            
            # Stats niveaux
            niveau = etudiant['niveau']
            niveaux_stats[niveau] = niveaux_stats.get(niveau, 0) + 1
        
        # Créer le fichier JSON complet
        output_data = {
            'metadata': {
                'total_etudiants': len(etudiants),
                'date_extraction': datetime.now().isoformat(),
                'source_file': excel_file,
                'classes_uniques': len(classes_stats),
                'filieres': len(filieres_stats),
                'niveaux': len(niveaux_stats)
            },
            'statistiques': {
                'filieres': filieres_stats,
                'classes': classes_stats,
                'niveaux': niveaux_stats
            },
            'etudiants': etudiants
        }
        
        # Sauvegarder le JSON
        with open('students_data.json', 'w', encoding='utf-8') as f:
            json.dump(output_data, f, indent=2, ensure_ascii=False)
        
        print(f"\n=== RÉSULTATS D'EXTRACTION ===")
        print(f"📊 Total lignes traitées: {compteurs['total']}")
        print(f"✅ Étudiants valides: {compteurs['valides']}")
        print(f"❌ Erreurs: {compteurs['erreurs']}")
        
        print(f"\n=== RÉPARTITION PAR FILIÈRE ===")
        for filiere, count in sorted(filieres_stats.items(), key=lambda x: x[1], reverse=True):
            print(f"  {filiere}: {count} étudiants")
        
        print(f"\n=== RÉPARTITION PAR NIVEAU ===")
        for niveau, count in sorted(niveaux_stats.items(), key=lambda x: x[1], reverse=True):
            print(f"  {niveau}: {count} étudiants")
        
        print(f"\n=== TOP 10 CLASSES ===")
        top_classes = sorted(classes_stats.items(), key=lambda x: x[1], reverse=True)[:10]
        for classe, count in top_classes:
            print(f"  {classe}: {count} étudiants")
        
        print(f"\n✅ Données sauvegardées dans students_data.json")
        print(f"🎯 Prêt pour l'import dans le seeder Laravel !")
        
    except Exception as e:
        print(f"❌ Erreur: {e}")

if __name__ == "__main__":
    main()