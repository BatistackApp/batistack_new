# DevNote - Module Paie

## Ce qui a été fait
- **Base de données & Modèles** :
  - Création des tables et modèles pour les profils de cotisation (`PayrollContributionProfile`) et les taux de cotisations associés (`PayrollContributionRate`).
  - Création des tables et modèles pour les acomptes (`AdvancePayment`).
  - Création des tables et modèles pour les fiches de paies (`Payslip`) et leurs lignes détaillées (`PayslipLine`).
- **Logique de calcul (PayrollCalculationService)** :
  - Calcul dynamique du salaire brut, des parts salariales et patronales.
  - Calcul exact de la base CSG (avec réintégration de la prévoyance et mutuelle) et gestion de la CSG déductible vs non-déductible.
  - Calcul du Prélèvement à la Source (PAS) et du Net à Payer.
  - Déduction et rattachement automatiques des acomptes validés (`paid`).
  - Validation du moteur de calcul via un test unitaire certifiant l'exactitude du Net Payé (au centime près) par rapport à un vrai bulletin BTP.
- **Génération PDF (PayslipPdfService)** :
  - Intégration de la vue `pdf.payslip` pour la modélisation visuelle du bulletin.
  - Génération des documents via `Browsershot` (via le `DocumentService` global du projet).
- **Interface Utilisateur (Filament)** :
  - Création du `PaiePanelProvider` dédié à la paie.
  - Ressources pour configurer les taux de cotisations, gérer les acomptes et consulter/générer les fiches de paie.

## Ce qu'il reste à faire
- **Action de génération de paie en masse** : Ajouter la possibilité dans Filament de générer les fiches de paie de l'ensemble des employés actifs d'un seul clic pour un mois donné.
- **Export Comptable** : Générer le fichier d'écritures comptables (OD de paie) mensuel post-paie à envoyer au logiciel comptable ou à traiter dans l'ERP.
- **DADS / DSN** : Préparer la structure des données de paie pour faciliter l'export DSN (Déclaration Sociale Nominative).
- **Envoi des fiches de paies** : Ajouter une fonctionnalité pour envoyer directement les PDF générés par email aux salariés ou dans un coffre-fort numérique sécurisé.

## Idées d'implémentation ou d'amélioration future
- **Gestion des congés payés et des absences** : Interfacer le module RH (absences, maladies) directement avec la paie pour déduire ou indemniser automatiquement les jours d'absence sur le bulletin.
- **Heures Supplémentaires / Majorations** : Intégrer les données du module pointage chantier pour remonter automatiquement les heures supplémentaires, de nuit, et les primes de grand déplacement.
- **Simulation de paie** : Créer un outil permettant à l'employeur de simuler une paie (Brut -> Net ou Net -> Brut) pour prévoir le coût d'une embauche ou d'une augmentation.
- **Versionnement des Taux de Cotisation** : Les taux (Sécurité Sociale, Chômage) évoluent souvent au 1er Janvier. Il serait pertinent d'ajouter des dates de validité (`valid_from`, `valid_to`) sur les `PayrollContributionRate` pour conserver l'historique des taux et recalculer correctement les paies passées sans dupliquer les profils complets.
