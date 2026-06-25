# ItemsPlus

Plugin **PocketMine-MP 5** permettant de créer, enregistrer, modifier et distribuer des items, outils, armures, blocs et minerais personnalisés directement en jeu.

Le plugin regroupe les anciens systèmes **ItemsPlus** et **MineraisPlus** dans une seule extension et utilise **Customies** pour enregistrer le contenu personnalisé.

## Informations

| Élément    | Valeur                               |
| ---------- | ------------------------------------ |
| Nom        | ItemsPlus                            |
| Version    | `2.4.5-block-placement-rollback-fix` |
| Auteur     | **Nest**                             |
| API        | PocketMine-MP `5.0.0`                |
| Dépendance | Customies                            |
| Chargement | `STARTUP`                            |

## Fonctionnalités

* Création d’items personnalisés depuis une interface en jeu.
* Création d’épées, pioches, haches, pelles et houes.
* Création de casques, plastrons, jambières et bottes.
* Création de blocs normaux personnalisés.
* Création et génération de minerais personnalisés.
* Modification et suppression du contenu depuis une interface en jeu.
* Configuration de la durabilité, des dégâts, de la vitesse de minage et du niveau de récolte.
* Configuration de la défense et de la résistance des armures.
* Gestion des drops, des filons et des hauteurs de génération des minerais.
* Classement automatique des items dans les catégories du menu créatif.
* Animation vanilla des outils lorsqu’ils sont utilisés en main.
* Correctif de pose des blocs empêchant les rollbacks et les blocs fantômes.
* Compatibilité avec les anciens contenus Tank, Nexium, Azurite et Auralite.

## Prérequis

* Un serveur **PocketMine-MP 5**.
* Le plugin **Customies** compatible avec la version du serveur.
* Un pack de ressources contenant les textures et les modèles des items et blocs personnalisés.

## Installation

1. Arrêtez complètement le serveur.
2. Installez `ItemsPlus.phar` dans le dossier `plugins/`.
3. Installez également **Customies** dans `plugins/`.
4. Conservez ou installez le pack de ressources contenant les textures personnalisées.
5. Supprimez les anciens plugins séparés `ItemsPlus` et `MineraisPlus` afin d’éviter les conflits d’identifiants et de commandes.
6. Redémarrez complètement le serveur.

La configuration principale est créée dans :

```text
plugin_data/ItemsPlus/config.yml
```

> Après la création, la modification ou la suppression d’un contenu Customies, un arrêt puis un redémarrage complet du serveur est nécessaire.

## Commandes

### `/itemsplus`

Commande principale permettant de lister, donner et équiper les items personnalisés.

| Commande                                     | Description                                      |
| -------------------------------------------- | ------------------------------------------------ |
| `/itemsplus`                                 | Affiche la liste des items disponibles.          |
| `/itemsplus list`                            | Affiche tous les items enregistrés.              |
| `/itemsplus give <item> [quantité]`          | Donne un item au joueur qui exécute la commande. |
| `/itemsplus give <joueur> <item> [quantité]` | Donne un item à un autre joueur.                 |
| `/itemsplus equip tank`                      | Équipe le set Tank sur soi.                      |
| `/itemsplus equip nexium`                    | Équipe le set Nexium sur soi.                    |
| `/itemsplus equip <joueur> tank`             | Équipe le set Tank sur un joueur.                |
| `/itemsplus equip <joueur> nexium`           | Équipe le set Nexium sur un joueur.              |
| `/itemsplus id <item>`                       | Affiche l’identifiant complet d’un item.         |
| `/itemsplus identifier <item>`               | Alias de `/itemsplus id`.                        |

La quantité est limitée entre **1 et 64**.

Permission : `itemsplus.command` — opérateurs par défaut.

### `/createitem`

Ouvre le créateur de contenu personnalisé en jeu.

| Commande              | Description                                      |
| --------------------- | ------------------------------------------------ |
| `/createitem`         | Ouvre le menu principal de création.             |
| `/createitem minerai` | Ouvre directement le créateur de minerai.        |
| `/createitem bloc`    | Ouvre directement le créateur de bloc normal.    |
| `/createitem manage`  | Ouvre directement le gestionnaire des créations. |

Alias : `/itemcreator`, `/icreate`

Permission : `itemsplus.createitem` — opérateurs par défaut.

Types disponibles :

* Item simple
* Épée
* Pioche
* Hache
* Pelle
* Houe
* Casque
* Plastron
* Jambières
* Bottes
* Minerai
* Bloc normal

### `/createminerai`

Ouvre directement le créateur de minerai personnalisé.

Alias : `/createore`

Permission : `itemsplus.createitem` — opérateurs par défaut.

### `/createblock`

Ouvre directement le créateur de bloc normal personnalisé.

Alias : `/createbloc`

Permission : `itemsplus.createitem` — opérateurs par défaut.

### `/manageitem`

Ouvre le gestionnaire permettant de modifier ou supprimer les créations.

Alias : `/edititem`, `/itemmanager`, `/gereritem`

Permission : `itemsplus.manage` — opérateurs par défaut.

Éléments modifiables :

* Items simples : nom, texture, affichage créatif et résistance au feu.
* Outils : nom, texture, durabilité, dégâts, vitesse de minage, niveau de récolte, enchantabilité, usure, tags et catégorie créative.
* Armures : nom, texture, durabilité, défense, toughness, résistance au feu et catégorie créative.
* Minerais : nom, texture, dureté, hauteurs, filons, drop, quantité, chance et génération.
* Blocs normaux : nom, texture, dureté et affichage créatif.

La suppression d’un élément demande une confirmation en saisissant `SUPPRIMER`.

### `/minerais`

Commande de génération des minerais personnalisés.

| Commande                      | Description                                           |
| ----------------------------- | ----------------------------------------------------- |
| `/minerais c [rayon]`         | Génère les minerais personnalisés autour du joueur.   |
| `/minerais <monde> <minerai>` | Lance la génération du minerai sur la carte indiquée. |

Exemples :

```text
/minerais c 100
/minerais world azurite
```

Le rayon de génération locale est limité entre **8 et 256 blocs**.

Permission : `minerais.command` — opérateurs par défaut.

## Permissions

| Permission             | Description                          | Valeur par défaut |
| ---------------------- | ------------------------------------ | ----------------- |
| `itemsplus.command`    | Utiliser `/itemsplus`.               | OP                |
| `itemsplus.createitem` | Créer des items, blocs et minerais.  | OP                |
| `itemsplus.manage`     | Modifier ou supprimer les créations. | OP                |
| `minerais.command`     | Utiliser `/minerais`.                | OP                |

## Configuration

Tout le contenu personnalisé est regroupé dans :

```text
plugin_data/ItemsPlus/config.yml
```

Le fichier peut contenir notamment les sections suivantes :

* `items`
* `tools`
* `armor`
* `minerals`
* `blocks`

L’ancien fichier `plugin_data/MineraisPlus/config.yml` peut être importé automatiquement au premier démarrage lorsque la section `minerals` n’existe pas encore.

## Pack de ressources

Le plugin enregistre les items et blocs côté serveur, mais les textures doivent être présentes dans le pack de ressources.

Pour les items :

* ajoutez les fichiers PNG dans le pack ;
* déclarez leurs clés de texture dans les fichiers correspondants.

Pour les blocs et minerais :

* ajoutez les textures dans `textures/` ;
* déclarez-les dans `textures/terrain_texture.json` ;
* ajoutez les informations nécessaires dans `blocks.json`.

Le nom de texture saisi dans l’interface ItemsPlus doit correspondre exactement à la clé déclarée dans le pack.

## Menu créatif

ItemsPlus range automatiquement le contenu dans les catégories appropriées :

* outils et armes dans les groupes vanilla correspondants ;
* armures dans la catégorie des équipements ;
* blocs normaux dans **Construction / Pierre** ;
* minerais dans **Nature / Minerais**.

Le correctif actuel conserve les entrées créatives enregistrées par Customies afin d’éviter les désynchronisations entre le client et le serveur, les blocs fantômes et les rollbacks pendant la pose rapide.

## Structure du projet

```text
ItemsPlus/
├── plugin.yml
├── resources/
│   └── config.yml
└── src/
    └── nestouille/
        └── itemsplus/
            ├── Main.php
            ├── blocks/
            ├── command/
            ├── form/
            ├── item/
            └── minerals/
```

## Remarques importantes

* Ne laissez pas l’ancien plugin `MineraisPlus.phar` installé en même temps.
* Ne changez pas un identifiant déjà utilisé sans supprimer correctement l’ancien contenu.
* Les identifiants Customies sont enregistrés au démarrage du serveur.
* Les textures manquantes apparaîtront comme des objets ou blocs invisibles ou incorrects côté client.
* Effectuez toujours un arrêt complet avant de remplacer le plugin ou de modifier le pack de ressources.

## Auteur

Développé par **Nest**.
