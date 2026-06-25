# ItemsPlus 2.4.5

ItemsPlus lets PocketMine-MP 5 server administrators create and manage custom items, tools, armor, blocks and ores directly in-game.

## Main features

- In-game custom item, tool and armor creator
- Standard custom blocks and generated custom ores
- In-game editing and deletion manager
- Durability, damage, mining speed, harvest level and armor statistics
- Creative inventory category organization
- Local and full-world ore generation
- Block placement rollback and ghost-block fix
- Migration support for older ItemsPlus and MineraisPlus configurations

## Requirements

- PocketMine-MP 5
- Customies 1.4.0 or a compatible version
- A resource pack containing the texture keys used by your custom definitions

## Installation

1. Install ItemsPlus and Customies in `plugins/`.
2. Install your resource pack.
3. Fully restart the server.
4. Use `/createitem` to create content and `/manageitem` to edit it.

Customies identifiers are registered during startup, so a complete restart is required after adding, editing or deleting custom content.
