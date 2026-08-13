---
title: Annotations et Liaisons
icon: heroicon-o-chat-bubble-bottom-center-text
order: 3
---

# 📍 Annotations et Liaisons Métier

Avoir une maquette 3D est un bon début, mais Batistack va plus loin en transformant vos plans en de véritables outils de collaboration de bout en bout.

## 1. Créer une Annotation

Dans le visualiseur 3D, vous pouvez placer des **points d'intérêts** (annotations) directement sur la maquette.

1. Cliquez sur la surface exacte (un mur, une poutre, un tuyau) sur laquelle vous souhaitez ajouter une note.
2. Une fenêtre s'ouvre pour "Créer une annotation".
3. Renseignez le **Titre** et la **Description** (ex: "Climatisation défectueuse" ou "Percer ici").
4. Le système sauvegarde automatiquement les coordonnées 3D (X, Y, Z) du point que vous avez cliqué.

## 2. Le concept de "Liaison" (MorphTo)

Le vrai pouvoir de la Vision 3D dans Batistack est la possibilité de **lier une annotation à un autre élément de l'ERP**.

Au moment de créer (ou modifier) une annotation, vous pouvez utiliser le champ **"Lier à un élément"**.

### Cas d'usage : La Tâche de Chantier
Vous repérez un défaut sur le plan. Vous créez l'annotation et vous la liez à une "Tâche de Chantier" préalablement créée (ex: Tâche #45 "Reprise de la maçonnerie").
Lorsque l'ouvrier ouvrira la maquette, il verra l'annotation, et pourra cliquer dessus pour accéder directement aux détails de la Tâche (Titre, Statut : "En cours").

### Cas d'usage : L'Intervention de Maintenance (SAV)
Un client signale une panne. Le responsable crée une "Intervention". Il ouvre ensuite la maquette numérique du bâtiment, clique sur le composant en panne, crée une annotation et la lie à l'Intervention. Le technicien sur le terrain sait ainsi physiquement où se rendre.

> [!IMPORTANT]
> Les annotations servent de "marque-pages géographiques" pour vos processus métier. Ne vous contentez pas de décrire le problème en texte : liez l'annotation à un processus (Tâche, Intervention) pour suivre sa résolution (Statut).
