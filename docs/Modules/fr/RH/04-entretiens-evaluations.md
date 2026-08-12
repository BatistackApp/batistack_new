---
title: Entretiens & Évaluations
icon: heroicon-o-chat-bubble-bottom-center-text
order: 4
---

# 💬 Entretiens & Évaluations

Ce sous-module permet aux équipes RH et aux managers de centraliser et structurer les campagnes d'entretiens annuels et professionnels. Il dématérialise intégralement le processus, de la convocation jusqu'à la signature finale.

## 📌 Types d'Entretiens Supportés

- **Entretien Annuel** : Évaluation des performances de l'année écoulée.
- **Entretien Professionnel** : Perspectives d'évolution (obligatoire tous les 2 ans).
- **Entretien de Recadrage** : Suivi managérial spécifique.
- **Autre** : Format personnalisable.

## ✨ Fonctionnalités Principales

### Grille d'Évaluation Dynamique
Les managers ne sont plus contraints par un formulaire figé. Grâce à la grille d'évaluation dynamique (composant *Repeater* dans Filament), il est possible de créer à la volée des couples `Questions / Objectifs` et d'y apporter l'évaluation correspondante. 

### Signatures Électroniques Intégrées
Le recueil des signatures est réalisé en face à face ou à distance. Le composant s'appuie sur `saade/filament-autograph` qui offre un pavé de signature intégré dans l'interface, stockant de manière sécurisée les consentements de l'employé et du manager sous forme de canvas.

### Génération de Compte-Rendu PDF (Browsershot)
Une fois l'entretien réalisé et signé, un simple clic sur l'action **"Générer PDF"** produit le compte-rendu officiel au format PDF.
- Rendu parfait assuré par **Tailwind CSS** et le moteur **Puppeteer/Browsershot**.
- Insertion automatique des logos, de la grille remplie, et des deux signatures en bas de document.
- Archivable numériquement pour les inspections du travail.
