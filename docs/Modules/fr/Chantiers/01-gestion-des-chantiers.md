---
title: Structuration et Planning
icon: heroicon-o-queue-list
order: 2
---

# 📅 Structuration et Planning (Gantt)

Un chantier bien préparé est un chantier à moitié gagné. Batistack propose une approche hiérarchique stricte pour modéliser vos projets.

## 1. La hiérarchie du Projet

Un projet dans Batistack respecte toujours l'arborescence suivante :
1. **Le Chantier** : Le dossier global (ex: "Construction Résidence Les Lilas"). Il porte le budget global, l'adresse, et le client.
2. **Les Phases** : Les grandes étapes temporelles (ex: "Gros Œuvre", "Second Œuvre", "Finitions").
3. **Les Tâches** : Les actions concrètes (ex: "Coulage Dalle RDC", "Pose placo 1er étage"). C'est au niveau de la Tâche que l'on affecte les ouvriers et que l'on suit le temps passé.

> [!WARNING]  
> Il n'est pas possible d'affecter un ouvrier ou du matériel directement sur un "Chantier". Vous devez obligatoirement créer au moins une Phase et une Tâche.

## 2. Le Planning Interactif (Diagramme de Gantt)

Batistack intègre un puissant widget Gantt interactif pour visualiser la progression du chantier dans le temps.

- **Glisser-Déposer (Drag & Drop)** : Vous pouvez décaler une tâche ou ajuster sa durée directement à la souris sur le graphique.
- **Dépendances** : Si la tâche B dépend de la tâche A, décaler la tâche A vers la droite repoussera automatiquement la tâche B et toutes les suivantes, évitant ainsi les erreurs de calendrier.

## 3. Le Planning des Ressources

Alors que le Gantt sert à planifier le *temps*, le **Planning des Ressources** (Resource Planner) sert à planifier les *hommes* et les *machines*.

Depuis ce tableau de bord spécifique, vous visualisez la disponibilité de vos équipes.
- **Éviter la surréservation** : Le système empêche (ou alerte) d'affecter un ouvrier sur deux chantiers différents le même jour.
- **Flotte automobile** : Vous planifiez également quel véhicule/engin (ex: Mini-pelle) est affecté à quelle tâche.
