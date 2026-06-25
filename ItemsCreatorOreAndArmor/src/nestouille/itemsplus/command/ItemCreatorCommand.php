<?php

declare(strict_types=1);

namespace nestouille\itemsplus\command;

use nestouille\itemsplus\form\CustomForm;
use nestouille\itemsplus\form\SimpleForm;
use nestouille\itemsplus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use function array_key_exists;
use function in_array;
use function is_array;
use function max;
use function min;
use function preg_replace;
use function strtolower;
use function trim;

final class ItemCreatorCommand implements CommandExecutor{

    /** @var array<int, string> */
    private const TYPE_ORDER = [
        "manage",
        "ore",
        "block",
        "basic",
        "sword",
        "pickaxe",
        "axe",
        "shovel",
        "hoe",
        "helmet",
        "chestplate",
        "leggings",
        "boots"
    ];

    /** @var array<string, string> */
    private const TYPE_NAMES = [
        "manage" => "Modifier / supprimer",
        "basic" => "Item basic",
        "ore" => "Minerai",
        "block" => "Bloc normal",
        "sword" => "Épée",
        "pickaxe" => "Pioche",
        "axe" => "Hache",
        "shovel" => "Pelle",
        "hoe" => "Houe",
        "helmet" => "Casque",
        "chestplate" => "Plastron",
        "leggings" => "Jambières",
        "boots" => "Bottes"
    ];

    public function __construct(private Main $plugin, private ItemManagerCommand $manager){
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "Cette commande doit être utilisée en jeu.");
            return true;
        }

        $normalizedLabel = strtolower($label);
        $requestedType = strtolower(trim((string) ($args[0] ?? "")));

        if($normalizedLabel === "createminerai" || $normalizedLabel === "createore" || in_array($requestedType, ["minerai", "minerais", "ore", "mineral"], true)){
            $this->openMineralForm($sender);
            return true;
        }
        if($normalizedLabel === "createblock" || $normalizedLabel === "createbloc" || in_array($requestedType, ["block", "bloc", "blocks", "blocs"], true)){
            $this->openBlockForm($sender);
            return true;
        }
        if(in_array($requestedType, ["manage", "manager", "modifier", "edit", "gerer"], true)){
            $this->manager->openMainMenu($sender);
            return true;
        }

        $this->openTypeMenu($sender);
        return true;
    }

    private function openTypeMenu(Player $player) : void{
        $form = new SimpleForm(
            "§l§6Créateur d'items",
            "§7Crée un nouvel item ou ouvre le gestionnaire pour modifier et supprimer ceux qui existent déjà.\n\n§7Accès direct : §f/manageitem§7 ou §f/createitem manage§7.\n\n§7Les changements sont sauvegardés dans §fplugin_data/ItemsPlus/config.yml§7 puis nécessitent un redémarrage complet.",
            function(Player $player, int $button) : void{
                $type = self::TYPE_ORDER[$button] ?? null;
                if($type !== null){
                    $this->openCreationForm($player, $type);
                }
            }
        );

        foreach(self::TYPE_ORDER as $type){
            $prefix = $type === "manage" ? "§l§b▶ " : ($type === "ore" ? "§l§a" : "§l§f");
            $suffix = $type === "manage" ? " ◀" : "";
            $form->addButton($prefix . (self::TYPE_NAMES[$type] ?? $type) . $suffix);
        }

        $player->sendForm($form);
    }

    private function openCreationForm(Player $player, string $type) : void{
        if($type === "manage"){
            $this->manager->openMainMenu($player);
            return;
        }
        if($type === "basic"){
            $this->openBasicForm($player);
            return;
        }

        if($type === "ore"){
            $this->openMineralForm($player);
            return;
        }

        if($type === "block"){
            $this->openBlockForm($player);
            return;
        }

        if($this->isArmorType($type)){
            $this->openArmorForm($player, $type);
            return;
        }

        $this->openToolForm($player, $type);
    }

    private function openBasicForm(Player $player) : void{
        $form = new CustomForm("§l§6Créer un item basic", function(Player $player, array $data) : void{
            $key = $this->normalizeIdentifier((string) ($data[1] ?? ""));
            $name = trim((string) ($data[2] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[3] ?? ""));
            $creative = (bool) ($data[4] ?? true);
            $overwrite = (bool) ($data[5] ?? false);

            if(!$this->validateCommon($player, $key, $name, $texture)){
                return;
            }

            $definition = [
                "namespace" => "itemsplus",
                "id" => $key,
                "type" => "item",
                "name" => $name,
                "texture" => $texture,
                "creative" => $creative
            ];

            $this->saveDefinition($player, $key, $definition, $overwrite);
        });

        $form->addLabel("§7La texture PNG et sa clé doivent déjà exister dans le resource pack.")
            ->addInput("Identifiant de l'item", "exemple : lingot_rubis")
            ->addInput("Nom affiché", "exemple : Lingot de Rubis")
            ->addInput("Nom de la texture", "exemple : lingot_rubis")
            ->addToggle("Afficher dans le menu créatif", true)
            ->addToggle("Remplacer la configuration si cet identifiant existe", false);

        $player->sendForm($form);
    }


    private function openBlockForm(Player $player) : void{
        $form = new CustomForm("§l§6Créer un bloc normal", function(Player $player, array $data) : void{
            $key = $this->normalizeIdentifier((string) ($data[1] ?? ""));
            $name = trim((string) ($data[2] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[3] ?? ""));
            $hardness = $this->toFloat($data[4] ?? 1.5, 0.0, 1000.0);
            $creative = (bool) ($data[5] ?? true);
            $overwrite = (bool) ($data[6] ?? false);

            if(!$this->validateCommon($player, $key, $name, $texture)){
                return;
            }

            $definition = [
                "namespace" => "itemsplus",
                "id" => $key,
                "name" => $name,
                "texture" => $texture,
                "hardness" => $hardness,
                "creative" => $creative
            ];

            $this->saveBlockDefinition($player, $key, $definition, $overwrite);
        });

        $form->addLabel("§7Crée un bloc solide normal qui ne se génère pas automatiquement. Quand il est cassé, il redonne son propre bloc. La texture doit déjà être déclarée dans textures/terrain_texture.json et blocks.json du pack unique.")
            ->addInput("Identifiant du bloc", "exemple : bloc_rubis")
            ->addInput("Nom affiché", "exemple : Bloc de Rubis")
            ->addInput("Nom de la texture du bloc", "exemple : bloc_rubis")
            ->addInput("Dureté du bloc", "0 = instantané, pierre ≈ 1.5", "1.5")
            ->addToggle("Afficher dans le menu créatif", true)
            ->addToggle("Remplacer ce bloc s'il existe déjà", false);

        $player->sendForm($form);
    }

    private function openMineralForm(Player $player) : void{
        $form = new CustomForm("§l§6Créer un minerai", function(Player $player, array $data) : void{
            $oreId = $this->normalizeIdentifier((string) ($data[1] ?? ""));
            $name = trim((string) ($data[2] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[3] ?? ""));
            $hardness = $this->toFloat($data[4] ?? 3.0, 0.1, 1000.0);
            $minY = $this->toInt($data[5] ?? -60, -64, 320);
            $maxY = $this->toInt($data[6] ?? 32, -64, 320);
            $veinCount = $this->toInt($data[7] ?? 12, 1, 1000);
            $veinSize = $this->toInt($data[8] ?? 4, 1, 128);
            $dropIdentifier = $this->normalizeDropIdentifier((string) ($data[9] ?? ""));
            $dropMin = $this->toInt($data[10] ?? 1, 0, 64);
            $dropMax = $this->toInt($data[11] ?? 1, 0, 64);
            $dropChance = $this->toInt($data[12] ?? 100, 0, 100);
            $fullMapVeins = $this->toInt($data[13] ?? 1, 1, 64);
            $fullMapChance = $this->toInt($data[14] ?? 65, 1, 100);
            $creative = (bool) ($data[15] ?? true);
            $overwrite = (bool) ($data[16] ?? false);

            if(!$this->validateCommon($player, $oreId, $name, $texture)){
                return;
            }
            if($dropIdentifier === ""){
                $player->sendMessage(TF::RED . "L'identifiant du drop ne peut pas être vide.");
                return;
            }

            if($maxY < $minY){
                [$minY, $maxY] = [$maxY, $minY];
            }
            if($dropMax < $dropMin){
                [$dropMin, $dropMax] = [$dropMax, $dropMin];
            }

            $configKey = str_ends_with($oreId, "_ore") ? substr($oreId, 0, -4) : $oreId;
            if($configKey === ""){
                $player->sendMessage(TF::RED . "L'identifiant du minerai est invalide.");
                return;
            }

            $definition = [
                "namespace" => "itemsplus",
                "ore_id" => $oreId,
                "ore_name" => $name,
                "ore_texture" => $texture,
                "hardness" => $hardness,
                "min_y" => $minY,
                "max_y" => $maxY,
                "vein_count" => $veinCount,
                "vein_size" => $veinSize,
                "drop_amount" => max(1, $dropMin),
                "drops" => [[
                    "item" => $dropIdentifier,
                    "min" => $dropMin,
                    "max" => $dropMax,
                    "chance" => $dropChance
                ]],
                "full_map_veins_per_chunk" => $fullMapVeins,
                "full_map_chance_percent" => $fullMapChance,
                "creative" => $creative
            ];

            $this->saveMineralDefinition($player, $configKey, $definition, $overwrite);
        });

        $form->addLabel("§7Crée un bloc minerai Customies et configure sa génération. La clé de texture doit déjà exister dans textures/terrain_texture.json du pack unique. Crée d'abord le drop avec le type Item basic si nécessaire.")
            ->addInput("Identifiant du bloc minerai", "exemple : rubis_ore")
            ->addInput("Nom affiché", "exemple : Minerai de Rubis")
            ->addInput("Nom de la texture du bloc", "exemple : rubis_ore")
            ->addInput("Dureté du bloc", "exemple : 3.0", "3.0")
            ->addInput("Hauteur minimale Y", "-64 à 320", "-60")
            ->addInput("Hauteur maximale Y", "-64 à 320", "32")
            ->addInput("Nombre de filons avec /minerais c", "exemple : 12", "12")
            ->addInput("Taille d'un filon", "exemple : 4", "4")
            ->addInput("Identifiant de l'item obtenu", "exemple : itemsplus:rubis ou minecraft:raw_iron")
            ->addInput("Quantité minimale du drop", "0 à 64", "1")
            ->addInput("Quantité maximale du drop", "0 à 64", "1")
            ->addInput("Chance du drop en %", "0 à 100", "100")
            ->addInput("Filons par chunk pour la génération de map", "exemple : 1", "1")
            ->addInput("Chance de génération par chunk en %", "1 à 100", "65")
            ->addToggle("Afficher dans le menu créatif", true)
            ->addToggle("Remplacer ce minerai s'il existe déjà", false);

        $player->sendForm($form);
    }

    private function openToolForm(Player $player, string $toolType) : void{
        $typeName = self::TYPE_NAMES[$toolType] ?? "Outil";
        $defaults = $this->getToolDefaults($toolType);

        $form = new CustomForm("§l§6Créer : " . $typeName, function(Player $player, array $data) use ($toolType) : void{
            $key = $this->normalizeIdentifier((string) ($data[1] ?? ""));
            $name = trim((string) ($data[2] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[3] ?? ""));
            $durability = $this->toInt($data[4] ?? 1562, 1, 1000000);
            $damage = $this->toInt($data[5] ?? 1, 0, 10000);
            $breakingSpeed = $this->toFloat($data[6] ?? 8.0, 0.1, 1000.0);
            $harvestLevel = $this->toInt($data[7] ?? 5, 1, 1000);
            $enchantability = $this->toInt($data[8] ?? 10, 0, 1000);
            $creative = (bool) ($data[9] ?? true);
            $overwrite = (bool) ($data[10] ?? false);

            if(!$this->validateCommon($player, $key, $name, $texture)){
                return;
            }

            $definition = [
                "namespace" => "itemsplus",
                "id" => $key,
                "type" => "tool",
                "tool_type" => $toolType,
                "name" => $name,
                "texture" => $texture,
                "damage" => $damage,
                "durability" => $durability,
                "breaking_speed" => $breakingSpeed,
                "harvest_level" => $harvestLevel,
                "enchantability" => $enchantability,
                "block_durability_damage" => $toolType === "sword" ? 2 : 1,
                "entity_durability_damage" => $toolType === "hoe" || $toolType === "sword" ? 1 : 2,
                "creative" => $creative
            ];

            $this->saveDefinition($player, $key, $definition, $overwrite);
        });

        $form->addLabel("§7Après création, l'infobulle affichera uniquement le nom de l'item et sa durabilité. La texture doit déjà exister dans le resource pack.")
            ->addInput("Identifiant de l'item", "exemple : " . $toolType . "_rubis")
            ->addInput("Nom affiché", "exemple : " . $typeName . " en Rubis")
            ->addInput("Nom de la texture", "exemple : " . $toolType . "_rubis")
            ->addInput("Durabilité maximale", "1 à 1 000 000", (string) $defaults["durability"])
            ->addInput("Dégâts", "0 à 10 000", (string) $defaults["damage"])
            ->addInput("Vitesse de minage", "exemple : 8", (string) $defaults["breaking_speed"])
            ->addInput("Niveau de récolte", "exemple : 5", (string) $defaults["harvest_level"])
            ->addInput("Enchantabilité", "exemple : 10", (string) $defaults["enchantability"])
            ->addToggle("Afficher dans le menu créatif", true)
            ->addToggle("Remplacer la configuration si cet identifiant existe", false);

        $player->sendForm($form);
    }

    private function openArmorForm(Player $player, string $slot) : void{
        $typeName = self::TYPE_NAMES[$slot] ?? "Armure";
        $defaults = $this->getArmorDefaults($slot);

        $form = new CustomForm("§l§6Créer : " . $typeName, function(Player $player, array $data) use ($slot) : void{
            $key = $this->normalizeIdentifier((string) ($data[1] ?? ""));
            $name = trim((string) ($data[2] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[3] ?? ""));
            $durability = $this->toInt($data[4] ?? 200, 1, 1000000);
            $defense = $this->toInt($data[5] ?? 2, 0, 10000);
            $toughness = $this->toInt($data[6] ?? 0, 0, 10000);
            $creative = (bool) ($data[7] ?? true);
            $overwrite = (bool) ($data[8] ?? false);

            if(!$this->validateCommon($player, $key, $name, $texture)){
                return;
            }

            $definition = [
                "namespace" => "itemsplus",
                "id" => $key,
                "type" => "armor",
                "slot" => $slot,
                "name" => $name,
                "texture" => $texture,
                "defense" => $defense,
                "durability" => $durability,
                "toughness" => $toughness,
                "creative" => $creative
            ];

            $this->saveDefinition($player, $key, $definition, $overwrite);
        });

        $form->addLabel("§7Après création, l'infobulle affichera uniquement le nom de l'armure et sa durabilité. La texture doit déjà exister dans le resource pack.")
            ->addInput("Identifiant de l'item", "exemple : " . $slot . "_rubis")
            ->addInput("Nom affiché", "exemple : " . $typeName . " en Rubis")
            ->addInput("Nom de la texture", "exemple : " . $slot . "_rubis")
            ->addInput("Durabilité maximale", "1 à 1 000 000", (string) $defaults["durability"])
            ->addInput("Points de défense", "0 à 10 000", (string) $defaults["defense"])
            ->addInput("Résistance / toughness", "0 à 10 000", (string) $defaults["toughness"])
            ->addToggle("Afficher dans le menu créatif", true)
            ->addToggle("Remplacer la configuration si cet identifiant existe", false);

        $player->sendForm($form);
    }

    /** @param array<string, mixed> $definition */
    private function saveDefinition(Player $player, string $key, array $definition, bool $overwrite) : void{
        $config = $this->plugin->getConfig();
        $items = $config->get("items", []);
        if(!is_array($items)){
            $items = [];
        }

        if(array_key_exists($key, $items) && !$overwrite){
            $player->sendMessage(TF::RED . "Un item nommé '" . $key . "' existe déjà. Recommence en activant l'option de remplacement.");
            return;
        }

        $newDisplayName = strtolower(trim((string) ($definition["name"] ?? "")));
        foreach($items as $existingKey => $existingDefinition){
            if(!is_array($existingDefinition) || (string) $existingKey === $key){
                continue;
            }

            $existingId = strtolower((string) ($existingDefinition["id"] ?? $existingKey));
            if($existingId === $key){
                $player->sendMessage(TF::RED . "L'identifiant Customies itemsplus:" . $key . " est déjà utilisé par '" . (string) $existingKey . "'.");
                return;
            }

            $existingDisplayName = strtolower(trim((string) ($existingDefinition["name"] ?? "")));
            if($newDisplayName !== "" && $existingDisplayName === $newDisplayName){
                $player->sendMessage(TF::RED . "Le nom affiché est déjà utilisé par l'item '" . (string) $existingKey . "'. Choisis un nom différent.");
                return;
            }
        }

        $blocks = $config->get("blocks", []);
        if(is_array($blocks)){
            foreach($blocks as $blockKey => $blockDefinition){
                if(!is_array($blockDefinition)){
                    continue;
                }
                $blockIdentifier = strtolower((string) ($blockDefinition["namespace"] ?? "itemsplus")) . ":" . strtolower((string) ($blockDefinition["id"] ?? $blockKey));
                if($blockIdentifier === "itemsplus:" . $key){
                    $player->sendMessage(TF::RED . "L'identifiant itemsplus:" . $key . " est déjà utilisé par le bloc normal '" . (string) $blockKey . "'.");
                    return;
                }
            }
        }

        $items[$key] = $definition;
        $config->set("items", $items);
        $deleted = $config->get("deleted-items", []);
        if(is_array($deleted)){
            $config->set("deleted-items", array_values(array_filter($deleted, static fn(mixed $entry) : bool => (string) $entry !== $key)));
        }
        $config->save();

        $player->sendMessage(TF::GREEN . "✓ Item '" . (string) ($definition["name"] ?? $key) . "' enregistré.");
        $player->sendMessage(TF::GRAY . "Identifiant : itemsplus:" . $key);
        $player->sendMessage(TF::GRAY . "Texture : " . (string) ($definition["texture"] ?? $key));
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour enregistrer le nouvel identifiant Customies.");
    }


    /** @param array<string, mixed> $definition */
    private function saveBlockDefinition(Player $player, string $key, array $definition, bool $overwrite) : void{
        $config = $this->plugin->getConfig();
        $blocks = $config->get("blocks", []);
        if(!is_array($blocks)){
            $blocks = [];
        }

        if(array_key_exists($key, $blocks) && !$overwrite){
            $player->sendMessage(TF::RED . "Un bloc nommé '" . $key . "' existe déjà. Recommence en activant l'option de remplacement.");
            return;
        }

        $namespace = strtolower((string) ($definition["namespace"] ?? "itemsplus"));
        $id = strtolower((string) ($definition["id"] ?? $key));
        $identifier = $namespace . ":" . $id;
        $newDisplayName = strtolower(trim((string) ($definition["name"] ?? "")));

        foreach($blocks as $existingKey => $existingDefinition){
            if(!is_array($existingDefinition) || (string) $existingKey === $key){
                continue;
            }
            $existingIdentifier = strtolower((string) ($existingDefinition["namespace"] ?? "itemsplus")) . ":" . strtolower((string) ($existingDefinition["id"] ?? $existingKey));
            if($existingIdentifier === $identifier){
                $player->sendMessage(TF::RED . "L'identifiant Customies " . $identifier . " est déjà utilisé par le bloc '" . (string) $existingKey . "'.");
                return;
            }
            if($newDisplayName !== "" && strtolower(trim((string) ($existingDefinition["name"] ?? ""))) === $newDisplayName){
                $player->sendMessage(TF::RED . "Le nom affiché est déjà utilisé par le bloc '" . (string) $existingKey . "'.");
                return;
            }
        }

        $items = $config->get("items", []);
        if(is_array($items)){
            foreach($items as $itemKey => $itemDefinition){
                if(!is_array($itemDefinition)){
                    continue;
                }
                $itemIdentifier = strtolower((string) ($itemDefinition["namespace"] ?? "itemsplus")) . ":" . strtolower((string) ($itemDefinition["id"] ?? $itemKey));
                if($itemIdentifier === $identifier){
                    $player->sendMessage(TF::RED . "L'identifiant " . $identifier . " est déjà utilisé par l'item '" . (string) $itemKey . "'.");
                    return;
                }
            }
        }

        $minerals = $config->get("minerals", []);
        if(is_array($minerals)){
            foreach($minerals as $mineralKey => $mineralDefinition){
                if(!is_array($mineralDefinition)){
                    continue;
                }
                $mineralIdentifier = strtolower((string) ($mineralDefinition["namespace"] ?? "mineraisplus")) . ":" . strtolower((string) ($mineralDefinition["ore_id"] ?? ((string) $mineralKey . "_ore")));
                if($mineralIdentifier === $identifier){
                    $player->sendMessage(TF::RED . "L'identifiant " . $identifier . " est déjà utilisé par le minerai '" . (string) $mineralKey . "'.");
                    return;
                }
            }
        }

        $blocks[$key] = $definition;
        $config->set("blocks", $blocks);
        $deleted = $config->get("deleted-blocks", []);
        if(is_array($deleted)){
            $config->set("deleted-blocks", array_values(array_filter($deleted, static fn(mixed $entry) : bool => (string) $entry !== $key)));
        }
        $config->set("config-version", max(6, (int) $config->get("config-version", 0)));
        $config->save();

        $player->sendMessage(TF::GREEN . "✓ Bloc '" . (string) ($definition["name"] ?? $key) . "' enregistré.");
        $player->sendMessage(TF::GRAY . "Identifiant : " . $identifier);
        $player->sendMessage(TF::GRAY . "Texture de terrain : " . (string) ($definition["texture"] ?? $id));
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour enregistrer le nouveau bloc Customies.");
    }

    /** @param array<string, mixed> $definition */
    private function saveMineralDefinition(Player $player, string $key, array $definition, bool $overwrite) : void{
        $config = $this->plugin->getConfig();
        $minerals = $config->get("minerals", []);
        if(!is_array($minerals)){
            $minerals = [];
        }

        if(array_key_exists($key, $minerals) && !$overwrite){
            $player->sendMessage(TF::RED . "Un minerai nommé '" . $key . "' existe déjà. Recommence en activant l'option de remplacement.");
            return;
        }

        $namespace = strtolower((string) ($definition["namespace"] ?? "itemsplus"));
        $oreId = strtolower((string) ($definition["ore_id"] ?? ""));
        $identifier = $namespace . ":" . $oreId;
        $newDisplayName = strtolower(trim((string) ($definition["ore_name"] ?? "")));

        foreach($minerals as $existingKey => $existingDefinition){
            if(!is_array($existingDefinition) || (string) $existingKey === $key){
                continue;
            }

            $existingNamespace = strtolower((string) ($existingDefinition["namespace"] ?? "mineraisplus"));
            $existingOreId = strtolower((string) ($existingDefinition["ore_id"] ?? ((string) $existingKey . "_ore")));
            if($existingNamespace . ":" . $existingOreId === $identifier){
                $player->sendMessage(TF::RED . "L'identifiant Customies " . $identifier . " est déjà utilisé par le minerai '" . (string) $existingKey . "'.");
                return;
            }

            $existingDisplayName = strtolower(trim((string) ($existingDefinition["ore_name"] ?? "")));
            if($newDisplayName !== "" && $existingDisplayName === $newDisplayName){
                $player->sendMessage(TF::RED . "Le nom affiché est déjà utilisé par le minerai '" . (string) $existingKey . "'.");
                return;
            }
        }

        $items = $config->get("items", []);
        if(is_array($items)){
            foreach($items as $itemKey => $itemDefinition){
                if(!is_array($itemDefinition)){
                    continue;
                }
                $itemNamespace = strtolower((string) ($itemDefinition["namespace"] ?? "itemsplus"));
                $itemId = strtolower((string) ($itemDefinition["id"] ?? $itemKey));
                if($itemNamespace . ":" . $itemId === $identifier){
                    $player->sendMessage(TF::RED . "L'identifiant " . $identifier . " est déjà utilisé par l'item '" . (string) $itemKey . "'.");
                    return;
                }
            }
        }

        $blocks = $config->get("blocks", []);
        if(is_array($blocks)){
            foreach($blocks as $blockKey => $blockDefinition){
                if(!is_array($blockDefinition)){
                    continue;
                }
                $blockIdentifier = strtolower((string) ($blockDefinition["namespace"] ?? "itemsplus")) . ":" . strtolower((string) ($blockDefinition["id"] ?? $blockKey));
                if($blockIdentifier === $identifier){
                    $player->sendMessage(TF::RED . "L'identifiant " . $identifier . " est déjà utilisé par le bloc normal '" . (string) $blockKey . "'.");
                    return;
                }
            }
        }

        $minerals[$key] = $definition;
        $config->set("minerals", $minerals);
        $deleted = $config->get("deleted-minerals", []);
        if(is_array($deleted)){
            $config->set("deleted-minerals", array_values(array_filter($deleted, static fn(mixed $entry) : bool => (string) $entry !== $key)));
        }
        $config->set("config-version", max(5, (int) $config->get("config-version", 0)));
        $config->save();

        $player->sendMessage(TF::GREEN . "✓ Minerai '" . (string) ($definition["ore_name"] ?? $key) . "' enregistré.");
        $player->sendMessage(TF::GRAY . "Identifiant : " . $identifier);
        $player->sendMessage(TF::GRAY . "Texture de terrain : " . (string) ($definition["ore_texture"] ?? $oreId));
        $player->sendMessage(TF::GRAY . "Drop : " . (string) (($definition["drops"][0]["item"] ?? "inconnu")));
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour enregistrer le nouveau bloc Customies.");
    }

    private function normalizeDropIdentifier(string $value) : string{
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/u', '_', $value) ?? "";
        return preg_replace('/[^a-z0-9_:\/.]/', '', $value) ?? "";
    }

    private function validateCommon(Player $player, string $key, string $name, string $texture) : bool{
        if($key === ""){
            $player->sendMessage(TF::RED . "L'identifiant est invalide. Utilise uniquement des lettres minuscules, chiffres et underscores.");
            return false;
        }
        if($name === ""){
            $player->sendMessage(TF::RED . "Le nom affiché ne peut pas être vide.");
            return false;
        }
        if($texture === ""){
            $player->sendMessage(TF::RED . "Le nom de la texture ne peut pas être vide.");
            return false;
        }
        return true;
    }

    private function normalizeIdentifier(string $value) : string{
        $value = strtolower(trim($value));
        $value = preg_replace('/[\\s\\-]+/u', '_', $value) ?? "";
        $value = preg_replace('/[^a-z0-9_]/', '', $value) ?? "";
        $value = preg_replace('/_+/', '_', $value) ?? "";
        return trim($value, "_");
    }

    private function normalizeTexture(string $value) : string{
        $value = strtolower(trim($value));
        if(str_contains($value, ":")){
            $parts = explode(":", $value, 2);
            $value = (string) ($parts[1] ?? "");
        }
        $value = preg_replace('/[\\s\\-]+/u', '_', $value) ?? "";
        return preg_replace('/[^a-z0-9_\\/.]/', '', $value) ?? "";
    }

    private function toInt(mixed $value, int $minimum, int $maximum) : int{
        return max($minimum, min($maximum, (int) $value));
    }

    private function toFloat(mixed $value, float $minimum, float $maximum) : float{
        return max($minimum, min($maximum, (float) $value));
    }

    private function isArmorType(string $type) : bool{
        return $type === "helmet" || $type === "chestplate" || $type === "leggings" || $type === "boots";
    }

    /** @return array{durability: int, damage: int, breaking_speed: float, harvest_level: int, enchantability: int} */
    private function getToolDefaults(string $toolType) : array{
        return match($toolType){
            "sword" => ["durability" => 1562, "damage" => 7, "breaking_speed" => 1.5, "harvest_level" => 1, "enchantability" => 10],
            "pickaxe" => ["durability" => 1562, "damage" => 5, "breaking_speed" => 8.0, "harvest_level" => 5, "enchantability" => 10],
            "axe" => ["durability" => 1562, "damage" => 6, "breaking_speed" => 8.0, "harvest_level" => 5, "enchantability" => 10],
            "shovel" => ["durability" => 1562, "damage" => 4, "breaking_speed" => 8.0, "harvest_level" => 5, "enchantability" => 10],
            "hoe" => ["durability" => 1562, "damage" => 1, "breaking_speed" => 8.0, "harvest_level" => 5, "enchantability" => 10],
            default => ["durability" => 100, "damage" => 1, "breaking_speed" => 1.0, "harvest_level" => 1, "enchantability" => 10]
        };
    }

    /** @return array{durability: int, defense: int, toughness: int} */
    private function getArmorDefaults(string $slot) : array{
        return match($slot){
            "helmet" => ["durability" => 165, "defense" => 2, "toughness" => 0],
            "chestplate" => ["durability" => 240, "defense" => 6, "toughness" => 0],
            "leggings" => ["durability" => 225, "defense" => 5, "toughness" => 0],
            "boots" => ["durability" => 195, "defense" => 2, "toughness" => 0],
            default => ["durability" => 100, "defense" => 1, "toughness" => 0]
        };
    }
}
