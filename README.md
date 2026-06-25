# ItemsPlus

**ItemsPlus** is a PocketMine-MP 5 plugin that allows server administrators to create, save, edit, manage and distribute custom items, tools, armor, blocks and ores directly in-game.

The plugin combines the former **ItemsPlus** and **MineraisPlus** systems into a single extension and uses **Customies** to register custom content.

## Information

| Property       | Value                                |
| :------------- | :----------------------------------- |
| **Name**       | ItemsPlus                            |
| **Version**    | `2.4.5-block-placement-rollback-fix` |
| **Author**     | **Nest**                             |
| **API**        | PocketMine-MP `5.0.0`                |
| **Dependency** | Customies                            |
| **Load order** | `STARTUP`                            |

## Features

* Create custom items through an in-game interface.
* Create swords, pickaxes, axes, shovels and hoes.
* Create helmets, chestplates, leggings and boots.
* Create custom standard blocks.
* Create and generate custom ores.
* Edit and delete custom content through an in-game interface.
* Configure durability, damage, mining speed and harvest level.
* Configure armor defense, toughness and resistance.
* Configure ore drops, veins, generation height and spawn chance.
* Automatically organize items into the correct creative inventory categories.
* Use vanilla-style tool animations.
* Prevent block-placement rollbacks and ghost blocks.
* Maintain compatibility with older Tank, Nexium, Azurite and Auralite content.

## Requirements

* A **PocketMine-MP 5** server.
* A compatible version of the **Customies** plugin.
* A resource pack containing the textures and models for the custom items and blocks.

## Installation

1. Fully stop the server.
2. Place `ItemsPlus.phar` inside the `plugins/` directory.
3. Install **Customies** inside the `plugins/` directory.
4. Install or keep the resource pack containing the custom textures.
5. Remove older separate versions of `ItemsPlus` and `MineraisPlus` to prevent command and identifier conflicts.
6. Fully restart the server. 

The main configuration file is created at:

```text
plugin_data/ItemsPlus/config.yml
```

> After creating, editing or deleting Customies content, fully stop and restart the server so the content can be registered correctly.

## Commands

### `/itemsplus`

Main command used to list, give and equip custom items.

| Command                                      | Description                                        |
| :------------------------------------------- | :------------------------------------------------- |
| `/itemsplus`                                 | Displays the list of available custom items.       |
| `/itemsplus list`                            | Displays all registered items.                     |
| `/itemsplus give <item> [quantity]`          | Gives an item to the command sender.               |
| `/itemsplus give <player> <item> [quantity]` | Gives an item to another player.                   |
| `/itemsplus equip tank`                      | Equips the Tank armor set on the command sender.   |
| `/itemsplus equip nexium`                    | Equips the Nexium armor set on the command sender. |
| `/itemsplus equip <player> tank`             | Equips the Tank armor set on another player.       |
| `/itemsplus equip <player> nexium`           | Equips the Nexium armor set on another player.     |
| `/itemsplus id <item>`                       | Displays the complete identifier of an item.       |
| `/itemsplus identifier <item>`               | Alias of `/itemsplus id`.                          |

The quantity must be between **1 and 64**.

**Permission:** `itemsplus.command`
**Default access:** Operators

---

### `/createitem`

Opens the in-game custom content creator.

| Command               | Description                       |
| :-------------------- | :-------------------------------- |
| `/createitem`         | Opens the main creation menu.     |
| `/createitem minerai` | Opens the custom ore creator.     |
| `/createitem bloc`    | Opens the standard block creator. |
| `/createitem manage`  | Opens the custom content manager. |

**Aliases:** `/itemcreator`, `/icreate`
**Permission:** `itemsplus.createitem`
**Default access:** Operators

Available content types:

* Basic item
* Sword
* Pickaxe
* Axe
* Shovel
* Hoe
* Helmet
* Chestplate
* Leggings
* Boots
* Ore
* Standard block

---

### `/createminerai`

Opens the custom ore creator directly.

| Property           | Value                  |
| :----------------- | :--------------------- |
| **Command**        | `/createminerai`       |
| **Alias**          | `/createore`           |
| **Permission**     | `itemsplus.createitem` |
| **Default access** | Operators              |

---

### `/createblock`

Opens the standard custom block creator directly.

| Property           | Value                  |
| :----------------- | :--------------------- |
| **Command**        | `/createblock`         |
| **Alias**          | `/createbloc`          |
| **Permission**     | `itemsplus.createitem` |
| **Default access** | Operators              |

---

### `/manageitem`

Opens the custom content manager, allowing administrators to edit or delete registered content.

| Property           | Value                                     |
| :----------------- | :---------------------------------------- |
| **Command**        | `/manageitem`                             |
| **Aliases**        | `/edititem`, `/itemmanager`, `/gereritem` |
| **Permission**     | `itemsplus.manage`                        |
| **Default access** | Operators                                 |

Editable properties include:

| Content type        | Editable properties                                                                                                         |
| :------------------ | :-------------------------------------------------------------------------------------------------------------------------- |
| **Basic items**     | Name, texture, creative inventory visibility and fire resistance                                                            |
| **Tools**           | Name, texture, durability, damage, mining speed, harvest level, enchantability, durability loss, tags and creative category |
| **Armor**           | Name, texture, durability, defense, toughness, fire resistance and creative category                                        |
| **Ores**            | Name, texture, hardness, generation height, vein size, drops, quantity, chance and generation settings                      |
| **Standard blocks** | Name, texture, hardness and creative inventory visibility                                                                   |

Deleting custom content requires confirmation by entering:

```text
DELETE
```

---

### `/minerais`

Generates custom ores locally or across a selected world.

| Command                   | Description                                              |
| :------------------------ | :------------------------------------------------------- |
| `/minerais c [radius]`    | Generates registered custom ores around the player.      |
| `/minerais <world> <ore>` | Starts generating a selected ore in the specified world. |

Examples:

```text
/minerais c 100
/minerais world azurite
```

The local generation radius must be between **8 and 256 blocks**.

**Permission:** `minerais.command`
**Default access:** Operators

## Permissions

| Permission             | Description                                             | Default |
| :--------------------- | :------------------------------------------------------ | :-----: |
| `itemsplus.command`    | Allows access to `/itemsplus`.                          |    OP   |
| `itemsplus.createitem` | Allows the creation of custom items, blocks and ores.   |    OP   |
| `itemsplus.manage`     | Allows administrators to edit or delete custom content. |    OP   |
| `minerais.command`     | Allows access to `/minerais`.                           |    OP   |

## Configuration

All custom content is stored in:

```text
plugin_data/ItemsPlus/config.yml
```

The configuration file may contain the following sections:

| Section    | Content                                   |
| :--------- | :---------------------------------------- |
| `items`    | Basic custom items                        |
| `tools`    | Custom tools and weapons                  |
| `armor`    | Custom armor pieces                       |
| `minerals` | Custom ores and their generation settings |
| `blocks`   | Custom standard blocks                    |

The old configuration file located at:

```text
plugin_data/MineraisPlus/config.yml
```

may be imported automatically during the first launch when the `minerals` section does not already exist.

## Resource Pack

The plugin registers custom items and blocks on the server side, but their textures and models must be included in a resource pack.

### Items

* Add the PNG textures to the resource pack.
* Declare the texture keys in the appropriate resource-pack files.
* Make sure the texture name entered in ItemsPlus exactly matches the declared texture key.

### Blocks and ores

* Add the block textures to the `textures/` directory.
* Declare the textures in `textures/terrain_texture.json`.
* Add the required block definitions to `blocks.json`.

Example structure:

```text
resource_pack/
├── blocks.json
└── textures/
    ├── terrain_texture.json
    ├── items/
    └── blocks/
```

A missing or incorrect texture declaration may cause an item or block to appear invisible, incorrect or use the missing-texture placeholder.

## Creative Inventory

ItemsPlus automatically organizes registered content into the appropriate creative inventory categories.

| Content           | Creative category                            |
| :---------------- | :------------------------------------------- |
| Tools and weapons | Corresponding vanilla tool and weapon groups |
| Armor             | Equipment category                           |
| Standard blocks   | **Construction / Stone**                     |
| Ores              | **Nature / Ores**                            |

The current rollback fix preserves the creative inventory entries registered by Customies. This helps prevent client-server desynchronization, ghost blocks and placement rollbacks during rapid block placement.

## Project Structure

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

## Important Notes

* Do not install the old `MineraisPlus` plugin alongside ItemsPlus.
* Do not change an identifier that is already in use without properly deleting the old content.
* Customies identifiers are registered when the server starts.
* Some changes require a full server restart.
* Missing textures may appear as invisible or incorrect items and blocks.
* Always fully stop the server before replacing the plugin or modifying the resource pack.
* Keep a backup of `plugin_data/ItemsPlus/config.yml` before making major changes.

## Author

Developed by **Nest**.
