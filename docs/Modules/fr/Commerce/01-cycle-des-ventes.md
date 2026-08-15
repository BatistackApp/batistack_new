---
title: Le Cycle des Ventes
icon: heroicon-o-presentation-chart-line
order: 2
---

# 📈 Le Cycle des Ventes (Clients)

Le cycle de vente dans Batistack est conçu pour fluidifier la transition de la prospection à la réalisation, sans aucune double saisie.

## 1. Le Devis et la Signature en Ligne

La création d'un Devis est la première étape. Vous pouvez ajouter des articles issus du catalogue ou créer des lignes libres.

> [!IMPORTANT]
> **Signature Numérique**
> Une fois le devis prêt, utilisez l'action "Envoyer pour signature". Le client recevra un e-mail avec un lien sécurisé vers un portail public pour signer électroniquement avec son doigt ou sa souris. 
> Dès que le client signe, le devis bascule automatiquement en statut `ACCEPTED`.

## 2. Conversion (Commande & Chantier)

Depuis un devis accepté, des boutons d'action rapide vous permettent de :
1. Générer une **Commande Client** (qui confirme juridiquement l'engagement).
2. Générer le **Chantier** (qui va initialiser le planning et le budget prévisionnel de vos équipes travaux).

## 3. Les Situations de Travaux (Facturation à l'avancement)

Dans le BTP, on facture rarement la totalité à la fin. Batistack gère nativement les **Situations de travaux**.

- Créez une nouvelle "Situation" liée à votre commande.
- Saisissez un pourcentage d'avancement (ex: "Gros Œuvre achevé à 100%, Second Œuvre à 30%").
- L'ERP génère la Facture de Situation en calculant automatiquement le reste à payer, en déduisant les acomptes et les situations précédentes.

## 4. Les Avenants (Travaux Supplémentaires)

En cours de chantier, le client demande souvent des **travaux supplémentaires (TS)**. Au lieu de rééditer un devis isolé, Batistack propose un **Devis d'Avenant** rattaché à une **commande principale**.

### Comment créer un avenant
Depuis la **fiche chantier** (bouton **« Créer un avenant »**) ou la **fiche commande**, un avenant est créé automatiquement avec :
- une référence dédiée (`AV-YYYY-NNN`),
- le rattachement à la commande principale et au chantier,
- un statut **Brouillon** pour saisir les lignes.

### Le cycle de l'avenant
1. **Saisissez les lignes** de travaux supplémentaires (articles ou lignes libres).
2. **Envoyez l'avenant** au client (demande de signature en ligne, comme un devis classique).
3. **À l'acceptation**, Batistack :
   - **ajoute les lignes de l'avenant à la commande principale**,
   - **rehausse automatiquement le budget total du chantier** (`budget_total_ht`),
   - bascule l'avenant en statut **Signé**.

> [!TIP]
> **Intégration aux situations**
> Comme les lignes de l'avenant deviennent des lignes de la commande, elles sont **automatiquement prises en compte dans les situations de travaux suivantes** (facturation à l'avancement) — sans ressaisie.

## 5. Légalisations et Avoirs

Toute **Facture** émise doit être légalisée. Une fois validée, la facture reçoit un numéro de séquence inaltérable (ex: F-2026-0089). 
En cas d'erreur de facturation, la loi interdit de supprimer une facture. Vous devrez utiliser le bouton "Créer un Avoir" (Credit Note) pour annuler comptablement la somme.
