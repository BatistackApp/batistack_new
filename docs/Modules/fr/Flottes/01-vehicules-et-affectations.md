---
title: Véhicules et Affectations
icon: heroicon-o-key
order: 2
---

# 🔑 Véhicules et Affectations

La gestion rigoureuse d'une flotte commence par savoir exactement quels véhicules l'entreprise possède, et à qui ils sont confiés.

## 1. Le Référentiel des Véhicules

Le menu **Véhicules** centralise toute la donnée statique :
- La plaque d'immatriculation, la marque, le modèle.
- La motorisation (`fuel_type` : Diesel, Électrique, Hybride) indispensable pour le calcul RSE ultérieur.
- Le kilométrage initial.

Vous y trouverez également le **Contrat** lié au véhicule, avec ses échéances de leasing, sa date de fin, et son plafond kilométrique. Le Dashboard alertera le gestionnaire de parc si un véhicule s'approche de sa limite de kilométrage de leasing pour éviter les pénalités du loueur.

## 2. Les Affectations (Assignations)

Dans Batistack, un véhicule ne "disparaît" jamais. Lorsqu'il n'est pas au dépôt, il doit obligatoirement être affecté à un conducteur (employé du module RH).

> [!CAUTION]  
> **Contrôle du Permis de Conduire**
> L'ERP bloque informatiquement l'affectation d'un véhicule si le permis de conduire de l'employé (stocké dans le module RH) est invalide, expiré, ou ne correspond pas à la catégorie du véhicule (ex: tentative d'affecter un Poids Lourd à un détenteur du permis B). C'est une sécurité juridique majeure pour l'entreprise.

Une fois affecté, la date et l'heure de début sont enregistrées. L'employé devient légalement responsable du véhicule.
