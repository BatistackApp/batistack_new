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
- **Signatures multiples** : un même document peut être soumis à **plusieurs signataires**, chacun recevant son lien dédié. Le document n'est scellé qu'une fois l'ensemble des signatures recueillies.

Les demandes de signature sont pilotées depuis un **Panel Signatures** dédié, qui centralise les documents à faire signer et leur état d'avancement.

## 3. La Gestion Documentaire (GED)

Batistack indexe automatiquement **tous les PDF générés** par l'ERP (devis, contrats, rapports d'intervention, fiches de paie…) dans une **Gestion Électronique de Documents**.

- Une arborescence unique permet de retrouver, classer et archiver l'ensemble des documents, sans les éparpiller.
- L'accès est **cloisonné par espace** : chaque portail (administrateur, salarié, client, sous-traitant) ne voit que les documents qui le concernent.
- Les documents existants peuvent être ré-indexés en une commande, et la consultation s'appuie sur des liens sécurisés (compatible stockage cloud).

> [!NOTE]
> **Un seul point d'accès**
> La GED alimente directement les rubriques « Mes Documents » des différents portails self-service.

## 4. Le Moteur Géospatial (Google Maps)

Batistack intègre un service de géolocalisation utilisé par les modules de Chantiers et de Flotte Automobile. 
- Il permet de convertir les adresses saisies en coordonnées GPS exactes.
- Il est capable de calculer des matrices de distance (temps de trajet) pour optimiser les déplacements de vos véhicules (routing).

## 5. Le Service Météorologique

Relié à l'API OpenWeather, ce service récupère les prévisions et les alertes météo en fonction des coordonnées de vos chantiers. 
C'est ce qui permet d'afficher automatiquement les conditions climatiques dans les journaux de chantier, et de justifier d'éventuels jours d'intempéries sans intervention manuelle de votre part.
