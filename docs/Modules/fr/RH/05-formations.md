---
title: Formations & Suivi OPCO
icon: heroicon-o-academic-cap
order: 5
---

# 🎓 Formations & Suivi OPCO

Le module de formation permet de planifier les sessions (en interne ou en centre), de suivre le budget et les prises en charge, et surtout de **renouveler automatiquement** les habilitations des salariés ayant réussi la formation.

## 📌 Organisation d'une session

Une session (`TrainingSession`) contient les informations suivantes :
- **Détails & Dates** : Titre, date de début, date de fin, statut (Planifiée, En cours, Terminée).
- **Budget & OPCO** : Coût global de la formation, montant pris en charge par l'OPCO, et statut du dossier de remboursement.
- **Qualification visée (Optionnel)** : Si la session donne lieu à l'obtention ou au renouvellement d'une certification (ex: CACES), vous pouvez renseigner le type de certification (ex: CACES R489) et sa durée de validité en mois.

## 👥 Gestion des participants

Depuis l'interface de la session, vous pouvez attacher des participants et suivre leur statut de passage :
- `Inscrit`
- `Présent`
- `Absent`
- `Validé / Obtenu`
- `Échoué`

## 🔄 Renouvellement automatique (Clôture)

L'intérêt principal du module réside dans son automatisation finale. 

Depuis la liste des formations, cliquez sur l'action **"Clôturer la session"**. 
Le système va alors :
1. Changer le statut de la session à "Terminée".
2. Parcourir tous les participants inscrits à la session.
3. Pour ceux ayant le statut **Validé / Obtenu**, une nouvelle ligne d'habilitation sera ajoutée à leur dossier RH avec pour date d'obtention la fin de la formation et une date d'expiration automatiquement calculée grâce à la "Durée de validité" configurée.

*(Note: un nouveau record d'habilitation est créé plutôt que d'écraser l'ancien, afin de conserver l'historique complet des recyclages du salarié).*
