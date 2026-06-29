# 🏗️ Module Chantiers (Gestion de Projets BTP)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Architecture solide couvrant les chantiers, les phases de construction, les tâches, les budgets, l'affectation des équipes, le journal de bord (log) et les documents DOE.
*   **Logique d'analyse :** La gestion des imputations de coûts et du suivi financier par chantier est en place.
*   **Tests :** Testé à 100% avec PestPHP (les 38 tests métier passent avec succès).

## 🚧 Ce qu'il reste à faire
*   **Frontend (Critique) :** L'UI est inexistante. C'est le module qui demandera le plus d'attention UX/UI car il s'agit du cœur de la gestion quotidienne de l'entreprise.
*   **Dashboards dédiés :** Création de tableaux de bords analytiques de rentabilité par chantier.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Suivi de Chantier Mobile (Photo + Audio) :** Via la PWA, permettre aux conducteurs de travaux de prendre des photos de l'avancement ou de malfaçons, et d'utiliser une fonction Speech-to-Text (reconnaissance vocale) pour générer leur rapport de visite automatiquement dans le `ChantierLog`.
2.  **Planification Gantt :** Intégrer un widget diagramme de Gantt cliquable permettant de déplacer visuellement (Drag & Drop) les phases du chantier et les équipes, tout en gérant les dépendances.
3.  **Météo Automatisée :** Rattacher un flux API météo externe au journal de bord pour justifier légalement des intempéries.
