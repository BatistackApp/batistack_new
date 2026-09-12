---
title: Découpe Laser
icon: heroicon-o-bolt
order: 1
---

# ⚡ Module Découpe Laser

Bienvenue dans la documentation du module **Découpe Laser** de Batistack.

Ce module prend en charge toute l'activité de découpe : du chiffrage d'une pièce à la facturation, en passant par la commande, la livraison et les avoirs. Il est pensé pour les ateliers laser qui vendent des pièces découpées à façon.

## 📑 Que trouverez-vous dans ce module ?

Le module suit le cycle commercial de la découpe laser :

- [Les Matériaux Laser](./01-materiaux-laser.md) : votre référentiel de matières (acier, inox, alu…) avec densité et tarifs.
- [Les Devis Laser](./02-devis-laser.md) : chiffrage automatique selon les dimensions, l'épaisseur et le périmètre de découpe, avec **import de fichiers DXF**.
- [Les Commandes Laser](./03-commandes-laser.md) : transformation des devis acceptés en ordres de fabrication.
- [Les Bons de Livraison Laser](./04-bons-livraison-laser.md) : suivi des quantités réellement livrées.
- [Factures & Avoirs Laser](./05-factures-avoirs-laser.md) : facturation sur les quantités livrées et gestion des avoirs.

> [!TIP]
> **Un circuit sans double saisie**
> Chaque document se transforme automatiquement dans le suivant : **Devis → Commande → Bon de livraison → Facture**. Les quantités sont reprises d'une étape à l'autre pour éviter toute ressaisie et tout écart.

> [!NOTE]
> **Indépendance du cycle**
> Les documents laser (devis, commandes, factures…) vivent dans leur propre circuit et ne se confondent pas avec ceux du module Commerce classique.
