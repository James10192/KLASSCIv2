#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import json
import os

try:
    import pandas as pd
    import openpyxl
except ImportError as e:
    print(f"Erreur: Module manquant - {e}")
    print("Installez les dépendances avec: pip install pandas openpyxl")
    sys.exit(1)

def analyze_excel_file(file_path):
    """Analyser un fichier Excel et extraire les informations sur les étudiants"""
    
    if not os.path.exists(file_path):
        print(f"Fichier non trouvé: {file_path}")
        return None
    
    try:
        # Lire le fichier Excel
        print(f"Lecture du fichier: {file_path}")
        print(f"Taille du fichier: {os.path.getsize(file_path)} bytes")
        
        # Lire toutes les feuilles
        excel_file = pd.ExcelFile(file_path)
        print(f"Nombre de feuilles: {len(excel_file.sheet_names)}")
        print(f"Noms des feuilles: {excel_file.sheet_names}")
        
        # Lire la première feuille
        df = pd.read_excel(file_path, sheet_name=0)
        
        print(f"\n=== STRUCTURE DU FICHIER ===")
        print(f"Nombre de lignes: {len(df)}")
        print(f"Nombre de colonnes: {len(df.columns)}")
        
        print(f"\n=== COLONNES DÉTECTÉES ===")
        for i, col in enumerate(df.columns):
            print(f"  {i}: '{col}'")
        
        print(f"\n=== APERÇU DES DONNÉES ===")
        # Afficher les 10 premières lignes non nulles
        for i in range(min(10, len(df))):
            row = df.iloc[i]
            # Filtrer les valeurs non nulles
            non_null_data = {col: str(val) for col, val in row.items() if pd.notna(val) and str(val).strip() != ''}
            
            if non_null_data:
                print(f"Ligne {i + 1}: {json.dumps(non_null_data, ensure_ascii=False)}")
        
        print(f"\n=== ANALYSE DES COLONNES ===")
        for col in df.columns:
            non_null_count = df[col].count()
            unique_count = df[col].nunique()
            sample_values = df[col].dropna().head(3).tolist()
            
            print(f"Colonne '{col}':")
            print(f"  - Valeurs non nulles: {non_null_count}")
            print(f"  - Valeurs uniques: {unique_count}")
            print(f"  - Échantillon: {sample_values}")
            
            # Deviner le type de données
            if df[col].dtype == 'object':
                # Vérifier si c'est des dates
                try:
                    pd.to_datetime(df[col].dropna().head(5), errors='raise')
                    data_type = "DATE"
                except:
                    data_type = "TEXT"
            elif df[col].dtype in ['int64', 'float64']:
                data_type = "NUMERIC"
            else:
                data_type = str(df[col].dtype)
            
            print(f"  - Type détecté: {data_type}")
            print()
        
        # Identifier les colonnes qui pourraient correspondre aux champs d'étudiants
        print(f"\n=== MAPPING PROBABLE DES CHAMPS ===")
        student_fields_mapping = {}
        
        for col in df.columns:
            col_lower = str(col).lower().strip()
            
            if any(keyword in col_lower for keyword in ['nom', 'name', 'surname']):
                student_fields_mapping['nom'] = col
            elif any(keyword in col_lower for keyword in ['prénom', 'prenom', 'firstname', 'given']):
                student_fields_mapping['prenoms'] = col
            elif any(keyword in col_lower for keyword in ['email', 'mail', '@']):
                student_fields_mapping['email'] = col
            elif any(keyword in col_lower for keyword in ['téléphone', 'telephone', 'phone', 'tel']):
                student_fields_mapping['telephone'] = col
            elif any(keyword in col_lower for keyword in ['matricule', 'numero', 'id', 'code']):
                student_fields_mapping['matricule'] = col
            elif any(keyword in col_lower for keyword in ['filière', 'filiere', 'formation', 'program']):
                student_fields_mapping['filiere'] = col
            elif any(keyword in col_lower for keyword in ['classe', 'class', 'level']):
                student_fields_mapping['classe'] = col
            elif any(keyword in col_lower for keyword in ['naissance', 'birth', 'né']):
                student_fields_mapping['date_naissance'] = col
            elif any(keyword in col_lower for keyword in ['lieu', 'place', 'ville']):
                student_fields_mapping['lieu_naissance'] = col
            elif any(keyword in col_lower for keyword in ['adresse', 'address']):
                student_fields_mapping['adresse'] = col
        
        print("Correspondances trouvées:")
        for field, column in student_fields_mapping.items():
            print(f"  {field} -> '{column}'")
        
        # Sauvegarder l'analyse dans un fichier JSON
        analysis_result = {
            'file_info': {
                'path': file_path,
                'size': os.path.getsize(file_path),
                'sheets': excel_file.sheet_names
            },
            'data_info': {
                'rows': len(df),
                'columns': len(df.columns),
                'column_names': list(df.columns)
            },
            'field_mapping': student_fields_mapping,
            'sample_data': []
        }
        
        # Ajouter quelques exemples de données
        for i in range(min(5, len(df))):
            row = df.iloc[i]
            row_data = {col: str(val) if pd.notna(val) else None for col, val in row.items()}
            analysis_result['sample_data'].append(row_data)
        
        # Sauvegarder l'analyse
        with open('excel_analysis.json', 'w', encoding='utf-8') as f:
            json.dump(analysis_result, f, ensure_ascii=False, indent=2)
        
        print(f"\n=== RÉSUMÉ ===")
        print(f"Analyse sauvegardée dans: excel_analysis.json")
        print(f"Le fichier contient {len(df)} lignes de données")
        print(f"Colonnes probablement mappées: {len(student_fields_mapping)}")
        
        return analysis_result
        
    except Exception as e:
        print(f"Erreur lors de la lecture du fichier: {e}")
        return None

if __name__ == "__main__":
    file_path = "DATA/LISTE ETUIANTS2425 OKKK.xlsx"
    result = analyze_excel_file(file_path)