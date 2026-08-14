---
title: Quantitatifs & Achats (BOM)
icon: heroicon-o-shopping-cart
order: 4
---

# 🛒 Quantitatifs (BOM) & Génération de Commandes

Grâce à la passerelle entre la **Vision 3D**, le **module Articles** et les **Achats**, vous pouvez définir les quantités de matériaux nécessaires à la réalisation d'une maquette (la nomenclature, ou *BOM*), puis générer automatiquement un **bon de commande fournisseur** en tenant compte de votre stock actuel.

> [!NOTE]
> Cette version gère la saisie **manuelle** des quantitatifs. L'extraction automatique des quantités depuis le fichier IFC est une amélioration prévue ultérieurement.

## 📦 1. Saisir les quantitatifs d'une maquette

1. Ouvrez la **maquette** concernée (depuis le module **Vision 3D** ou l'onglet **Maquettes 3D & Plans** d'un chantier).
2. Dans l'onglet **Quantitatifs (BOM)**, cliquez sur **Nouveau** pour ajouter une ligne :
   - **Article** : sélectionnez l'article du catalogue (Articles & Stocks) correspondant au matériau.
   - **Élément (maquette)** (optionnel) : précisez le nom de l'élément BIM associé, à titre informatif.
   - **Unité** (optionnel) : ex. `m`, `m²`, `m³`, `u`, `kg`.
   - **Quantité requise** : la quantité totale nécessaire à la maquette.
3. Répétez l'opération pour chaque matériau de la maquette. Plusieurs lignes peuvent référencer le même article (les quantités sont alors additionnées).

## 🧮 2. Comment est calculé le besoin à commander ?

Lors de la génération du bon de commande, Batistack calcule pour chaque article :

- le **besoin brut** : la somme des quantités requises dans la BOM de la maquette ;
- le **stock physique** actuel (tous dépôts confondus) ;
- le **stock en commande** : les quantités déjà présentes dans des commandes fournisseurs non clôturées (Brouillon, Confirmé, Livré partiellement).

Le logiciel ne commande que la **différence** lorsque celle-ci est positive :

```
À commander = Besoin brut − Stock physique − Stock en commande
```

Un article déjà couvert par le stock n'apparaît pas dans le bon de commande.

## 📄 3. Générer le bon de commande

1. Ouvrez la maquette et cliquez sur **Générer le bon de commande** (en haut de la fiche).
2. Une fenêtre de récapitulatif s'affiche : pour chaque article, vous voyez le **besoin brut**, le **stock disponible**, le **stock en commande** et la **quantité à commander**.
3. Confirmez. Batistack crée alors un **bon de commande brouillon** dans le module **Achats → Commandes Fournisseurs**, puis vous y redirige.

Quelques points à connaître :

- Les commandes sont **groupées par fournisseur** (selon le fournisseur renseigné sur chaque article).
- Le bon de commande est rattaché au **chantier** porteur de la maquette, avec un prix unitaire basé sur le **prix d'achat** de l'article.
- Un article **sans fournisseur** renseigné est ignoré (une alerte est tracée côté technique).
- Si le stock couvre déjà tout le besoin, aucun bon de commande n'est créé.

## 🔁 4. Relancer après modification

La génération est **idempotente** : si vous relancez après avoir modifié la BOM ou reçu de la marchandise, Batistack **met à jour** le bon de commande brouillon existant (référence `PO-BIM-...`) au lieu d'en créer un doublon. Vous pouvez donc ajuster vos quantitatifs et relancer à tout moment avant validation.