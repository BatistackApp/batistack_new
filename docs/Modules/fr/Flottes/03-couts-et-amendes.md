---
title: Coûts et Amendes
icon: heroicon-o-receipt-percent
order: 4
---

# 💶 Coûts, Carburant et Amendes

Gérer une flotte, c'est aussi gérer l'argent qu'elle coûte.

## 1. Import des Frais (Carburant et Télépéage)

Plutôt que de saisir chaque ticket de caisse de station-service manuellement, l'ERP est capable d'ingérer les fichiers consolidés de vos fournisseurs (TotalEnergies, DKV, Ulys).

1. Allez dans **Flotte > Import de Frais**.
2. Uploadez le fichier CSV de votre fournisseur.
3. L'ERP matche chaque transaction avec un véhicule grâce à la plaque d'immatriculation ou au numéro du badge.
4. Le coût est instantanément affecté au "Total Cost of Ownership (TCO)" du véhicule.

## 2. Le rapprochement avec la Comptabilité Analytique

Mais l'ERP ne s'arrête pas là. **Il cherche à refacturer ces coûts aux chantiers.**

Lorsqu'un plein de carburant de 80€ est importé le 15 Octobre à 14h pour la "Camionnette A", le système va chercher : 
*À quoi était affectée la Camionnette A le 15 Octobre à 14h ?*
S'il trouve une affectation liée au "Chantier Résidence Les Lilas", les 80€ sont automatiquement imputés en dépense sur le budget de ce chantier !

## 3. Gestion des Infractions (Amendes)

Lorsque vous recevez un avis de contravention (radar automatique), la loi vous oblige à désigner le conducteur ou à payer l'amende sur les deniers de l'entreprise.

Dans le menu **Amendes (Traffic Fines)**, créez une nouvelle infraction en indiquant la date, l'heure exacte et la plaque d'immatriculation.
> [!IMPORTANT]
> **Désignation Magique** : L'ERP va croiser la date et l'heure avec le registre des affectations. Il va instantanément retrouver qui était au volant à la minute de l'infraction et associer l'employé à l'amende. Le gestionnaire de flotte peut ensuite exporter le document pour dénoncer l'infraction auprès des autorités.
