---
title: Sécurité & Droits d'Accès
icon: heroicon-o-lock-closed
order: 3
---

# 🔒 Sécurité et Droits d'Accès

La protection de vos données commerciales et financières est une priorité absolue. Batistack gère les droits d'accès de manière très granulaire.

## 1. Rôles et Permissions (Filament Shield)

L'accès aux différents modules est régi par un système de Rôles (ex: "Directeur", "Chef de Chantier", "Comptable").
Chaque rôle possède des Permissions extrêmement fines. Par exemple, vous pouvez autoriser un "Chef de Chantier" à *voir* les devis, mais lui interdire d'en *créer* ou d'en *supprimer*.

## 2. Synchronisation magique avec les Ressources Humaines (RH)

Dans beaucoup d'entreprises, lorsqu'un employé change de poste, l'informatique oublie de mettre à jour ses droits d'accès. Pas dans Batistack !

L'ERP lie automatiquement le **Rôle informatique** à l'**Intitulé du poste (Contrat RH)**.
- Lorsqu'un ouvrier est promu "Chef de Chantier" dans le module RH, ses accès logiciels sont mis à jour le jour même.
- **Révocation automatique** : À la date de fin d'un contrat (CDD, fin de période d'essai), une tâche planifiée nocturne révoque instantanément les droits d'accès de l'utilisateur. Aucun risque de fuite de données par un ancien employé.

## 3. La Piste d'Audit (Activity Log)

"Qui a supprimé cette ligne sur le devis ?"
Cette question trouve toujours sa réponse dans Batistack. L'outil intègre une traçabilité globale (Piste d'audit).

- Chaque création, modification ou suppression sur un document sensible (Devis, Facture, Chantier) est enregistrée.
- En ouvrant un document, vous trouverez un onglet **Historique (Timeline)** qui liste chronologiquement : qui a fait l'action, à quelle heure, et quelles étaient les anciennes vs nouvelles valeurs.
