<?php

declare(strict_types=1);

namespace nestouille\itemsplus;

use Closure;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\CustomiesItemFactory;
use nestouille\itemsplus\command\ItemsPlusCommand;
use nestouille\itemsplus\command\ItemCreatorCommand;
use nestouille\itemsplus\command\ItemManagerCommand;
use nestouille\itemsplus\blocks\BlocksManager;
use nestouille\itemsplus\item\CustomArmorItem;
use nestouille\itemsplus\item\CustomTextureItem;
use nestouille\itemsplus\item\CustomToolItem;
use nestouille\itemsplus\listener\ToolBehaviorListener;
use nestouille\itemsplus\listener\ArmorDurabilityListener;
use nestouille\itemsplus\minerals\MineralsManager;
use nestouille\itemsplus\minerals\command\MineraisCommand;
use pocketmine\block\BlockToolType;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function constant;
use function count;
use function defined;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function str_contains;
use function strtolower;
use function trim;

final class Main extends PluginBase{

    /** @var array<string, array<string, mixed>> */
    private const DEFAULT_ITEMS = [
        "epee_dragon" => [
            "namespace" => "itemsplus",
            "id" => "epee_dragon",
            "type" => "tool",
            "tool_type" => "sword",
            "name" => "Épée dragon",
            "texture" => "epee_dragon",
            "damage" => 10,
            "durability" => 2031,
            "breaking_speed" => 1.5,
            "harvest_level" => 6,
            "enchantability" => 15,
            "block_durability_damage" => 2,
            "entity_durability_damage" => 1,
            "armor_durability_damage" => -1,
        ],
        "pioche_obsidienne" => [
            "namespace" => "itemsplus",
            "id" => "pioche_obsidienne",
            "type" => "tool",
            "tool_type" => "pickaxe",
            "name" => "Pioche en obsidienne",
            "texture" => "pioche_obsidienne",
            "damage" => 7,
            "durability" => 2031,
            "breaking_speed" => 12.0,
            "harvest_level" => 6,
            "enchantability" => 15,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "hache_dragon" => [
            "namespace" => "itemsplus",
            "id" => "hache_dragon",
            "type" => "tool",
            "tool_type" => "axe",
            "name" => "Hache dragon",
            "texture" => "hache_dragon",
            "damage" => 9,
            "durability" => 2031,
            "breaking_speed" => 10.0,
            "harvest_level" => 6,
            "enchantability" => 15,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "pelle_dragon" => [
            "namespace" => "itemsplus",
            "id" => "pelle_dragon",
            "type" => "tool",
            "tool_type" => "shovel",
            "name" => "Pelle dragon",
            "texture" => "pelle_dragon",
            "damage" => 6,
            "durability" => 2031,
            "breaking_speed" => 10.0,
            "harvest_level" => 6,
            "enchantability" => 15,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "houe_dragon" => [
            "namespace" => "itemsplus",
            "id" => "houe_dragon",
            "type" => "tool",
            "tool_type" => "hoe",
            "name" => "Houe dragon",
            "texture" => "houe_dragon",
            "damage" => 5,
            "durability" => 2031,
            "breaking_speed" => 10.0,
            "harvest_level" => 6,
            "enchantability" => 15,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 1,
            "armor_durability_damage" => -1,
        ],
        "casque_tank" => [
            "namespace" => "itemsplus",
            "id" => "casque_tank",
            "type" => "armor",
            "slot" => "helmet",
            "name" => "Casque Tank",
            "texture" => "casque_tank",
            "defense" => 3,
            "durability" => 407,
            "toughness" => 3
        ],
        "plastron_tank" => [
            "namespace" => "itemsplus",
            "id" => "plastron_tank",
            "type" => "armor",
            "slot" => "chestplate",
            "name" => "Plastron Tank",
            "texture" => "plastron_tank",
            "defense" => 8,
            "durability" => 592,
            "toughness" => 3
        ],
        "jambieres_tank" => [
            "namespace" => "itemsplus",
            "id" => "jambieres_tank",
            "type" => "armor",
            "slot" => "leggings",
            "name" => "Jambières Tank",
            "texture" => "jambieres_tank",
            "defense" => 6,
            "durability" => 555,
            "toughness" => 3
        ],
        "bottes_tank" => [
            "namespace" => "itemsplus",
            "id" => "bottes_tank",
            "type" => "armor",
            "slot" => "boots",
            "name" => "Bottes Tank",
            "texture" => "bottes_tank",
            "defense" => 3,
            "durability" => 481,
            "toughness" => 3
        ],
        "azurite_ingot" => [
            "namespace" => "mineraisplus",
            "id" => "azurite_ingot",
            "type" => "item",
            "name" => "Lingot d'Azurite",
            "texture" => "azurite_ingot",
            "creative" => true
        ],
        "auralite_ingot" => [
            "namespace" => "mineraisplus",
            "id" => "auralite_ingot",
            "type" => "item",
            "name" => "Lingot d'Auralite",
            "texture" => "auralite_ingot",
            "creative" => true
        ],
        "nexium_ingot_legacy" => [
            "namespace" => "mineraisplus",
            "id" => "nexium_ingot",
            "type" => "item",
            "name" => "Lingot de Nexium",
            "texture" => "nexium_ingot",
            "creative" => false
        ],
        "nexium_ingot" => [
            "namespace" => "itemsplus",
            "id" => "nexium_ingot",
            "type" => "item",
            "name" => "Lingot de Nexium",
            "texture" => "nexium_ingot"
        ],
        "nexium_helmet" => [
            "namespace" => "itemsplus",
            "id" => "nexium_helmet",
            "type" => "armor",
            "slot" => "helmet",
            "name" => "Casque Nexium",
            "texture" => "nexium_helmet",
            "defense" => 4,
            "durability" => 555,
            "toughness" => 4
        ],
        "nexium_chestplate" => [
            "namespace" => "itemsplus",
            "id" => "nexium_chestplate",
            "type" => "armor",
            "slot" => "chestplate",
            "name" => "Plastron Nexium",
            "texture" => "nexium_chestplate",
            "defense" => 9,
            "durability" => 810,
            "toughness" => 4
        ],
        "nexium_leggings" => [
            "namespace" => "itemsplus",
            "id" => "nexium_leggings",
            "type" => "armor",
            "slot" => "leggings",
            "name" => "Jambières Nexium",
            "texture" => "nexium_leggings",
            "defense" => 7,
            "durability" => 760,
            "toughness" => 4
        ],
        "nexium_boots" => [
            "namespace" => "itemsplus",
            "id" => "nexium_boots",
            "type" => "armor",
            "slot" => "boots",
            "name" => "Bottes Nexium",
            "texture" => "nexium_boots",
            "defense" => 4,
            "durability" => 650,
            "toughness" => 4
        ],
        "epee_nexium" => [
            "namespace" => "itemsplus",
            "id" => "epee_nexium",
            "type" => "tool",
            "tool_type" => "sword",
            "name" => "Épée Nexium",
            "texture" => "epee_nexium",
            "damage" => 11,
            "durability" => 2600,
            "breaking_speed" => 2.0,
            "harvest_level" => 7,
            "enchantability" => 18,
            "block_durability_damage" => 2,
            "entity_durability_damage" => 1,
            "armor_durability_damage" => -1,
        ],
        "pioche_nexium" => [
            "namespace" => "itemsplus",
            "id" => "pioche_nexium",
            "type" => "tool",
            "tool_type" => "pickaxe",
            "name" => "Pioche Nexium",
            "texture" => "pioche_nexium",
            "damage" => 8,
            "durability" => 2600,
            "breaking_speed" => 15.0,
            "harvest_level" => 7,
            "enchantability" => 18,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "hache_nexium" => [
            "namespace" => "itemsplus",
            "id" => "hache_nexium",
            "type" => "tool",
            "tool_type" => "axe",
            "name" => "Hache Nexium",
            "texture" => "hache_nexium",
            "damage" => 10,
            "durability" => 2600,
            "breaking_speed" => 12.0,
            "harvest_level" => 7,
            "enchantability" => 18,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "pelle_nexium" => [
            "namespace" => "itemsplus",
            "id" => "pelle_nexium",
            "type" => "tool",
            "tool_type" => "shovel",
            "name" => "Pelle Nexium",
            "texture" => "pelle_nexium",
            "damage" => 7,
            "durability" => 2600,
            "breaking_speed" => 13.0,
            "harvest_level" => 7,
            "enchantability" => 18,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 2,
            "armor_durability_damage" => -1,
        ],
        "houe_nexium" => [
            "namespace" => "itemsplus",
            "id" => "houe_nexium",
            "type" => "tool",
            "tool_type" => "hoe",
            "name" => "Houe Nexium",
            "texture" => "houe_nexium",
            "damage" => 6,
            "durability" => 2600,
            "breaking_speed" => 12.0,
            "harvest_level" => 7,
            "enchantability" => 18,
            "block_durability_damage" => 1,
            "entity_durability_damage" => 1,
            "armor_durability_damage" => -1,
        ]
    ];

    /** @var array<string, mixed> */
    private const DEFAULT_GENERATION = [
        "default-radius" => 64,
        "replace-blocks" => ["Stone", "Deepslate", "Tuff"],
        "full-map-chunks-per-tick" => 8,
        "full-map-veins-per-chunk" => 1,
        "full-map-chance-percent" => 65
    ];

    /** @var array<string, array<string, mixed>> */
    private const DEFAULT_MINERALS = [
        "azurite" => [
            "namespace" => "mineraisplus",
            "ore_id" => "azurite_ore",
            "ingot_id" => "azurite_ingot",
            "ingot_identifier" => "mineraisplus:azurite_ingot",
            "ore_name" => "Minerai d'Azurite",
            "ingot_name" => "Lingot d'Azurite",
            "ingot_texture" => "azurite_ingot",
            "hardness" => 3.0,
            "min_y" => -60,
            "max_y" => 32,
            "vein_count" => 22,
            "vein_size" => 5,
            "drop_amount" => 1,
            "drops" => [["item" => "mineraisplus:azurite_ingot", "amount" => 1, "chance" => 100]],
            "full_map_veins_per_chunk" => 1,
            "full_map_chance_percent" => 65
        ],
        "auralite" => [
            "namespace" => "mineraisplus",
            "ore_id" => "auralite_ore",
            "ingot_id" => "auralite_ingot",
            "ingot_identifier" => "mineraisplus:auralite_ingot",
            "ore_name" => "Minerai d'Auralite",
            "ingot_name" => "Lingot d'Auralite",
            "ingot_texture" => "auralite_ingot",
            "hardness" => 3.0,
            "min_y" => -40,
            "max_y" => 48,
            "vein_count" => 18,
            "vein_size" => 5,
            "drop_amount" => 1,
            "drops" => [["item" => "mineraisplus:auralite_ingot", "amount" => 1, "chance" => 100]],
            "full_map_veins_per_chunk" => 1,
            "full_map_chance_percent" => 60
        ],
        "nexium" => [
            "namespace" => "mineraisplus",
            "ore_id" => "nexium_ore",
            "ingot_id" => "nexium_ingot",
            "ingot_identifier" => "itemsplus:nexium_ingot",
            "ore_name" => "Minerai de Nexium",
            "ingot_name" => "Lingot de Nexium",
            "ingot_texture" => "nexium_ingot",
            "hardness" => 4.0,
            "min_y" => -64,
            "max_y" => 16,
            "vein_count" => 12,
            "vein_size" => 4,
            "drop_amount" => 1,
            "drops" => [["item" => "itemsplus:nexium_ingot", "amount" => 1, "chance" => 100]],
            "full_map_veins_per_chunk" => 1,
            "full_map_chance_percent" => 45
        ]
    ];

    /** @var array<string, string> item key => customies identifier */
    private array $items = [];

    /** @var array<string, float> item display name => simple breaking speed */
    private array $toolBreakingSpeedsByName = [];

    private ?MineralsManager $mineralsManager = null;
    private ?BlocksManager $blocksManager = null;

    protected function onEnable() : void{
        $this->saveDefaultConfig();
        $this->importLegacyMineraisPlusConfig();
        $this->mergeDefaultItemsIntoConfig();
        $this->mergeDefaultMineralsIntoConfig();
        $this->ensureBlocksConfigSections();
        $this->registerItems();

        $this->blocksManager = new BlocksManager($this);
        $this->blocksManager->enable();

        $this->mineralsManager = new MineralsManager($this);
        $this->mineralsManager->enable();

        $this->getServer()->getPluginManager()->registerEvents(new ToolBehaviorListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ArmorDurabilityListener($this), $this);
        $this->scheduleDurabilityLoreTask();

        $command = $this->getCommand("itemsplus");
        if($command !== null){
            $command->setExecutor(new ItemsPlusCommand($this));
        }

        $mineralsCommand = $this->getCommand("minerais");
        if($mineralsCommand !== null){
            $mineralsCommand->setExecutor(new MineraisCommand($this->mineralsManager));
        }

        $itemManagerExecutor = new ItemManagerCommand($this);
        $itemCreatorExecutor = new ItemCreatorCommand($this, $itemManagerExecutor);

        $itemCreatorCommand = $this->getCommand("createitem");
        if($itemCreatorCommand !== null){
            $itemCreatorCommand->setExecutor($itemCreatorExecutor);
        }

        $mineralCreatorCommand = $this->getCommand("createminerai");
        if($mineralCreatorCommand !== null){
            $mineralCreatorCommand->setExecutor($itemCreatorExecutor);
        }

        $blockCreatorCommand = $this->getCommand("createblock");
        if($blockCreatorCommand !== null){
            $blockCreatorCommand->setExecutor($itemCreatorExecutor);
        }

        $managerCommand = $this->getCommand("manageitem");
        if($managerCommand !== null){
            $managerCommand->setExecutor($itemManagerExecutor);
        }

        $this->getLogger()->info(TF::GREEN . "ItemsPlus fusionné actif : " . count($this->items) . " items enregistrés.");
        if(count($this->items) > 0){
            $this->getLogger()->info(TF::GRAY . "Items enregistrés : " . implode(", ", array_keys($this->items)));
        }
    }


    private function ensureBlocksConfigSections() : void{
        $config = $this->getConfig();
        $changed = false;

        if(!$config->exists("blocks") || !is_array($config->get("blocks", []))){
            $config->set("blocks", []);
            $changed = true;
        }
        if(!$config->exists("deleted-blocks") || !is_array($config->get("deleted-blocks", []))){
            $config->set("deleted-blocks", []);
            $changed = true;
        }
        if((int) $config->get("config-version", 0) < 6){
            $config->set("config-version", 6);
            $changed = true;
        }

        if($changed){
            $config->save();
            $this->reloadConfig();
        }
    }

    private function importLegacyMineraisPlusConfig() : void{
        if($this->getConfig()->exists("minerals")){
            return;
        }

        $pluginsDataFolder = dirname(rtrim($this->getDataFolder(), DIRECTORY_SEPARATOR));
        $legacyPath = $pluginsDataFolder . DIRECTORY_SEPARATOR . "MineraisPlus" . DIRECTORY_SEPARATOR . "config.yml";
        if(!is_file($legacyPath)){
            return;
        }

        try{
            $legacy = new Config($legacyPath, Config::YAML);
            $legacyMinerals = $legacy->get("minerals", []);
            $legacyGeneration = $legacy->get("generation", []);

            if(is_array($legacyMinerals) && $legacyMinerals !== []){
                $this->getConfig()->set("minerals", $legacyMinerals);
            }
            if(is_array($legacyGeneration) && $legacyGeneration !== []){
                $this->getConfig()->set("generation", $legacyGeneration);
            }
            $this->getConfig()->set("config-version", max(4, (int) $legacy->get("config-version", 0)));
            $this->getConfig()->save();
            $this->reloadConfig();
            $this->getLogger()->info(TF::YELLOW . "Ancienne configuration MineraisPlus importée dans plugin_data/ItemsPlus/config.yml.");
        }catch(Throwable $e){
            $this->getLogger()->warning("Impossible d'importer l'ancien config.yml de MineraisPlus : " . $e->getMessage());
        }
    }

    private function mergeDefaultMineralsIntoConfig() : void{
        $config = $this->getConfig();
        $generation = $config->get("generation", []);
        $minerals = $config->get("minerals", []);
        $deletedMinerals = $config->get("deleted-minerals", []);
        if(!is_array($deletedMinerals)){
            $deletedMinerals = [];
        }
        $changed = false;

        if(!is_array($generation)){
            $generation = [];
            $changed = true;
        }
        foreach(self::DEFAULT_GENERATION as $field => $value){
            if(!array_key_exists($field, $generation)){
                $generation[$field] = $value;
                $changed = true;
            }
        }

        if(!is_array($minerals)){
            $minerals = [];
            $changed = true;
        }
        foreach(self::DEFAULT_MINERALS as $key => $defaultData){
            if(in_array($key, $deletedMinerals, true)){
                continue;
            }
            if(!isset($minerals[$key]) || !is_array($minerals[$key])){
                $minerals[$key] = $defaultData;
                $changed = true;
                continue;
            }
            foreach($defaultData as $field => $value){
                if(!array_key_exists($field, $minerals[$key])){
                    $minerals[$key][$field] = $value;
                    $changed = true;
                }
            }
        }

        if((int) $config->get("config-version", 0) < 5){
            $config->set("config-version", 5);
            $changed = true;
        }

        if($changed){
            $config->set("generation", $generation);
            $config->set("minerals", $minerals);
            $config->save();
            $this->reloadConfig();
            $this->getLogger()->info(TF::YELLOW . "config.yml complété avec la configuration des minerais et des items basic.");
        }
    }

    private function mergeDefaultItemsIntoConfig() : void{
        $config = $this->getConfig();
        $items = $config->get("items", []);
        $deletedItems = $config->get("deleted-items", []);
        if(!is_array($deletedItems)){
            $deletedItems = [];
        }
        $changed = false;

        if(!is_array($items)){
            $items = [];
            $changed = true;
        }

        foreach(self::DEFAULT_ITEMS as $key => $defaultData){
            if(in_array($key, $deletedItems, true)){
                continue;
            }
            if(!isset($items[$key]) || !is_array($items[$key])){
                $items[$key] = $defaultData;
                $changed = true;
                continue;
            }

            foreach($defaultData as $field => $value){
                if(!array_key_exists($field, $items[$key])){
                    $items[$key][$field] = $value;
                    $changed = true;
                }
            }
        }

        if($changed){
            $config->set("items", $items);
            $config->save();
            $this->reloadConfig();
            $this->getLogger()->info(TF::YELLOW . "config.yml mise à jour automatiquement avec les nouveaux champs simples.");
        }
    }

    private function registerItems() : void{
        $this->items = [];
        $this->toolBreakingSpeedsByName = [];
        $items = $this->getConfig()->get("items", []);
        if(!is_array($items)){
            $this->getLogger()->warning("La section 'items' du config.yml est invalide.");
            return;
        }

        foreach($items as $key => $data){
            if(!is_array($data)){
                continue;
            }

            $key = strtolower((string) $key);
            $namespace = strtolower((string) ($data["namespace"] ?? "itemsplus"));
            $id = strtolower((string) ($data["id"] ?? $key));
            $name = (string) ($data["name"] ?? $key);
            $texture = $this->resolveTextureKey((string) ($data["texture"] ?? $id), $id);
            $identifier = $namespace . ":" . $id;
            $type = strtolower((string) ($data["type"] ?? "item"));
            $creative = (bool) ($data["creative"] ?? true);
            $creativeInfoForPlacement = null;

            try{
                if($type === "armor" || $type === "armure"){
                    $armorSlot = $this->resolveArmorSlot((string) ($data["slot"] ?? ""), $id);
                    $defense = (int) ($data["defense"] ?? $this->getDefaultDefense($armorSlot));
                    $durability = (int) ($data["durability"] ?? $this->getDefaultDurability($armorSlot));
                    $toughness = (int) ($data["toughness"] ?? 0);
                    $fireProof = (bool) ($data["fireproof"] ?? $data["fireProof"] ?? false);

                    $creativeInfoForPlacement = $creative ? $this->makeArmorCreativeInfo($armorSlot, $data) : null;
                    $this->registerCustomArmorItem($identifier, $name, $texture, $armorSlot, $defense, $durability, $toughness, $fireProof, $creativeInfoForPlacement);
                }elseif($type === "tool" || $type === "outil" || $type === "weapon" || $type === "arme"){
                    $toolType = $this->resolveToolType((string) ($data["tool_type"] ?? $data["toolType"] ?? $data["kind"] ?? $data["tool"] ?? ""), $id);
                    $blockToolType = $this->getBlockToolTypeForTool($toolType);
                    $attackDamage = (int) ($data["damage"] ?? $data["attack_damage"] ?? $data["attackDamage"] ?? $this->getDefaultToolDamage($toolType));
                    $durability = (int) ($data["durability"] ?? $this->getDefaultToolDurability($toolType));
                    $miningEfficiency = (float) ($data["breaking_speed"] ?? $data["break_speed"] ?? $data["mining_efficiency"] ?? $data["miningEfficiency"] ?? $this->getDefaultMiningEfficiency($toolType));
                    $harvestLevel = (int) ($data["harvest_level"] ?? $data["mining_level"] ?? $data["harvestLevel"] ?? $this->getDefaultHarvestLevel($toolType));
                    $enchantability = (int) ($data["enchantability"] ?? 10);
                    $fireProof = (bool) ($data["fireproof"] ?? $data["fireProof"] ?? false);
                    $blockDurabilityDamage = (int) ($data["block_durability_damage"] ?? $data["blockDurabilityDamage"] ?? ($toolType === "sword" ? 2 : 1));
                    $entityDurabilityDamage = (int) ($data["entity_durability_damage"] ?? $data["entityDurabilityDamage"] ?? ($toolType === "hoe" ? 1 : ($toolType === "sword" ? 1 : 2)));
                    $armorDurabilityDamage = (int) ($data["armor_durability_damage"] ?? $data["armorDurabilityDamage"] ?? -1);
                    $diggerTags = $this->parseStringList($data["digger_tags"] ?? $data["diggerTags"] ?? $data["destroy_tags"] ?? $data["destroyTags"] ?? []);
                    $diggerBlocks = $this->parseStringList($data["digger_blocks"] ?? $data["diggerBlocks"] ?? []);
                    $diggerBlocks = array_values(array_unique(array_merge(
                        $diggerBlocks,
                        $this->getConfiguredDiggerBlocksForTool($toolType)
                    )));

                    $this->registerCustomToolItem(
                        $identifier,
                        $name,
                        $texture,
                        $toolType,
                        $blockToolType,
                        $harvestLevel,
                        $attackDamage,
                        $durability,
                        $miningEfficiency,
                        $enchantability,
                        $fireProof,
                        $blockDurabilityDamage,
                        $entityDurabilityDamage,
                        $armorDurabilityDamage,
                        $diggerTags,
                        $diggerBlocks,
                        $creativeInfoForPlacement = $creative ? $this->makeToolCreativeInfo($toolType, $data) : null
                    );
                    $this->toolBreakingSpeedsByName[$name] = $miningEfficiency;
                }else{
                    $creativeInfoForPlacement = $creative ? $this->makeBasicItemCreativeInfo($data) : null;
                    $this->registerCustomItem(
                        $identifier,
                        $name,
                        $texture,
                        $creativeInfoForPlacement
                    );
                }

                // Certaines versions de Customies enregistrent les composants de l'item,
                // mais ne l'ajoutent pas réellement au catalogue créatif lorsqu'un groupe est fourni.
                // On force donc une seule entrée, dans la bonne catégorie et le bon groupe.
                if($creative && $creativeInfoForPlacement !== null){
                    $this->forceCreativePlacementByIdentifier($identifier, $creativeInfoForPlacement);
                }
                $this->items[$key] = $identifier;
            }catch(Throwable $e){
                $this->getLogger()->warning("Impossible d'enregistrer l'item " . $identifier . " : " . $e->getMessage());
            }
        }
    }


    private function scheduleDurabilityLoreTask() : void{
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            foreach($this->getServer()->getOnlinePlayers() as $player){
                $inventory = $player->getInventory();
                for($slot = 0; $slot < $inventory->getSize(); ++$slot){
                    $item = $inventory->getItem($slot);
                    if($this->refreshDurabilityLoreIfNeeded($item)){
                        $inventory->setItem($slot, $item);
                    }
                }

                $armorInventory = $player->getArmorInventory();

                $helmet = $armorInventory->getHelmet();
                if($this->refreshDurabilityLoreIfNeeded($helmet)){
                    $armorInventory->setHelmet($helmet);
                }

                $chestplate = $armorInventory->getChestplate();
                if($this->refreshDurabilityLoreIfNeeded($chestplate)){
                    $armorInventory->setChestplate($chestplate);
                }

                $leggings = $armorInventory->getLeggings();
                if($this->refreshDurabilityLoreIfNeeded($leggings)){
                    $armorInventory->setLeggings($leggings);
                }

                $boots = $armorInventory->getBoots();
                if($this->refreshDurabilityLoreIfNeeded($boots)){
                    $armorInventory->setBoots($boots);
                }
            }
        }), 20);
    }

    private function refreshDurabilityLoreIfNeeded(Item $item) : bool{
        if($item instanceof CustomToolItem || $item instanceof CustomArmorItem){
            return $item->refreshDurabilityLore();
        }

        return false;
    }

    private function registerCustomItem(string $identifier, string $name, string $texture, ?CreativeInventoryInfo $creativeInfo) : void{
        $factory = CustomiesItemFactory::getInstance();

        CustomTextureItem::configure($name, $texture, $creativeInfo);

        try{
            $method = new ReflectionMethod($factory, "registerItem");
            $parameters = $method->getParameters();
            $firstType = $parameters[0]->getType() ?? null;
            $expectsClosure = $firstType instanceof ReflectionNamedType && $firstType->getName() === Closure::class;

            if($expectsClosure){
                $factory->registerItem(
                    static fn() : Item => new CustomTextureItem(new ItemIdentifier(ItemTypeIds::newId()), $name),
                    $identifier,
                    $creativeInfo
                );
                return;
            }

            if(count($parameters) >= 4){
                /** @phpstan-ignore-next-line - Compatibilité Customies class-string avec CreativeInventoryInfo */
                $factory->registerItem(CustomTextureItem::class, $identifier, $name, $creativeInfo);
                return;
            }
        }catch(Throwable $e){
            $this->getLogger()->debug("Détection de registerItem impossible : " . $e->getMessage());
        }

        /** @phpstan-ignore-next-line - Compatibilité Customies v1.4.0 Poggit : registerItem(class-string, identifier, name) */
        $factory->registerItem(CustomTextureItem::class, $identifier, $name);
    }

    private function registerCustomArmorItem(
        string $identifier,
        string $name,
        string $texture,
        int $armorSlot,
        int $defense,
        int $durability,
        int $toughness,
        bool $fireProof,
        ?CreativeInventoryInfo $creativeInfo
    ) : void{
        $factory = CustomiesItemFactory::getInstance();

        CustomArmorItem::configure($name, $texture, $defense, $durability, $armorSlot, $toughness, $fireProof, $creativeInfo);

        try{
            $method = new ReflectionMethod($factory, "registerItem");
            $parameters = $method->getParameters();
            $firstType = $parameters[0]->getType() ?? null;
            $expectsClosure = $firstType instanceof ReflectionNamedType && $firstType->getName() === Closure::class;

            if($expectsClosure){
                $factory->registerItem(
                    static fn() : Item => new CustomArmorItem(new ItemIdentifier(ItemTypeIds::newId()), $name),
                    $identifier,
                    $creativeInfo
                );
                return;
            }

            if(count($parameters) >= 4){
                /** @phpstan-ignore-next-line - Compatibilité Customies class-string avec CreativeInventoryInfo */
                $factory->registerItem(CustomArmorItem::class, $identifier, $name, $creativeInfo);
                return;
            }
        }catch(Throwable $e){
            $this->getLogger()->debug("Détection de registerItem armor impossible : " . $e->getMessage());
        }

        /** @phpstan-ignore-next-line - Compatibilité Customies v1.4.0 Poggit : registerItem(class-string, identifier, name) */
        $factory->registerItem(CustomArmorItem::class, $identifier, $name);
    }

    /**
     * @return string[] Identifiants exacts des blocs personnalisés qui doivent
     *                  utiliser la vitesse configurée côté client Bedrock.
     */
    private function getConfiguredDiggerBlocksForTool(string $toolType) : array{
        $toolType = strtolower(trim($toolType));
        $result = [];

        // Tous les minerais personnalisés sont enregistrés comme blocs de pioche.
        if(in_array($toolType, ["pickaxe", "pioche"], true)){
            $minerals = $this->getConfig()->get("minerals", []);
            if(is_array($minerals)){
                foreach($minerals as $key => $data){
                    if(!is_array($data)){
                        continue;
                    }
                    $namespace = strtolower((string) ($data["namespace"] ?? "mineraisplus"));
                    $oreId = strtolower((string) ($data["ore_id"] ?? (strtolower((string) $key) . "_ore")));
                    $result[] = $namespace . ":" . $oreId;
                }
            }
        }

        // Les blocs normaux peuvent déclarer explicitement tool_type dans config.yml.
        $blocks = $this->getConfig()->get("blocks", []);
        if(is_array($blocks)){
            foreach($blocks as $key => $data){
                if(!is_array($data)){
                    continue;
                }
                $blockTool = strtolower((string) ($data["tool_type"] ?? $data["toolType"] ?? ""));
                if($blockTool === "" || $this->resolveToolType($blockTool, "") !== $this->resolveToolType($toolType, "")){
                    continue;
                }
                $namespace = strtolower((string) ($data["namespace"] ?? "itemsplus"));
                $id = strtolower((string) ($data["id"] ?? $key));
                $result[] = $namespace . ":" . $id;
            }
        }

        return array_values(array_unique($result));
    }

    private function registerCustomToolItem(
        string $identifier,
        string $name,
        string $texture,
        string $toolType,
        int $blockToolType,
        int $harvestLevel,
        int $attackDamage,
        int $durability,
        float $miningEfficiency,
        int $enchantability,
        bool $fireProof,
        int $blockDurabilityDamage,
        int $entityDurabilityDamage,
        int $armorDurabilityDamage,
        array $diggerTags,
        array $diggerBlocks,
        ?CreativeInventoryInfo $creativeInfo
    ) : void{
        $factory = CustomiesItemFactory::getInstance();

        CustomToolItem::configure(
            $name,
            $texture,
            $toolType,
            $blockToolType,
            $harvestLevel,
            $attackDamage,
            $durability,
            $miningEfficiency,
            $enchantability,
            $fireProof,
            $blockDurabilityDamage,
            $entityDurabilityDamage,
            $armorDurabilityDamage,
            $diggerTags,
            $diggerBlocks,
            $creativeInfo
        );

        try{
            $method = new ReflectionMethod($factory, "registerItem");
            $parameters = $method->getParameters();
            $firstType = $parameters[0]->getType() ?? null;
            $expectsClosure = $firstType instanceof ReflectionNamedType && $firstType->getName() === Closure::class;

            if($expectsClosure){
                $factory->registerItem(
                    static fn() : Item => new CustomToolItem(new ItemIdentifier(ItemTypeIds::newId()), $name),
                    $identifier,
                    $creativeInfo
                );
                return;
            }

            if(count($parameters) >= 4){
                /** @phpstan-ignore-next-line - Compatibilité Customies class-string avec CreativeInventoryInfo */
                $factory->registerItem(CustomToolItem::class, $identifier, $name, $creativeInfo);
                return;
            }
        }catch(Throwable $e){
            $this->getLogger()->debug("Détection de registerItem tool impossible : " . $e->getMessage());
        }

        /** @phpstan-ignore-next-line - Compatibilité Customies v1.4.0 Poggit : registerItem(class-string, identifier, name) */
        $factory->registerItem(CustomToolItem::class, $identifier, $name);
    }

    private function forceCreativePlacementByIdentifier(string $identifier, CreativeInventoryInfo $creativeInfo) : void{
        try{
            $item = CustomiesItemFactory::getInstance()->get($identifier, 1);
        }catch(Throwable){
            try{
                $item = CustomiesItemFactory::getInstance()->get($identifier);
            }catch(Throwable $e){
                $this->getLogger()->warning("Item enregistré, mais introuvable pour le menu créatif " . $identifier . " : " . $e->getMessage());
                return;
            }
        }

        if($item instanceof Item){
            $this->forceCreativePlacement($item, $creativeInfo);
        }
    }

    /**
     * Garantit qu'un item possède exactement une entrée dans le menu créatif.
     * Cette méthode contourne les différences entre les versions de Customies :
     * certaines ajoutent l'item automatiquement, d'autres enregistrent seulement
     * ses composants réseau quand CreativeInventoryInfo est renseigné.
     */
    public function forceCreativePlacement(Item $item, ?CreativeInventoryInfo $creativeInfo) : void{
        if($creativeInfo === null){
            return;
        }

        $inventory = CreativeInventory::getInstance();

        // Ne jamais retirer une entrée du catalogue créatif ici.
        // CreativeInventory::remove() conserve des trous dans les index internes ;
        // le client et le serveur peuvent alors référencer des objets différents,
        // ce qui provoque des poses annulées / rollback, notamment en godbridge.
        // Si Customies a déjà enregistré l'objet avec sa catégorie, on le garde.
        if($inventory->contains($item)){
            return;
        }

        try{
            $addMethod = new ReflectionMethod($inventory, "add");
            if(count($addMethod->getParameters()) < 2 || !class_exists("pocketmine\\inventory\\CreativeCategory")){
                $inventory->add($item);
                return;
            }

            $categoryName = method_exists($creativeInfo, "getCategory")
                ? strtolower(trim((string) $creativeInfo->getCategory()))
                : "items";
            $categoryClass = "pocketmine\\inventory\\CreativeCategory";
            $categoryConstant = match($categoryName){
                "construction" => $categoryClass . "::CONSTRUCTION",
                "nature" => $categoryClass . "::NATURE",
                "equipment" => $categoryClass . "::EQUIPMENT",
                default => $categoryClass . "::ITEMS"
            };
            $category = constant($categoryConstant);

            $groupName = method_exists($creativeInfo, "getGroup")
                ? trim((string) $creativeInfo->getGroup())
                : "";
            if(str_starts_with($groupName, "minecraft:")){
                $groupName = substr($groupName, 10);
            }

            $group = null;
            if($groupName !== "" && strtolower($groupName) !== "none"){
                foreach($inventory->getAllEntries() as $entry){
                    if(!method_exists($entry, "getGroup")){
                        continue;
                    }
                    $candidate = $entry->getGroup();
                    if($candidate === null || !method_exists($candidate, "getName")){
                        continue;
                    }

                    $candidateName = $candidate->getName();
                    if(is_object($candidateName) && method_exists($candidateName, "getText")){
                        $candidateName = $candidateName->getText();
                    }
                    $candidateName = (string) $candidateName;
                    if(str_starts_with($candidateName, "minecraft:")){
                        $candidateName = substr($candidateName, 10);
                    }

                    if($candidateName === $groupName){
                        $group = $candidate;
                        break;
                    }
                }

                if($group === null && class_exists("pocketmine\\inventory\\CreativeGroup") && class_exists("pocketmine\\lang\\Translatable")){
                    $groupClass = "pocketmine\\inventory\\CreativeGroup";
                    $translatableClass = "pocketmine\\lang\\Translatable";
                    $group = new $groupClass(new $translatableClass($groupName), $item);
                }
            }

            $addMethod->invokeArgs($inventory, [$item, $category, $group]);
        }catch(Throwable $e){
            // Priorité absolue : l'item doit rester visible, même si le serveur
            // utilise une ancienne variante de l'API du catalogue créatif.
            try{
                if(!$inventory->contains($item)){
                    $inventory->add($item);
                }
            }catch(Throwable $fallback){
                $this->getLogger()->warning("Impossible d'ajouter " . $item->getName() . " au menu créatif : " . $fallback->getMessage());
            }
            $this->getLogger()->debug("Classement créatif de secours pour " . $item->getName() . " : " . $e->getMessage());
        }
    }

    private function resolveTextureKey(string $texture, string $defaultTexture) : string{
        $texture = strtolower(trim($texture));
        if($texture === ""){
            $texture = strtolower($defaultTexture);
        }
        if(str_contains($texture, ":")){
            $parts = explode(":", $texture, 2);
            return strtolower($parts[1] ?? $defaultTexture);
        }
        return $texture;
    }

    private function resolveArmorSlot(string $slot, string $id) : int{
        $slot = strtolower(trim($slot));
        $id = strtolower(trim($id));

        if($slot === "helmet" || $slot === "head" || $slot === "casque" || str_contains($id, "helmet") || str_contains($id, "casque")){
            return ArmorInventory::SLOT_HEAD;
        }
        if($slot === "chestplate" || $slot === "chest" || $slot === "plastron" || str_contains($id, "chestplate") || str_contains($id, "plastron")){
            return ArmorInventory::SLOT_CHEST;
        }
        if($slot === "leggings" || $slot === "legs" || $slot === "jambieres" || $slot === "jambières" || str_contains($id, "leggings") || str_contains($id, "jambieres") || str_contains($id, "jambières")){
            return ArmorInventory::SLOT_LEGS;
        }
        if($slot === "boots" || $slot === "feet" || $slot === "bottes" || str_contains($id, "boots") || str_contains($id, "bottes")){
            return ArmorInventory::SLOT_FEET;
        }

        return ArmorInventory::SLOT_CHEST;
    }

    private function resolveToolType(string $toolType, string $id) : string{
        $value = strtolower(trim($toolType));
        $id = strtolower(trim($id));

        if($value === "sword" || $value === "epee" || $value === "épée" || str_contains($id, "sword") || str_contains($id, "epee") || str_contains($id, "épée")){
            return "sword";
        }
        if($value === "pickaxe" || $value === "pioche" || str_contains($id, "pickaxe") || str_contains($id, "pioche")){
            return "pickaxe";
        }
        if($value === "axe" || $value === "hache" || str_contains($id, "axe") || str_contains($id, "hache")){
            return "axe";
        }
        if($value === "shovel" || $value === "pelle" || str_contains($id, "shovel") || str_contains($id, "pelle")){
            return "shovel";
        }
        if($value === "hoe" || $value === "houe" || str_contains($id, "hoe") || str_contains($id, "houe")){
            return "hoe";
        }

        return "tool";
    }

    private function getBlockToolTypeForTool(string $toolType) : int{
        return match($toolType){
            "pickaxe" => BlockToolType::PICKAXE,
            "axe" => BlockToolType::AXE,
            "shovel" => BlockToolType::SHOVEL,
            "hoe" => BlockToolType::HOE,
            "sword" => defined(BlockToolType::class . "::SWORD") ? constant(BlockToolType::class . "::SWORD") : 0,
            default => 0
        };
    }

    private function getDefaultToolDamage(string $toolType) : int{
        return match($toolType){
            "sword" => 7,
            "axe" => 6,
            "pickaxe" => 5,
            "shovel" => 4,
            "hoe" => 1,
            default => 1
        };
    }

    private function getDefaultToolDurability(string $toolType) : int{
        return match($toolType){
            "sword", "pickaxe", "axe", "shovel", "hoe" => 1562,
            default => 100
        };
    }

    private function getDefaultMiningEfficiency(string $toolType) : float{
        return match($toolType){
            "pickaxe", "axe", "shovel", "hoe" => 8.0,
            "sword" => 1.5,
            default => 1.0
        };
    }

    private function getDefaultHarvestLevel(string $toolType) : int{
        return match($toolType){
            "sword" => 1,
            default => 5
        };
    }

    private function getDefaultDefense(int $armorSlot) : int{
        return match($armorSlot){
            ArmorInventory::SLOT_HEAD => 2,
            ArmorInventory::SLOT_CHEST => 6,
            ArmorInventory::SLOT_LEGS => 5,
            ArmorInventory::SLOT_FEET => 2,
            default => 1
        };
    }

    private function getDefaultDurability(int $armorSlot) : int{
        return match($armorSlot){
            ArmorInventory::SLOT_HEAD => 165,
            ArmorInventory::SLOT_CHEST => 240,
            ArmorInventory::SLOT_LEGS => 225,
            ArmorInventory::SLOT_FEET => 195,
            default => 100
        };
    }

    /** @param array<string, mixed> $data */
    private function makeBasicItemCreativeInfo(array $data) : ?CreativeInventoryInfo{
        return $this->makeCreativeInfo(
            $data,
            "CATEGORY_ITEMS",
            "items",
            "NONE",
            "none"
        );
    }

    /** @param array<string, mixed> $data */
    private function makeToolCreativeInfo(string $toolType, array $data) : ?CreativeInventoryInfo{
        [$groupConstant, $groupFallback] = match(strtolower($toolType)){
            "sword" => ["GROUP_SWORD", "itemGroup.name.sword"],
            "pickaxe" => ["GROUP_PICKAXE", "itemGroup.name.pickaxe"],
            "axe" => ["GROUP_AXE", "itemGroup.name.axe"],
            "shovel" => ["GROUP_SHOVEL", "itemGroup.name.shovel"],
            "hoe" => ["GROUP_HOE", "itemGroup.name.hoe"],
            default => ["NONE", "none"]
        };

        return $this->makeCreativeInfo(
            $data,
            "CATEGORY_EQUIPMENT",
            "equipment",
            $groupConstant,
            $groupFallback
        );
    }

    /** @param array<string, mixed> $data */
    private function makeArmorCreativeInfo(int $armorSlot, array $data) : ?CreativeInventoryInfo{
        [$groupConstant, $groupFallback] = match($armorSlot){
            ArmorInventory::SLOT_HEAD => ["GROUP_HELMET", "itemGroup.name.helmet"],
            ArmorInventory::SLOT_CHEST => ["GROUP_CHESTPLATE", "itemGroup.name.chestplate"],
            ArmorInventory::SLOT_LEGS => ["GROUP_LEGGINGS", "itemGroup.name.leggings"],
            ArmorInventory::SLOT_FEET => ["GROUP_BOOTS", "itemGroup.name.boots"],
            default => ["NONE", "none"]
        };

        return $this->makeCreativeInfo(
            $data,
            "CATEGORY_EQUIPMENT",
            "equipment",
            $groupConstant,
            $groupFallback
        );
    }

    /**
     * Trie automatiquement les créations dans les catégories vanilla du menu créatif.
     * Les champs facultatifs creative_category et creative_group permettent une surcharge manuelle.
     *
     * @param array<string, mixed> $data
     */
    private function makeCreativeInfo(
        array $data,
        string $categoryConstant,
        string $categoryFallback,
        string $groupConstant,
        string $groupFallback
    ) : ?CreativeInventoryInfo{
        if(!class_exists(CreativeInventoryInfo::class)){
            return null;
        }

        $category = trim((string) ($data["creative_category"] ?? ""));
        if($category === ""){
            $category = $this->creativeConstant($categoryConstant, $categoryFallback);
        }

        $group = trim((string) ($data["creative_group"] ?? ""));
        if($group === ""){
            $group = $this->creativeConstant($groupConstant, $groupFallback);
        }

        return new CreativeInventoryInfo($category, $group);
    }

    private function creativeConstant(string $name, string $fallback) : string{
        $constantName = CreativeInventoryInfo::class . "::" . $name;
        return defined($constantName) ? (string) constant($constantName) : $fallback;
    }

    /** @return string[] */
    private function parseStringList(mixed $value) : array{
        if(is_string($value)){
            $value = trim($value);
            return $value === "" ? [] : [$value];
        }
        if(!is_array($value)){
            return [];
        }

        $result = [];
        foreach($value as $entry){
            if(!is_string($entry)){
                continue;
            }
            $entry = trim($entry);
            if($entry !== ""){
                $result[] = $entry;
            }
        }
        return $result;
    }

    /** @return string[] */
    public function getItemKeys() : array{
        $keys = array_keys($this->items);
        if($this->blocksManager !== null){
            $keys = array_values(array_unique(array_merge($keys, $this->blocksManager->getBlockKeys())));
        }
        return $keys;
    }

    public function getIdentifierForKey(string $key) : ?string{
        $key = strtolower($key);
        return $this->items[$key] ?? $this->blocksManager?->getIdentifierForKey($key);
    }

    public function getCustomItem(string $key, int $amount = 1) : ?Item{
        $key = strtolower($key);
        if(!isset($this->items[$key]) && $this->blocksManager !== null){
            $blockItem = $this->blocksManager->getBlockItem($key, $amount);
            if($blockItem instanceof Item){
                return $blockItem;
            }
        }

        $identifier = $this->items[$key] ?? null;
        if($identifier === null){
            return null;
        }

        try{
            $item = CustomiesItemFactory::getInstance()->get($identifier, $amount);
            if($item instanceof Item){
                $this->refreshDurabilityLoreIfNeeded($item);
                return $item;
            }
        }catch(Throwable){
            // Fallback parser en dessous.
        }

        try{
            $item = StringToItemParser::getInstance()->parse($identifier);
            if($item instanceof Item){
                $item->setCount($amount);
                $this->refreshDurabilityLoreIfNeeded($item);
                return $item;
            }
        }catch(Throwable){
            // Rien d'autre à faire.
        }

        return null;
    }
}
