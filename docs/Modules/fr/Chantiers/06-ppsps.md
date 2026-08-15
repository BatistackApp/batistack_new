---
title: PPSPS & Sécurité (Génération automatique)
icon: heroicon-o-shield-check
order: 7
---

# 🛡️ Plan Particulier de Sécurité et de Protection de la Santé (PPSPS)

Le **PPSPS** est le document de prévention qui organise la sécurité sur un chantier où plusieurs entreprises ou corps de métier interviennent. Batistack le **génère automatiquement** en croisant les données déjà saisies dans l'ERP :

- les **tâches prévues** (planning par phase),
- le **matériel alloué** au chantier,
- les **fiches de données de sécurité (FDS)** renseignées sur vos produits.

L'objectif : produire un document complet **sans ressaisie**, où les **risques sont déduits des produits utilisés**.

---

## 1. Prérequis : renseigner la fiche de sécurité des produits

La génération repose sur les **fiches de sécurité** de vos articles. Pour qu'un produit soit pris en compte, renseignez son onglet **« Sécurité & FDS »** dans la fiche produit (module **Articles & Stocks**) :

- **Catégorie de danger** (ex. Inflammable, Corrosif, Toxique, Cancérogène…),
- **Pictogrammes CLP** (GHS01 à GHS09),
- **Phrases de danger (H)** et **phrases de précaution (P)** — saisie libre,
- **Date de mise à jour de la FDS** et pièce jointe **PDF** facultative.

> [!NOTE]
> Seuls les produits **dangereux** (ayant au moins une catégorie de danger, un pictogramme ou une phrase H) alimentent l'analyse des risques du PPSPS.

## 2. Générer le PPSPS

Sur la **fiche chantier** ou dans la **liste des chantiers**, ouvrez le menu **Impressions** puis cliquez sur **« PPSPS (Sécurité) »**.

Le document PDF est généré instantanément et téléchargé. Il contient 6 sections :

1. **Identité et intervenants** — chantier, maître d'ouvrage, entreprise, personnel (avec **visites médicales** et **habilitations**), sous-traitants.
2. **Planning et phases** — le découpage en phases et tâches avec périodes et avancement.
3. **Matériel alloué** — la liste du matériel affecté au chantier.
4. **Produits utilisés et FDS** — les produits avec leur catégorie de danger, pictogrammes et phrases H/P.
5. **Analyse des risques par phase** — les risques **déduits automatiquement** des produits de chaque phase, plus la synthèse des risques du chantier.
6. **Mesures de prévention** — les **protections collectives** et les **EPI** recommandés, déduits des risques identifiés.

## 3. Comment les risques sont-ils déduits ?

Chaque **catégorie de danger** est traduite en un ou plusieurs **types de risques** :

| Danger produit | Risque déduit |
|----------------|---------------|
| Explosif | Explosion |
| Inflammable / Comburant | Incendie |
| Corrosif | Brûlure / corrosion |
| Toxique / Nocif | Intoxication |
| Cancérogène | Santé à long terme |
| Sensibilisant | Allergie / sensibilisation |
| Gaz sous pression | Projection / explosion |
| Dangereux pour l'environnement | Pollution |

Pour chaque risque, le document propose automatiquement les **EPI** et **mesures de protection collective** associés (ex. produit corrosif → gants chimiques, lunettes étanches, douche de sécurité, bac de rétention).

## 4. Conseils

- **Renseignez systématiquement la catégorie de danger** de vos produits sensibles pour une analyse fiable.
- Le **matériel alloué** au chantier provient des **stocks du chantier** et des **ressources affectées aux tâches** : plus votre planning est complet, plus le PPSPS est précis.
- Les **visites médicales** et **habilitations** du personnel sont reprises depuis le module **RH** ; une habilitation expirée est signalée en rouge dans le document.

> [!TIP]
> La sécurité est un processus vivant : pensez à **mettre à jour la date de votre FDS** et vos pictogrammes à chaque changement de fournisseur ou de composition du produit.