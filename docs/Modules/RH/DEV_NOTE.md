# 👥 Module RH & Pointage

## 📌 Vue d'ensemble du Module
Le module **Ressources Humaines (RH)** est l'un des piliers centraux de Batistack. Il couvre de bout en bout le cycle de vie des employés : de l'onboarding digitalisé au pointage (classique ou biométrique), en passant par la gestion des absences, la conformité légale (visites médicales, CACES) et la consolidation des variables de paie et notes de frais.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/RH` & `app/Enums/RH`)
*   **Référentiel Salariés** : `Employee`, `Contract` (avec Types de contrats), `Equipement` (matériel confié avec gestion du coût d'immobilisation journalier).
*   **Temps & Activité** : `TimeEntry` (Pointage), `Abscence` (Congés/Maladie).
*   **Notes de Frais** : `ExpenseReport`, `ExpenseItem`.
*   **Conformité & Santé** : `MedicalVisit`, `Qualification` (Habilitations, CACES).
*   **Paie & Social** : `PayrollExport`, `PayrollVariable`, `CibtpDeclaration`.
*   **Enums strictes** : Validation des types (`AbsenceType`, `ContractType`, `CacesSymbol`, `ExpenseItemStatus`, etc.).

### 2. Logique Métier & Services (`app/Services/RH`)
*   **Gestion Sociale Avancée** :
    *   **CIBTP** : `CibtpService` automatise l'export DNA et les Demandes De Congés (DDC).
    *   **Subrogation** : Calcul automatique des Indemnités Journalières lors d'un arrêt, génération de l'attestation de salaire PDF.
    *   **Affiliation** : Génération du bulletin PRO BTP automatique post-onboarding.
*   **Paie & Temps** : `PayrollGenerationService` consolide les variables de paie en fin de mois (heures de base, heures supplémentaires, absences, primes). Génération d'un export mensuel CSV des heures et de Fiches de Paie Pro Forma (estimatives).
*   **Notes de Frais & OCR** : Workflow complet de soumission. Moteur OCR (`GoogleCloudVisionOcrService`) intégré pour l'extraction automatique des montants et catégorisation depuis les tickets. Support natif des factures PDF multi-pages (Issue #144).
    *   Gestion du moyen de paiement (Carte Personnelle ou Carte Corporate). Les dépenses par carte corpo sont automatiquement exclues du montant à rembourser au salarié (Issue #143).
    *   **Avances sur Frais** : Les salariés peuvent demander des avances budgétaires (`ExpenseAdvance`), qui sont virées via SEPA. Lors de la saisie de la note de frais finale, l'avance est automatiquement déduite du reste à payer (Issue #145).
*   **Export SEPA** : Génération automatique de fichiers de virement SEPA (pain.001.001.03) pour le remboursement groupé des notes de frais validées et le paiement des avances sur frais (Issue #142).
*   **Signature Électronique** : API DocuSeal intégrée pour les contrats.

### 3. Observers & Événements (`app/Observers/RH`)
*   **Conformité et Alertes** : Observers (`MedicalVisitObserver`, `QualificationObserver`) couplés à des Jobs qui envoient des notifications push et emails avant l'expiration d'une certification ou d'une visite médicale.
*   **Synergies** : Actions croisées avec les modules Flottes (amendes) et Chantiers (dépassement budgétaire lié aux heures).

### 4. Interface Utilisateur (Filament & Kiosques)
*   **Panel RH Filament** : Interface de gestion complète des employés, formulaires de saisie, matrice de polyvalence dynamique (validité des habilitations avec code couleur). Intégration d'un Calendrier fusionnant présences et congés.
*   **Lecteur de Code-barres** : Intégration de `filament-barcode-scanner-field` pour assigner et tracer l'équipement.
*   **Kiosque Biométrique** : Saisie des heures via une pointeuse tablette avec reconnaissance faciale (`face-api.js`), traitement local, gestion RGPD et brouillons automatiques.
*   **Onboarding Digitalisé** : Espace candidat autonome pour le dépôt des pièces justificatives avant édition du contrat.

### 5. Tests
*   **100% de succès** sur la suite massive de plus de 130 tests PestPHP. Toutes les fonctionnalités de base et avancées (y compris l'analytique et l'OCR) sont couvertes.

## 🚧 Ce qu'il reste à faire
*   Le module est fonctionnellement très abouti et couvre tous les besoins RH classiques et avancés. Il est en phase de maintenance/amélioration continue.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Intégration DSN complète** : Déclaration Sociale Nominative via API net-entreprises.
*   **Refonte du Dashboard Ressources Humaines (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher le volume d'heures supplémentaires (Variance), la conformité légale CACES/Médicale (Goal), la répartition des contrats (Composition) et les demandes en attente (Detail List).
