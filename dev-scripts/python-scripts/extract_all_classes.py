#!/usr/bin/env python3
"""
Script pour extraire TOUTES les classes réelles du fichier Excel avec leurs filières et effectifs
"""
import pandas as pd
import json
import re

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

def extract_niveau_from_classe(classe_name):
    """Extrait le niveau d'études du nom de la classe"""
    # Chercher les patterns comme "2A", "1A", "L3", etc.
    match = re.match(r'^(\w+)', str(classe_name))
    if match:
        niveau = match.group(1)
        if niveau in ['1A', '2A', 'L1', 'L2', 'L3', 'M1', 'M2', '5A']:
            return niveau
    return 'INCONNU'

def main():
    excel_file = "DATA/LISTE ETUIANTS2425 OKKK.xlsx"
    
    print("📊 Extraction complète des classes depuis Excel...")
    
    try:
        # Lire le fichier Excel
        df = pd.read_excel(excel_file, sheet_name=0)
        
        print(f"✅ {len(df)} étudiants trouvés")
        
        # Analyser les classes et leurs effectifs
        classes_data = {}
        filieres_count = {}
        niveaux_count = {}
        
        for _, row in df.iterrows():
            classe_name = row['Libelle_classe']
            niveau = row['Code_niveau']
            
            if pd.notna(classe_name):
                # Compter les étudiants par classe
                if classe_name not in classes_data:
                    filiere = extract_filiere_from_classe(classe_name)
                    classes_data[classe_name] = {
                        'libelle': classe_name,
                        'filiere': filiere,
                        'niveau': niveau,
                        'effectif': 0
                    }
                    
                    # Compter par filière
                    filieres_count[filiere] = filieres_count.get(filiere, 0)
                    
                    # Compter par niveau
                    niveaux_count[niveau] = niveaux_count.get(niveau, 0)
                
                classes_data[classe_name]['effectif'] += 1
                filieres_count[classes_data[classe_name]['filiere']] += 1
                niveaux_count[niveau] += 1
        
        print(f"\n=== RÉSULTATS D'EXTRACTION ===")
        print(f"📚 {len(classes_data)} classes uniques trouvées")
        print(f"🎓 {len(filieres_count)} filières identifiées")
        print(f"📊 {len(niveaux_count)} niveaux d'études")
        
        print(f"\n=== RÉPARTITION PAR FILIÈRE ===")
        for filiere, count in sorted(filieres_count.items(), key=lambda x: x[1], reverse=True):
            print(f"  {filiere}: {count} étudiants")
        
        print(f"\n=== RÉPARTITION PAR NIVEAU ===")
        for niveau, count in sorted(niveaux_count.items(), key=lambda x: x[1], reverse=True):
            print(f"  {niveau}: {count} étudiants")
        
        print(f"\n=== TOUTES LES CLASSES AVEC EFFECTIFS ===")
        for classe_name, data in sorted(classes_data.items(), key=lambda x: x[1]['effectif'], reverse=True):
            print(f"  {data['libelle']} | {data['filiere']} | {data['niveau']} | {data['effectif']} étudiants")
        
        # Sauvegarder en JSON pour utilisation dans le seeder
        output_data = {
            'classes': list(classes_data.values()),
            'filieres': list(filieres_count.keys()),
            'niveaux': list(niveaux_count.keys()),
            'stats': {
                'total_etudiants': len(df),
                'total_classes': len(classes_data),
                'filieres_count': filieres_count,
                'niveaux_count': niveaux_count
            }
        }
        
        with open('classes_extraction.json', 'w', encoding='utf-8') as f:
            json.dump(output_data, f, indent=2, ensure_ascii=False)
        
        print(f"\n✅ Données sauvegardées dans classes_extraction.json")
        print(f"📋 Prêt pour mise à jour du seeder !")
        
    except Exception as e:
        print(f"❌ Erreur: {e}")

if __name__ == "__main__":
    main()