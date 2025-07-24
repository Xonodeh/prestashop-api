# prestashop-api
# Exercicedashboard & Client Matricule Checker

## Le but du module

Ce module permet d’afficher dans le tableau de bord du back-office PrestaShop la meteo.  
Les données sont récupérées via une API publique gratuite.  

---

## Les prérequis

- PrestaShop version 1.7 ou supérieure  
- PHP version 7.4 minimum (8.0 recommandé)  
- Extension PHP curl activée ou `allow_url_fopen` activé

---

## Comment installer le module

1. Copier le dossier `exercicedashboard` dans le répertoire `/modules` de l’installation PrestaShop.  
2. Aller dans le back-office PrestaShop, puis dans le menu **Modules** > **Gestionnaire de modules**.  
3. Rechercher le module nommé `exercicedashboard`.  
4. Cliquer sur le bouton **Installer**.  
5. Une fois installé, cliquer sur **Configurer** pour accéder à la page de configuration.

---

## Comment le configurer

- Activer ou désactiver le module selon les besoins.  
- Choisir la fréquence de mise à jour des données : manuelle ou automatique toutes les 24 heures.  
- Utiliser le bouton **Mettre à jour maintenant** pour afficher la météo a l'instant T

---

## Comment tester les fonctionnalités

- Vérifier que le widget affichant la météo apparaît bien dans le tableau de bord du back-office.  
- Tester la mise à jour manuelle en cliquant sur le bouton **Mettre à jour maintenant** et vérifier que la donnée est actualisée.  
- Si la mise à jour automatique est activée, vérifier après 24 heures que les données se sont mises à jour automatiquement.  
- Tester l’activation et la désactivation du module via la page de configuration.

# Module ClientMatricule

## Description

Ce module permet de vérifier l'authenticité des clients via un champ **matricule** ajouté au formulaire d'inscription, en comparant ce matricule à une liste de matricules valides importée par boutique.

Le module est compatible **multiboutique** : chaque boutique dispose de sa propre liste de matricules valides.

---

## Fonctionnalités

- Ajout d'un champ **matricule** obligatoire dans le formulaire d'inscription client.
- Upload d'un fichier `.csv` contenant la liste des matricules valides, spécifique à chaque boutique.
- Validation du matricule saisi lors de l'inscription client en le comparant à la liste importée.
- Affichage dans le Back Office de la liste des clients avec leur statut de validation (matricule valide ou non) par boutique.
- Support multiboutique, avec isolation des données par boutique.

---

## Format du fichier CSV attendu

Le fichier CSV doit être un fichier texte avec un encodage UTF-8, contenant une liste des matricules valides, avec ou sans en-tête.

### Format simple recommandé

Chaque ligne contient un matricule valide :

```csv
12345
23456
34567
45678

