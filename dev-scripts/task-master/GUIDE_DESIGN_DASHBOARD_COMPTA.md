{
"dashboard_acasi": {
"meta": {
"app_name": "ACASI",
"type": "dashboard_comptabilite",
"annee": "2021",
"utilisateur": "Jean Martin",
"export_date": "2025-07-09"
},

    "header": {
      "logo": {
        "text": "ACASI",
        "style": {
          "font_weight": "bold",
          "color": "#1e3a8a",
          "font_size": "24px"
        }
      },

      "search_bar": {
        "placeholder": "Rechercher",
        "style": {
          "background": "#f3f4f6",
          "border_radius": "20px",
          "width": "300px"
        }
      },

      "year_selector": {
        "selected": "2021",
        "type": "dropdown",
        "style": {
          "background": "#f3f4f6",
          "border_radius": "8px"
        }
      },

      "user_actions": [
        {
          "type": "profile",
          "icon": "user"
        },
        {
          "type": "accessibility",
          "icon": "accessibility"
        },
        {
          "type": "messages",
          "icon": "message"
        },
        {
          "type": "logout",
          "icon": "power"
        }
      ]
    },

    "sidebar": {
      "navigation": [
        {
          "id": "accueil",
          "label": "Accueil",
          "icon": "home",
          "active": true,
          "style": {
            "background": "#bfdbfe",
            "color": "#1e40af",
            "border_radius": "12px"
          }
        },
        {
          "id": "banques",
          "label": "Banques",
          "icon": "bank",
          "active": false
        },
        {
          "id": "clients",
          "label": "Clients",
          "icon": "users",
          "active": false
        },
        {
          "id": "ventes",
          "label": "Ventes",
          "icon": "shopping_bag",
          "active": false
        },
        {
          "id": "produits",
          "label": "Produits",
          "icon": "package",
          "active": false
        },
        {
          "id": "achats",
          "label": "Achats",
          "icon": "shopping_cart",
          "active": false
        },
        {
          "id": "documents",
          "label": "Documents",
          "icon": "folder",
          "active": false
        },
        {
          "id": "social",
          "label": "Social",
          "icon": "settings",
          "active": false
        }
      ],

      "user_profile": {
        "nom": "Jean Martin",
        "avatar": {
          "type": "circle",
          "background": "#1e3a8a",
          "initials": "JM",
          "color": "#ffffff"
        }
      }
    },

    "main_content": {
      "soldes_section": {
        "title": "Soldes principaux",
        "cards": [
          {
            "type": "solde_bancaire",
            "title": "SOLDE BANCAIRE",
            "montant": "9841 €",
            "style": {
              "background": "#ffffff",
              "border_radius": "12px",
              "shadow": "0 1px 3px rgba(0,0,0,0.1)"
            },
            "graphique": {
              "type": "line_chart",
              "couleur": "#1e40af",
              "tendance": "stable_avec_variations"
            }
          },
          {
            "type": "solde_previsionnel",
            "title": "SOLDE PRÉVISIONNEL",
            "montant": "12780 €",
            "style": {
              "background": "#ffffff",
              "border_radius": "12px",
              "shadow": "0 1px 3px rgba(0,0,0,0.1)"
            },
            "graphique": {
              "type": "line_chart",
              "couleur": "#1e40af",
              "tendance": "croissante"
            }
          }
        ]
      },

      "taxes_section": {
        "title": "Taxes et cotisations",
        "cards": [
          {
            "type": "tva",
            "title": "TVA",
            "montant": "630 €",
            "couleur": "#f97316",
            "echeance": "Prochaine échéance le 20/07/2021"
          },
          {
            "type": "impots",
            "title": "IMPÔTS",
            "montant": "845 €",
            "couleur": "#f97316",
            "echeance": "Prochaine échéance le 20/08/2022"
          },
          {
            "type": "cotisations_sociales",
            "title": "COT. SOCIALES",
            "montant": "1171 €",
            "couleur": "#6b7280",
            "echeance": "Prochaine échéance le 21/03/2021"
          }
        ]
      },

      "resultats_section": {
        "cards": [
          {
            "type": "chiffre_affaires",
            "title": "CHIFFRE D'AFFAIRES HT 2021",
            "montant": "18 540 €",
            "couleur": "#06b6d4",
            "details": [
              {
                "client": "Big Blue",
                "montant": "2000 €"
              },
              {
                "client": "ArtGéo",
                "montant": "3000 €"
              },
              {
                "client": "ArtGéo",
                "montant": "3000 €"
              }
            ]
          },
          {
            "type": "resultat",
            "title": "RÉSULTAT 2021",
            "montant": "10 125 €",
            "couleur": "#06b6d4",
            "details": [
              {
                "ligne": "Chiffre d'affaires",
                "montant": "18 540 €"
              },
              {
                "ligne": "Achat",
                "montant": "1 500 €"
              },
              {
                "ligne": "Impôts",
                "montant": "415 €"
              },
              {
                "ligne": "Perte",
                "montant": "10 125 €"
              }
            ]
          },
          {
            "type": "charges",
            "title": "CHARGES 2021",
            "montant": "8000 €",
            "couleur": "#06b6d4",
            "details": [
              {
                "categorie": "Hôtel",
                "montant": "2 040 €",
                "icon": "bed"
              },
              {
                "categorie": "Restaurant",
                "montant": "2 200 €",
                "icon": "utensils"
              },
              {
                "categorie": "VTC",
                "montant": "1 800 €",
                "icon": "car"
              },
              {
                "categorie": "Logiciel",
                "montant": "960 €",
                "icon": "computer"
              }
            ]
          }
        ]
      }
    },

    "sidebar_droite": {
      "title": "ENCAISSEMENT CLIENT TTC",
      "sous_titre": "4500 € en attente d'encaissement",

      "clients": [
        {
          "nom": "ACCENTURE",
          "montant": "1200 €",
          "couleur": "#ef4444",
          "statut": "non encaissé sur 1200 €"
        },
        {
          "nom": "WEMIND",
          "montant": "800 €",
          "couleur": "#ef4444",
          "statut": "non encaissé sur 800 €"
        },
        {
          "nom": "BIG BLUE",
          "montant": "2000 €",
          "couleur": "#10b981",
          "statut": "non encaissé sur 2000 €"
        },
        {
          "nom": "ARTGGO",
          "montant": "3000 €",
          "couleur": "#10b981",
          "statut": "non encaissé sur 3000 €"
        }
      ]
    },

    "design_system": {
      "palette_couleurs": {
        "primaire": "#1e3a8a",
        "secondaire": "#1e40af",
        "accent_blue": "#06b6d4",
        "accent_orange": "#f97316",
        "success": "#10b981",
        "danger": "#ef4444",
        "neutre": "#6b7280",
        "background": "#f8fafc",
        "surface": "#ffffff",
        "text_primary": "#111827",
        "text_secondary": "#6b7280"
      },

      "typographie": {
        "font_family": "system-ui, -apple-system, sans-serif",
        "tailles": {
          "titre_principale": "24px",
          "titre_section": "14px",
          "montant_principal": "28px",
          "montant_secondaire": "20px",
          "texte_normal": "14px",
          "texte_small": "12px"
        },
        "poids": {
          "normal": "400",
          "medium": "500",
          "bold": "700"
        }
      },

      "espacements": {
        "xs": "4px",
        "sm": "8px",
        "md": "16px",
        "lg": "24px",
        "xl": "32px"
      },

      "border_radius": {
        "small": "6px",
        "medium": "12px",
        "large": "20px",
        "circle": "50%"
      },

      "shadows": {
        "card": "0 1px 3px rgba(0,0,0,0.1)",
        "elevated": "0 4px 6px rgba(0,0,0,0.1)"
      }
    },

    "layout": {
      "structure": "sidebar_main_sidebar",
      "sidebar_gauche": {
        "largeur": "200px",
        "background": "#ffffff"
      },
      "main_content": {
        "largeur": "flexible",
        "background": "#f8fafc",
        "padding": "24px"
      },
      "sidebar_droite": {
        "largeur": "280px",
        "background": "#ffffff"
      }
    },

    "composants": {
      "card": {
        "background": "#ffffff",
        "border_radius": "12px",
        "shadow": "0 1px 3px rgba(0,0,0,0.1)",
        "padding": "20px"
      },

      "navigation_item": {
        "padding": "12px 16px",
        "border_radius": "12px",
        "active_background": "#bfdbfe",
        "active_color": "#1e40af",
        "hover_background": "#f3f4f6"
      },

      "montant": {
        "font_weight": "700",
        "color": "#111827"
      },

      "graphique_line": {
        "stroke_width": "2px",
        "couleur_principale": "#1e40af"
      }
    }

}
}
