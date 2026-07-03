# 👷 Module RH & Pointage

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modèles backend ultra-complets et validés à 100%. Cela inclut la gestion des Employés, Contrats, Temps pointés, Absences, et Visites médicales.
*   **Logique Métier :** Les "Services" et Observers calculent correctement la conformité RH et les heures.
*   **Intégrations :** Synergies renforcées avec Flottes (avertissements RH automatiques suite aux amendes routières) et Chantiers (notifications de dépassement budgétaire lié aux heures pointées).
*   **Tests :** **100% de succès** sur la suite de plus de 130 tests PestPHP. Toutes les fonctionnalités de base (Employés, Contrats, Pointages, Absences) et les synergies complexes sont parfaitement fonctionnelles et sans anomalie.

## 🚧 Ce qu'il reste à faire
*   **Frontend Filament :** Créer les interfaces de gestion des employés, les formulaires de saisie.
*   **Vues Calendrier :** Intégrer des widgets calendriers pour visualiser les plannings et les congés.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Pointeuse Mobile (PWA) :** Créer une interface simplifiée spécifique pour les smartphones, permettant aux chefs d'équipes de pointer la présence sur chantier via un scan de QR Code sur les badges employés ou par géolocalisation GPS.
2.  **Alertes Automatisées :** Mettre en place des notifications push (via WebPush) et des emails automatiques envoyés aux managers ou RH 30 jours avant l'expiration d'une certification, d'un CACES, ou d'une visite médicale.
