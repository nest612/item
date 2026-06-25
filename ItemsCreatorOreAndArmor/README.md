# ItemsPlus

ItemsPlus is a **PocketMine-MP 5** plugin that lets server administrators create, register, edit, manage and distribute custom items, tools, armor, blocks and ores directly in-game.

It combines the former **ItemsPlus** and **MineraisPlus** systems into one plugin and uses **Customies** to register custom content.

## Information

| Property | Value |
|:---|:---|
| **Name** | ItemsPlus |
| **Version** | `2.4.5` |
| **Author** | **Nest** |
| **PocketMine-MP API** | `5.0.0` |
| **Required dependency** | Customies |
| **Load order** | `STARTUP` |
| **License** | MIT |

## Features

- Create custom basic items through an in-game form.
- Create swords, pickaxes, axes, shovels and hoes.
- Create helmets, chestplates, leggings and boots.
- Create custom standard blocks.
- Create and generate custom ores.
- Edit or delete registered content through an in-game manager.
- Configure durability, damage, mining speed and harvest level.
- Configure armor defense, toughness and fire resistance.
- Configure ore drops, vein size, generation height and spawn chance.
- Automatically place registered content in creative inventory categories.
- Use vanilla-style tool animations.
- Prevent rapid block-placement rollbacks and ghost blocks.
- Import older ItemsPlus and MineraisPlus configurations.
- Preserve compatibility with the bundled Tank, Nexium, Azurite and Auralite definitions.

## Requirements

- PocketMine-MP 5.
- Customies 1.4.0 or a compatible build.
- A resource pack containing every texture and model referenced by your custom definitions.

## Installation

1. Fully stop the server.
2. Download the ItemsPlus `.phar` from Poggit.
3. Place it in the server's `plugins/` directory.
4. Install **Customies** in the same directory.
5. Install the resource pack containing your custom textures and models.
6. Remove older separate ItemsPlus or MineraisPlus plugins to prevent duplicate identifiers and commands.
7. Start the server.

The main configuration is created at:

```text
plugin_data/ItemsPlus/config.yml
```

> Customies registers identifiers during startup. After creating, editing or deleting custom content, fully stop and restart the server.

## Commands

### `/itemsplus`

Lists, gives and equips registered custom items.

| Command | Description |
|:---|:---|
| `/itemsplus` | Displays the available ItemsPlus commands and items. |
| `/itemsplus list` | Lists all registered custom items. |
| `/itemsplus give <item> [quantity]` | Gives an item to the command sender. |
| `/itemsplus give <player> <item> [quantity]` | Gives an item to another player. |
| `/itemsplus equip tank` | Equips the Tank armor set on the command sender. |
| `/itemsplus equip nexium` | Equips the Nexium armor set on the command sender. |
| `/itemsplus equip <player> tank` | Equips the Tank armor set on another player. |
| `/itemsplus equip <player> nexium` | Equips the Nexium armor set on another player. |
| `/itemsplus id <item>` | Displays the complete identifier of an item. |
| `/itemsplus identifier <item>` | Alias of `/itemsplus id`. |

The quantity is limited to **1–64**.

**Permission:** `itemsplus.command`  
**Default:** Operator

### `/createitem`

Opens the in-game custom content creator.

| Command | Description |
|:---|:---|
| `/createitem` | Opens the main creation menu. |
| `/createitem ore` | Opens the custom ore creator. |
| `/createitem block` | Opens the standard block creator. |
| `/createitem manage` | Opens the custom content manager. |

French arguments such as `/createitem minerai` and `/createitem bloc` are also accepted.

**Aliases:** `/itemcreator`, `/icreate`  
**Permission:** `itemsplus.createitem`  
**Default:** Operator

Available content types:

- Basic item
- Sword
- Pickaxe
- Axe
- Shovel
- Hoe
- Helmet
- Chestplate
- Leggings
- Boots
- Ore
- Standard block

### `/createminerai`

Opens the custom ore creator directly.

| Property | Value |
|:---|:---|
| **Command** | `/createminerai` |
| **Alias** | `/createore` |
| **Permission** | `itemsplus.createitem` |
| **Default** | Operator |

### `/createblock`

Opens the standard custom block creator directly.

| Property | Value |
|:---|:---|
| **Command** | `/createblock` |
| **Alias** | `/createbloc` |
| **Permission** | `itemsplus.createitem` |
| **Default** | Operator |

### `/manageitem`

Opens the manager used to edit or delete registered content.

| Property | Value |
|:---|:---|
| **Command** | `/manageitem` |
| **Aliases** | `/edititem`, `/itemmanager`, `/gereritem` |
| **Permission** | `itemsplus.manage` |
| **Default** | Operator |

| Content type | Editable properties |
|:---|:---|
| **Basic items** | Name, texture, creative visibility and fire resistance |
| **Tools** | Name, texture, durability, damage, mining speed, harvest level, enchantability, durability loss, tags and creative category |
| **Armor** | Name, texture, durability, defense, toughness, fire resistance and creative category |
| **Ores** | Name, texture, hardness, generation height, vein size, drops, quantity, chance and generation settings |
| **Standard blocks** | Name, texture, hardness and creative visibility |

Deleting content requires confirmation by entering `DELETE` in the manager.

### `/minerais`

Generates registered custom ores locally or across a selected world.

| Command | Description |
|:---|:---|
| `/minerais c [radius]` | Generates custom ores around the player. |
| `/minerais <world> <ore>` | Starts generation of a selected ore in the specified world. |

Examples:

```text
/minerais c 100
/minerais world azurite
```

The local generation radius is limited to **8–256 blocks**.

**Permission:** `minerais.command`  
**Default:** Operator

## Permissions

| Permission | Description | Default |
|:---|:---|:---:|
| `itemsplus.command` | Allows the use of `/itemsplus`. | OP |
| `itemsplus.createitem` | Allows custom items, blocks and ores to be created in-game. | OP |
| `itemsplus.manage` | Allows registered content to be edited or deleted. | OP |
| `minerais.command` | Allows the use of `/minerais`. | OP |

## Configuration

Custom content is stored in:

```text
plugin_data/ItemsPlus/config.yml
```

| Section | Content |
|:---|:---|
| `items` | Basic items |
| `tools` | Tools and weapons |
| `armor` | Armor pieces |
| `minerals` | Ores and generation settings |
| `blocks` | Standard blocks |

An older `plugin_data/MineraisPlus/config.yml` file may be imported automatically when the `minerals` section does not exist yet.

## Resource pack

ItemsPlus registers content on the server, but textures and models must be supplied by a resource pack.

### Items

- Add the PNG textures to the resource pack.
- Declare each texture key in the appropriate item texture file.
- Use exactly the same texture key in the ItemsPlus creator.

### Blocks and ores

- Add block textures to the resource pack's `textures/` directory.
- Declare them in `textures/terrain_texture.json`.
- Add the required definitions to `blocks.json`.

A missing or incorrect texture key may cause an item or block to appear invisible or use the missing-texture placeholder.

## Creative inventory

| Content | Category |
|:---|:---|
| Tools and weapons | Matching vanilla tool or weapon groups |
| Armor | Equipment |
| Standard blocks | **Construction / Stone** |
| Ores | **Nature / Ores** |

The rollback fix preserves creative entries registered by Customies to reduce client-server desynchronization, ghost blocks and placement rollbacks during rapid building.

## Project structure

```text
ItemsPlus/
├── .poggit.yml
├── LICENSE
├── README.md
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

## Important notes

- Do not run the old separate MineraisPlus plugin alongside ItemsPlus.
- Do not reuse an identifier without deleting the old definition correctly.
- Customies identifiers are registered during server startup.
- Keep a backup of `plugin_data/ItemsPlus/config.yml` before major changes.
- Fully stop the server before replacing the plugin or resource pack.
- The current in-game forms and messages are primarily written in French.

## Support and contributions

Bug reports and pull requests are welcome through the GitHub repository.

When reporting a problem, include:

- PocketMine-MP version
- Customies version
- ItemsPlus version
- Complete error or crash log
- Steps required to reproduce the issue

## License

ItemsPlus is released under the **MIT License**.

## Author

Developed by **Nest**.
