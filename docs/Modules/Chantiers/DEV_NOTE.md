# 🏗️ Module Chantiers (Gestion de Projets BTP)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture solide couvrant les chantiers, les phases de construction, les tâches, les budgets, l'affectation des équipes, le journal de bord (log) et les documents DOE.
*   **Logique d'analyse :** La gestion des imputations de coûts et du suivi financier par chantier est en place, incluant désormais l'intégration des **coûts de flotte**. Un widget d'aperçu financier en temps réel a été ajouté.
*   **DOE :** Les fiches techniques des articles sont automatiquement incluses dans la génération du DOE (avec patch de la gestion des chemins absolus via Storage).
*   **Tests :** Testé à 100% avec PestPHP (les 38 tests métier passent avec succès, y compris les tests d'évaluation financière et analytique).

## 🚧 Ce qu'il reste à faire
*   **Frontend (Critique) :** L'UI est inexistante. C'est le module qui demandera le plus d'attention UX/UI car il s'agit du cœur de la gestion quotidienne de l'entreprise.
*   **Dashboards dédiés :** Création de tableaux de bords analytiques de rentabilité par chantier.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Planification Gantt (Interactive) :** Intégrer un widget diagramme de Gantt interactif permettant de déplacer visuellement (Drag & Drop) les phases du chantier et l'affectation des équipes, avec recalcul automatique des dépendances.
2.  **Suivi de Chantier Mobile (Speech-to-Text) :** Via une PWA, permettre aux conducteurs de travaux d'utiliser la reconnaissance vocale pour dicter leur rapport de visite et le retranscrire automatiquement dans le journal de bord.
3.  **Module de Pointage Matériel (IoT) :** Intégrer des capteurs IoT ou des QR Codes pour tracker l'entrée/sortie du gros matériel sur le chantier et imputer le coût d'immobilisation de manière automatisée.
4.  **BIM (Building Information Modeling) :** Intégrer une visionneuse 3D de maquettes BIM (ex: Forge viewer) pour lier visuellement les tâches aux éléments de la maquette (cliquer sur un mur pour voir les tâches associées).
