# 🤝 Module Tiers (CRM : Clients, Fournisseurs, Sous-Traitants)

## 📌 Vue d'ensemble du Module
Le module **Tiers** est le cœur de la relation externe de Batistack. Il centralise toutes les informations relatives aux Clients, Fournisseurs, et Sous-Traitants. Il assure le suivi documentaire légal (Kbis, attestations) et l'intégration de portails B2B dédiés.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Tiers` & `app/Enums/Tiers`)
*   **Référentiel Tiers** : `ThirdParty` (Tiers général), `Contact`, `Address`, `Category`.
*   **Appels d'Offres** : `Consultation`, `ConsultationOffer`.
*   **Documents de Conformité** : `ThirdPartyDocument` (Kbis, Attestation URSSAF, Décennale).
*   **Enums** : `ThirdPartyType`, `ThirdPartyDocumentType`, `ThirdPartyDocumentStatus`.

### 2. Logique Métier & Services (`app/Services/Tiers`)
*   **Intégration APIs Publiques** : 
    *   `PappersService` / `Core\SirenService` pour récupérer les données INSEE.
    *   `VigilanceService` pour l'évaluation financière et de solvabilité en temps réel (via `recherche-entreprises.api.gouv.fr`) afin de détecter les liquidations ou redressements judiciaires et afficher des alertes visuelles.
*   **Scoring** : `SupplierScoringService` (Scoring Fournisseur basé sur la qualité, délais, litiges).
*   **Gestion Documentaire** : `TiersDocumentService` centralise les documents obligatoires avec suivi des expirations (notifications automatisées à J-30 et J-7).
*   **Signature Électronique (Sous-Traitants)** : Intégration avancée de la signature électronique pour les devis et les marchés de sous-traitance afin de contractualiser plus rapidement avec les partenaires externes (Issue #146).
*   **Portail d'Appels d'Offres Privé** : Publication de consultations pour les sous-traitants qui peuvent soumettre leurs offres chiffrées via leur portail.

### 3. Observers & Événements (`app/Observers/Tiers`)
*   Multiples Observers (`ThirdPartyObserver`, `ContactObserver`, `AddressObserver`) pour synchroniser les données externes et vérifier la conformité (ex: déclencher une alerte si un Kbis expire).

### 4. Interface Utilisateur (Filament & Portails)
*   **Administration** : Les fondations du Panel Filament sont complètes (`ThirdPartyResource`, vues Infolist massives offrant une vue 360° financière et opérationnelle).
*   **Portails B2B** : 
    *   **SubcontractorPanel** (Sous-traitants) : Gestion des consultations, visualisation des tâches de chantiers assignées avec mise à jour de l'avancement, et upload de factures de situation (PDF via Spatie MediaLibrary) (Issue #223).
    *   **CustomerPanel** (Clients) : Espace sécurisé pour les clients finaux.
*   **Dashboard CRM (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher les alertes de conformité (Detail List), l'acquisition client (Variance), la qualité de la base (Goal) et la répartition du portefeuille (Composition).

### 5. Tests
*   Suite ultra-complète validée à 100% (plus de 172 tests PestPHP).

## 🚧 Ce qu'il reste à faire
*   La base du module est terminée et extrêmement complète. Aucune amélioration prioritaire n'est requise dans l'immédiat.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Collecte Automatique des Documents Légaux** : Connecter l'ERP à une API légale tierce (e-Attestations ou Provigis) pour télécharger et mettre à jour automatiquement les Kbis et attestations URSSAF des sous-traitants, annulant le besoin de relance manuelle.
*   **Module d'E-Mailing et Campagnes** : Création d'un outil de publipostage intégré pour cibler la base de données Contacts B2B (ex: informations de fermeture annuelle aux sous-traitants, offres de maintenance aux clients).
