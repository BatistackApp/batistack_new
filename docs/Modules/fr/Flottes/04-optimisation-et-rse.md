---
title: Optimisation IA & Bilan RSE
icon: heroicon-o-globe-europe-africa
order: 5
---

# 🌍 Optimisation des Trajets & Bilan Carbone (RSE)

Batistack n'est pas qu'un outil de suivi, c'est aussi un outil d'aide à la décision pour réduire vos coûts opérationnels et votre impact environnemental.

## 1. L'Optimisation des Trajets (Routing Maps IA)

Le matin, vous avez 10 véhicules au dépôt et 10 chantiers actifs dispersés dans la région. Quel véhicule affecter à quel chantier pour minimiser les kilomètres parcourus ?

L'interface d'**Optimisation des Trajets** vous mâche le travail :
1. Elle interroge l'API Google Maps Distance Matrix.
2. L'algorithme croise la position de départ (Dépôt ou domicile du salarié) avec les adresses des chantiers du jour.
3. Le système vous propose une grille d'affectation optimale (ex: "Affecter le Fourgon C au Chantier Nord").
4. Vous validez en un clic, et les conducteurs reçoivent leurs ordres de mission sur leur smartphone.

Résultat : Moins de temps perdu dans les bouchons, et des économies massives de carburant.

## 2. Le Bilan Carbone (Rapports RSE)

Dans le secteur du BTP, le respect des normes environnementales et la comptabilité carbone (RSE) deviennent obligatoires pour répondre aux appels d'offres publics.

Batistack automatise ce processus fastidieux :
- Grâce à l'import de vos factures de carburant, l'ERP connaît le volume (en Litres) consommé.
- Grâce au référentiel des véhicules, il connaît la motorisation (`fuel_type` : Diesel, Essence, Hybride).
- Le rapport **Bilan Carbone** applique automatiquement les coefficients de conversion officiels pour transformer ces litres en **Tonnes d'équivalent CO2 (tCO2e)**.

> [!TIP]
> **Le détail qui tue** : Puisque le carburant est réconcilié avec les chantiers, vous êtes capable de dire à votre client : *"Le chantier de votre résidence a généré exactement 2.4 Tonnes de CO2 liées au transport"* !
