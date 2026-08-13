---
title: Campagnes d'E-Mailing
icon: heroicon-o-paper-airplane
order: 3
---

# Campagnes d'E-Mailing (CRM Marketing)

Batistack intègre un outil de publipostage complet permettant d'envoyer des communications groupées à l'ensemble de vos Tiers (Clients, Fournisseurs, Sous-traitants). Ce module est idéal pour :
- Diffuser des informations importantes (fermetures exceptionnelles, vœux).
- Promouvoir de nouvelles offres commerciales.
- Effectuer des rappels de maintenance.

## Création d'une Campagne

1. Naviguez dans le menu **Tiers** > **Campagnes**.
2. Cliquez sur **Nouvelle Campagne**.
3. Renseignez le **Nom interne**, l'**Objet** et rédigez votre contenu à l'aide de l'éditeur de texte enrichi (Rich Text).
4. Vous pouvez (optionnellement) définir une **Date de planification** pour un envoi en différé.
5. Cliquez sur **Créer**.

## Génération de la Cible (Destinataires)

Une fois la campagne créée, vous accédez à sa vue détaillée. En bas de page se trouve la liste des destinataires.

1. Cliquez sur le bouton **Générer la cible**.
2. Une fenêtre s'ouvre : sélectionnez les **Types de tiers** (ex: `Clients` et/`ou` `Fournisseurs`) que vous souhaitez cibler.
3. Cliquez sur **Valider**. L'ERP scannera automatiquement la base de données et ajoutera tous les contacts associés à ces tiers disposant d'une adresse email valide.

Vous avez la possibilité d'ajouter des destinataires manuellement via **Ajouter manuel**, ou de retirer certains destinataires de la liste générée (grâce au bouton de suppression en bout de ligne) avant d'envoyer la campagne.

## Planification et Envoi

Lorsque votre campagne et votre liste de destinataires sont prêtes :

1. Cliquez sur l'action **Planifier l'envoi** en haut à droite.
2. Le statut de la campagne passera de `Brouillon` à `Planifié`.

*L'envoi sera exécuté de façon invisible (en arrière-plan) à la date demandée, ou immédiatement si aucune date n'a été spécifiée.*

Vous pourrez suivre l'état de chaque destinataire directement dans la liste :
*   **En attente** : L'email n'a pas encore été expédié.
*   **Envoyé** : L'email est parti avec succès.
*   **Échoué** : Une erreur (adresse introuvable, blocage serveur) s'est produite (les détails sont affichés dans la colonne 'Erreur').
