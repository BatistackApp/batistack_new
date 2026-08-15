---
title: Suivi Analytique & DOE
icon: heroicon-o-presentation-chart-line
order: 4
---

# 📊 Suivi Analytique & Génération du DOE

Le véritable enjeu d'un ERP de BTP n'est pas seulement de construire, mais de savoir si la construction est rentable.

## 1. La Comptabilité Analytique

Chaque chantier possède son propre "compte de résultat" analytique en temps réel.
Au lieu d'attendre la fin de l'année pour que le comptable calcule la rentabilité, Batistack compile les coûts au jour le jour :

- **Main d'œuvre** : Le système croise les heures pointées dans les *Journaux de Chantier* avec le taux horaire chargé de chaque employé (issu du module RH).
- **Matériaux** : Les bons de consommation (Module Articles) lient le coût d'achat des matériaux au chantier.
- **Matériel lourd & Flotte** : L'immobilisation d'un engin (ex: Grue) est facturée analytiquement au chantier selon son temps de présence (amortissement calculé par l'IoT et le module Flottes).

> [!NOTE]  
> Le Dashboard du Directeur de Travaux affiche en direct la *variance* entre le budget prévisionnel (issu du devis) et les coûts réels engagés. L'alerte se déclenche **pendant** le chantier, et non après !

### 1.1 Suivi Bancaire du chantier

Depuis le module **Banque**, chaque transaction bancaire (encaissement ou décaissement) peut être **affectée à un Chantier**. Une fois affectée, elle alimente en temps réel le suivi de rentabilité du chantier :

- **Encaissements (Banque)** : somme des crédits affectés au chantier.
- **Décaissements (Banque)** : somme des débits affectés au chantier.
- **Trésorerie nette (Banque)** : encaissements − décaissements.

Ces indicateurs sont visibles sur la fiche chantier (widget *ChantierFinancialOverview*) et dans le **Bilan Analytique PDF**. Ils viennent **en complément** de la marge analytique (budget − coûts), qui reste basée sur les documents (MO, matériaux, flotte, achats...).

Pour affecter une transaction : ouvrez la **Ligne Bancaire** dans le module Banque → champ **Chantier** (recherchable).

## 2. Le Dossier des Ouvrages Exécutés (DOE)

À la fin d'un chantier, la remise du DOE au client est obligatoire. C'est souvent une corvée administrative de plusieurs jours.

Grâce à Batistack, le DOE est **compilé automatiquement** en un clic. 
Le système va :
1. Rassembler toutes les fiches techniques PDF des matériaux utilisés (récupérées dans le catalogue Articles).
2. Compiler les plans validés (Vision 3D).
3. Intégrer les rapports de réception (Checklists).
4. Générer un PDF global, chapitré et structuré, prêt à être envoyé au Maître d'Ouvrage.
