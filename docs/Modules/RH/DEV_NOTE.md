# 👷 Module RH & Pointage

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modèles backend ultra-complets et validés à 100%. Cela inclut la gestion des Employés, Contrats, Temps pointés, Absences, et Visites médicales.
*   **Logique Métier :** Les "Services" et Observers calculent correctement la conformité RH et les heures.
*   **Intégrations :** Synergies renforcées avec Flottes (avertissements RH automatiques suite aux amendes routières) et Chantiers (notifications de dépassement budgétaire lié aux heures pointées).
*   **Tests :** **100% de succès** sur la suite de plus de 130 tests PestPHP. Toutes les fonctionnalités de base et avancées sont parfaitement fonctionnelles et sans anomalie.
*   **Alertes Automatisées :** Notifications push et emails automatiques en place (via `MedicalVisitReminderNotification`, `QualificationExpiringNotification`, et Jobs associés) envoyés avant l'expiration d'une certification, d'un CACES, ou d'une visite médicale.
*   **Frontend Filament & Terrain :** Interfaces de gestion des employés, formulaires de saisie, et pointeuse de SaisieHeureCollective fonctionnelles.
*   **Vues Calendrier :** Intégration d'un widget calendrier dynamique (via le plugin Guava Calendar) sur le panneau RH fusionnant les plannings de présence et les congés avec code couleur.
*   **Export Paie Automatisé :** Génération d'un export mensuel CSV des heures pointées (normales, trajet, GD) et absences validées depuis le panel Filament.
*   **Signature Électronique (DocuSeal) :** Intégration de l'API DocuSeal pour envoyer et suivre la signature électronique des contrats directement depuis l'historique Filament.
*   **Matrice de Polyvalence :** Tableau de bord dynamique permettant de consulter d'un seul coup d'œil la validité des habilitations (Q) et des équipements (E) de chaque employé avec un code couleur (Valide, Expirant, Expiré).
*   **Fiches de Paie Pro Forma :** Génération automatique d'un PDF estimatif du bulletin de salaire (Heures normales, majorations 25/50%, primes de grand déplacement).
*   **Pointeuse Biométrique (Kiosque) :** Implémentation d'un kiosque de pointage avec reconnaissance faciale (`face-api.js`) traitant l'image côté client, gestion du consentement RGPD, et création automatique des brouillons de saisie d'heures.

## 🚧 Ce qu'il reste à faire
*(Le module est fonctionnellement très abouti et couvre tous les besoins RH classiques et avancés. Il est en phase de maintenance/amélioration continue).*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Onboarding Digitalisé :** Mettre en place un espace candidat permettant aux nouveaux arrivants de remplir leurs informations (pièce d'identité, RIB, attestation sécurité sociale) avant de générer le contrat DocuSeal.
