#!/usr/bin/env python3
"""
Script pour analyser les données Excel et créer le mapping vers la base de données
"""
import pandas as pd
import os

def analyze_excel_data():
    """Analyser le fichier Excel pour créer le mapping DB"""
    
    excel_file = "DATA/LISTE ETUIANTS2425 OKKK.xlsx"
    
    if not os.path.exists(excel_file):
        print(f"❌ Fichier Excel non trouvé: {excel_file}")
        return
    
    try:
        # Lire le fichier Excel
        print("📊 Lecture du fichier Excel...")
        df = pd.read_excel(excel_file, sheet_name=0)
        
        print(f"✅ {len(df)} étudiants trouvés")
        print(f"✅ {len(df.columns)} colonnes disponibles")
        
        print("\n=== COLONNES EXCEL ===")
        for i, col in enumerate(df.columns):
            print(f"{i+1:2d}. {col}")
        
        print("\n=== ÉCHANTILLON DE DONNÉES (3 premiers étudiants) ===")
        for i in range(min(3, len(df))):
            print(f"\n--- Étudiant {i+1} ---")
            for col in df.columns:
                value = df.iloc[i][col]
                if pd.notna(value):
                    print(f"  {col}: {value}")
        
        print("\n=== ANALYSE DES NIVEAUX D'ÉTUDES ===")
        if 'Code_niveau' in df.columns:
            niveaux = df['Code_niveau'].value_counts()
            print("Niveaux trouvés:")
            for niveau, count in niveaux.items():
                print(f"  - {niveau}: {count} étudiants")
        
        print("\n=== ANALYSE DES CLASSES ===")
        if 'Libelle_classe' in df.columns:
            classes = df['Libelle_classe'].value_counts()
            print(f"Total classes: {len(classes)}")
            print("Top 10 classes par effectif:")
            for classe, count in classes.head(10).items():
                print(f"  - {classe}: {count} étudiants")
        
        print("\n=== ANALYSE DES NATIONALITÉS ===")
        if 'Code_Nte' in df.columns:
            nationalites = df['Code_Nte'].value_counts()
            print("Codes nationalité:")
            for nat, count in nationalites.items():
                print(f"  - {nat}: {count} étudiants")
        
        print("\n=== ANALYSE DES SEXES ===")
        if 'Genre_El' in df.columns:
            sexes = df['Genre_El'].value_counts()
            print("Répartition par sexe:")
            for sexe, count in sexes.items():
                print(f"  - {sexe}: {count} étudiants")
        
        print("\n=== MAPPING EXCEL → BASE DE DONNÉES ===")
        
        mapping = {
            # Colonnes Excel → Colonnes DB
            'MAT': 'matricule',
            'NOMP': 'nom + prenoms',  # À séparer
            'Nom_El Prenom_El': 'nom + prenoms',  # Alternative
            'Datenais_El': 'date_naissance',
            'Lieunais_El': 'lieu_naissance',
            'Genre_El': 'sexe',
            'Code_Nte': 'nationalite',
            'Contact': 'telephone',
            'Libelle_classe': 'classe_id (via mapping)',
            'Code_niveau': 'niveau via classe',
            'Redoublant': 'statut ou champ custom'
        }
        
        print("Mapping proposé:")
        for excel_col, db_col in mapping.items():
            if excel_col in df.columns:
                non_null_count = df[excel_col].count()
                print(f"  ✅ {excel_col:20} → {db_col:25} ({non_null_count} valeurs)")
            else:
                print(f"  ❌ {excel_col:20} → {db_col:25} (colonne manquante)")
        
        # Analyser les filières à partir des classes
        print("\n=== EXTRACTION DES FILIÈRES ===")
        if 'Libelle_classe' in df.columns:
            classes_uniques = df['Libelle_classe'].unique()
            filieres_detected = set()
            
            for classe in classes_uniques:
                if pd.notna(classe):
                    classe_str = str(classe).upper()
                    if 'BATIMENT' in classe_str or 'BÂTIMENT' in classe_str:
                        filieres_detected.add('BATIMENT')
                    elif 'TRAVAUX PUBLICS' in classe_str or 'TP' in classe_str:
                        filieres_detected.add('TRAVAUX_PUBLICS')
                    elif 'TRANSPORT' in classe_str:
                        filieres_detected.add('TRANSPORT')
                    elif 'ARCHITECTURE' in classe_str:
                        filieres_detected.add('ARCHITECTURE')
                    elif 'TOPOGRAPHIE' in classe_str:
                        filieres_detected.add('TOPOGRAPHIE')
                    elif 'GÉNIE CIVIL' in classe_str or 'GENIE CIVIL' in classe_str:
                        filieres_detected.add('GENIE_CIVIL')
            
            print("Filières détectées:")
            for filiere in sorted(filieres_detected):
                print(f"  - {filiere}")
        
        print("\n=== RÉSUMÉ POUR LE SEEDER ===")
        print(f"📊 Données à importer:")
        print(f"  - {len(df)} étudiants")
        print(f"  - {len(df['Libelle_classe'].unique())} classes uniques")
        print(f"  - {len(df['Code_niveau'].unique())} niveaux uniques") 
        print(f"  - {len(filieres_detected)} filières détectées")
        
        print(f"\n✅ Analyse terminée - Prêt pour création du seeder !")
        
    except Exception as e:
        print(f"❌ Erreur lors de l'analyse: {e}")

if __name__ == "__main__":
    analyze_excel_data()