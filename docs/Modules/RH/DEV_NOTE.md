# 👥 Module RH & Pointage

## 📌 Vue d'ensemble du Module
Le module **Ressources Humaines (RH)** est l'un des piliers centraux de Batistack. Il couvre de bout en bout le cycle de vie des employés : de l'onboarding digitalisé au pointage (classique ou biométrique), en passant par la gestion des absences, la conformité légale (visites médicales, CACES) et la consolidation des variables de paie et notes de frais.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/RH` & `app/Enums/RH`)
*   **Référentiel Salariés** : `Employee`, `Contract` (avec Types de contrats), `Equipement` (matériel confié).
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
*   **Notes de Frais & OCR** : Workflow complet de soumission. Moteur OCR (`GoogleCloudVisionOcrService`) intégré pour l'extraction automatique des montants et catégorisation depuis les tickets.
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
*   **Export Comptable & SEPA** : Générer automatiquement un fichier de virement SEPA pour le remboursement groupé des notes de frais validées.
*   **Rapprochement Bancaire (Cartes Corpo)** : Intégrer une API (Bridge/Plaid) pour réconcilier automatiquement les dépenses des cartes "Corporate" de l'entreprise avec les tickets scannés par les salariés.
*   **OCR Multi-Pages & PDF** : Étendre le support OCR pour traiter les factures PDF multi-pages, et pas uniquement les photos JPEG de tickets.
*   **Avances sur Frais** : Permettre aux salariés de demander une avance budgétaire pour un grand déplacement à venir, avec suivi et déduction automatique lors de la saisie de la note de frais finale.
