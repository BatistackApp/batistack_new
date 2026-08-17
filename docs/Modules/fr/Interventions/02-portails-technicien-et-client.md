---
title: Portails Technicien & Client
icon: heroicon-o-device-phone-mobile
order: 3
---

# 📱 Portails Technicien & Client

L'ERP ne se limite pas aux employés de bureau. Des portails dédiés simplifient la vie des techniciens et de vos clients.

## 1. L'Espace Technicien SAV

Vos techniciens terrain disposent d'un espace sécurisé et épuré (accessible sur tablette et smartphone).
- **Vue Restreinte** : Un technicien ne voit que les interventions qui lui sont assignées et planifiées.
- **Scanner QR Code** : Il peut utiliser l'appareil photo de son smartphone pour scanner une pièce détachée dans son camion et l'ajouter instantanément au ticket.
- **Signature Électronique** : À la fin des travaux, le technicien tend la tablette au client. Le client signe avec son doigt. La signature est **scellée cryptographiquement** au PDF du Bon de Travail (sans prix, pour ne pas gêner sur le terrain).
- *Sécurité : Toute modification de l'intervention après signature invalidera automatiquement cette dernière.*

### 1.1. Formulaires d'Intervention Dynamiques (Check-lists sur-mesure)

Adaptez chaque intervention à la réalité du terrain grâce à des **modèles de rapports dynamiques**. Plutôt qu'un formulaire rigide, vous définissez vous-même la liste des champs que le technicien devra obligatoirement renseigner avant de clôturer l'intervention.

#### Créer un modèle de rapport (côté administration)
Dans le groupe **Configuration**, rubrique **Modèles de Rapport d'Intervention** :
- **Nom & Description** : identifiez le modèle (ex. « Rapport SAV Climatisation »).
- **Type d'intervention** : choisissez **Régie** ou **Forfait** — un modèle ne s'applique qu'à un seul type.
- **Blocs de champs** : composez librement le formulaire en ajoutant des blocs, chacun avec un **nom technique** unique (minuscules, chiffres, underscore), un **libellé** affiché au technicien, et une case **Obligatoire** :
  - **Texte court** (TextInput)
  - **Texte long** (Textarea)
  - **Nombre** (avec min/max optionnels)
  - **Case à cocher** (Checkbox)
  - **Liste déroulante** (Select — saisissez une option par ligne)
  - **Date** (DatePicker)
  - **Photo / Fichier** (FileUpload acceptant images et documents, stocké sur le disque public)
- **Actif** : seul un modèle **actif** est proposé aux techniciens.

> [!NOTE]
> Pour un même type d'intervention, plusieurs modèles peuvent exister ; seul le plus récent modèle **actif** (ou celui explicitement lié à l'intervention) est appliqué.

#### Remplir le rapport (côté technicien)
- Sur l'intervention concernée, cliquez sur l'action **Remplir le rapport** (disponible lorsqu'un modèle actif correspond au type, même pour une intervention déjà terminée ou signée — le formulaire reste consultable, mais l'enregistrement est refusé si l'intervention n'est plus en cours ou déjà signée).
- La page dédiée affiche dynamiquement les champs définis dans le modèle, pré-remplis s'ils ont déjà été saisis.
- Enregistrez le rapport : le formulaire est sauvegardé sur l'intervention et le modèle est lié.

#### Contrôle à la clôture
- À la **clôture** (passage au statut *Terminée*), Batistack vérifie automatiquement que **tous les champs obligatoires** du rapport ont été renseignés.
- Si un champ obligatoire manque, la clôture est **bloquée** et le message liste les champs à compléter.
- Sans modèle actif, aucune contrainte n'est appliquée : la clôture reste libre.

## 2. Le Portail Client SAV (Espace Client)

Offrez un portail extranet professionnel à vos clients fidèles. Avec leurs propres identifiants, ils peuvent se connecter à un environnement sécurisé où ils ne voient que *leurs* données.

Depuis ce portail, le client peut :
- **Consulter son parc d'équipements** (historique des machines, numéros de série).
- **Suivre l'avancement** de ses tickets SAV en cours.
- **Déclarer une panne** en toute autonomie : Le client remplit un formulaire rapide qui crée instantanément un nouveau ticket (en mode Régie) dans votre ERP, vous faisant gagner un appel au standard téléphonique.
