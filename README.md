# ItemsPlus

The **PocketMine-MP 5** plugin allows you to create, save, edit and distribute custom items, tools, armour, blocks and ores directly within the game.

The plugin combines the former **ItemsPlus** and **MineraisPlus** systems into a single extension and uses **Customies** to save custom content.

## Information

| Element    | Value                                |
| ---------- | ------------------------------------ |
| Name       | ItemsPlus                            |
| Version    | `2.4.5-block-placement-rollback-fix` |
| Author     | **Nest**                             |
| API        | PocketMine-MP `5.0.0`                |
| Dependency | Customies                            |
| Loading    | `STARTUP`                            |

## Features

* Crafting custom items via an in-game interface.
* Crafting swords, pickaxes, axes, spades and hoes.
* Crafting helmets, chestplates, legguards and boots.
* Crafting custom normal blocks.
* Crafting and generating custom ores.
* Modifying and deleting content via an in-game interface.
* Configuring durability, damage, mining speed and harvesting efficiency.
* Configuring armour defence and resistance.
* Management of ore drops, veins and spawn heights.
* Automatic sorting of items into categories in the creative menu.
* Vanilla animations for tools when used in hand.
* Block placement fix to prevent rollbacks and ghost blocks.
* Compatibility with older Tank, Nexium, Azurite and Auralite content.

## Prerequisites

* A **PocketMine-MP 5** server.
* The **Customies** plugin, compatible with the server version.
* A resource pack containing textures and models for custom items and blocks.

## Installation

1. Shut down the server completely.
2. Install `ItemsPlus.phar` in the `plugins/` folder.
3. Also install **Customies** in `plugins/`.
4. Keep or install the resource pack containing the custom textures.
5. Remove the old separate `ItemsPlus` and `MineraisPlus` plugins to avoid conflicts with identifiers and commands.
6. Restart the server completely.

The main configuration is created in:

```text
plugin_data/ItemsPlus/config.yml
```

> After creating, editing or deleting Customies content, the server must be shut down and then fully restarted.

## Orders

### `/itemsplus`

Main command for listing, assigning and equipping custom items.
  
| Command                                      | Description                                         |
| -------------------------------------------- | ----------------------------------------- -------   |
| `/itemsplus`                                 | Displays the list of available items.               |
| `/itemsplus list`                            | Displays all saved items.                           |
| `/itemsplus give <item> [quantity]`          | Gives an item to the player who enters the command. |
| `/itemsplus give <player> <item> [quantity]` | Gives an item to another player.                    |
| `/itemsplus equip tank`                      | Equips the Tank set on yourself.                    |
| `/itemsplus equip nexium`                    | Equips the Nexium set on yourself.                  |
| `/itemsplus equip <player> tank`             | Equips the Tank set on a player.                    |
| `/itemsplus equip <player> nexium`           | Equips the Nexium set on a player.                  |
| `/itemsplus id <item>`                       | Displays the full ID of an item.                    |
| `/itemsplus identifier <item>`               | Alias for `/itemsplus id`.                          |

The quantity is limited to between **1 and 64**.

Permission: `itemsplus.command` — default operators.

### `/createitem`

Opens the in-game custom content creator.

| Command               | Description                                       |
| --------------------- | ----------------------------------------------- - |
| `/createitem`         | Opens the main creation menu.                     |
| `/createitem ore`     |  Opens the ore creator directly.                  |
| `/createitem block`   | Opens the normal block creator directly.          |
| `/createitem manage`  | Opens the creation manager directly.              |

Alias : `/itemcreator`, `/icreate`

Permission : `itemsplus.createitem` — opérateurs par défaut.

Available types:

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
* Normal block

### `/createminerai`

Opens the custom ore creator directly.

Alias: `/createore`

Permission: `itemsplus.createitem` — operators by default.

### `/createblock`

Opens the standard custom block creator directly.

Alias: `/createbloc`

Permission: `itemsplus.createitem` — operators by default.

### `/manageitem`

Opens the manager allowing you to edit or delete creations.

Alias : `/edititem`, `/itemmanager`, `/gereritem`

Permission: `itemsplus.manage` — default operators.

Editable items:

* Simple items: name, texture, creative display and fire resistance.
* Tools: name, texture, durability, damage, mining speed, harvesting level, enchantability, wear, tags and creative category.
* Armour: name, texture, durability, defence, toughness, fire resistance and creative category.
* Ore: name, texture, hardness, heights, veins, drop, quantity, chance and generation.
* Normal blocks: name, texture, hardness and creative view.

Deleting an item requires confirmation by typing `DELETE`.

### `/minerals`

Command for generating custom minerals.
  
| Command                       | Description                                              |
| ----------------------------- | ------------------------------------ -----------------   |
| `/minerals c [radius]`        | Generates custom ores around the player.                 |
| `/minerals <world> <ore>`     | Starts ore generation on the specified map.              |

Examples:

```text
/minerals c 100
/minerals world azurite
```

The local spawn radius is limited to between **8 and 256 blocks**.

Permission: `minerals.command` — operators by default.

## Permissions

| Permission             | Description                          | Default value     |
| ---------------------- | ------------------------------------ | ---------------- -|
| `itemsplus.command`    | Use `/itemsplus`.                    | OP                |
| `itemsplus.createitem` | Create items, blocks and ores.       | OP                |
| `itemsplus.manage`     | Edit or delete creations.            | OP                |
| `minerais.command`     | Use `/minerais`.                     | OP                |

## Configuration

All custom content is grouped in:

```text
plugin_data/ItemsPlus/config.yml
```

The file may contain the following sections, amongst others:

* `items`
* `tools`
* `armour`
* `minerals`
* `blocks`
The old `plugin_data/MineraisPlus/config.yml` file can be imported automatically on first launch if the `minerals` section does not yet exist.

## Resource pack

The plugin saves items and blocks on the server side, but the textures must be included in the resource pack.

For items:

* add the PNG files to the resource pack;
* declare their texture keys in the relevant files.

For blocks and ores:

* add the textures to `textures/`...

* add the textures to `textures/`;
* declare them in `textures/terrain_texture.json`;
* add the necessary information to `blocks.json`.

The texture name entered in the ItemsPlus interface must match exactly the key declared in the pack.

## Creative Menu

ItemsPlus automatically organises content into the appropriate categories:

* tools and weapons in the corresponding vanilla groups;
* armour in the equipment category;
* normal blocks in **Construction / Stone**;
* ores in **Nature / Ores**.

The current patch preserves creative entries saved by Customies to prevent desynchronisation between the client and the server, ghost blocks and rollbacks during quick placement.

## Project structure

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

## Important notes

* Do not change an ID that is already in use without properly deleting the old content.
* Customies IDs are saved when the server starts up.
* Missing textures will appear as invisible or incorrect objects or blocks on the client side.
* Always perform a full shutdown before replacing the plugin or modifying the resource pack.

## Author

Developed by **Nest**.
