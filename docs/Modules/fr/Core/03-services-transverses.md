---
title: Services Transverses (Moteurs)
icon: heroicon-o-cpu-chip
order: 4
---

# 🚀 Services Transverses (Moteurs)

Le module Core abrite les "Moteurs" (Services) qui tournent en tâche de fond pour l'ensemble des modules de Batistack.

## 1. Le Moteur de Génération PDF

C'est lui qui transforme vos Devis, Commandes, Factures et Fiches de Paie en documents PDF impeccables, respectant l'identité visuelle de votre entreprise (logo, couleurs, mentions légales). 
Il fonctionne de manière asynchrone pour ne pas ralentir votre navigation.

## 2. Le Moteur de Signature Numérique

Ce service gère la signature électronique des devis clients, des contrats RH, ou des audits Qualité (QSE) sur les chantiers. 

- **Authenticité** : Lorsqu'un document est signé, ce moteur calcule une empreinte cryptographique (hash SHA-256) du document.
- **Scellement** : Le document PDF est scellé avec le certificat de signature, le rendant juridiquement valide et inaltérable.
- Il supporte le recueil de signature en "Local" (directement sur votre tablette sur un chantier) ou "à distance" via e-mail sécurisé.

## 3. Le Moteur Géospatial (Google Maps)

Batistack intègre un service de géolocalisation utilisé par les modules de Chantiers et de Flotte Automobile. 
- Il permet de convertir les adresses saisies en coordonnées GPS exactes.
- Il est capable de calculer des matrices de distance (temps de trajet) pour optimiser les déplacements de vos véhicules (routing).

## 4. Le Service Météorologique

Relié à l'API OpenWeather, ce service récupère les prévisions et les alertes météo en fonction des coordonnées de vos chantiers. 
C'est ce qui permet d'afficher automatiquement les conditions climatiques dans les journaux de chantier, et de justifier d'éventuels jours d'intempéries sans intervention manuelle de votre part.
