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
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function max;
use function min;
use function sort;
use function strtolower;
use function trim;

final class ItemManagerCommand implements CommandExecutor{

    public function __construct(private Main $plugin){
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "Cette commande doit être utilisée en jeu.");
            return true;
        }

        $this->openMainMenu($sender);
        return true;
    }

    public function openMainMenu(Player $player) : void{
        if(!$player->hasPermission("itemsplus.manage")){
            $player->sendMessage(TF::RED . "Tu n'as pas la permission de gérer les items.");
            return;
        }

        $config = $this->plugin->getConfig();
        $items = $config->get("items", []);
        $minerals = $config->get("minerals", []);
        $blocks = $config->get("blocks", []);
        $itemCount = is_array($items) ? count($items) : 0;
        $mineralCount = is_array($minerals) ? count($minerals) : 0;
        $blockCount = is_array($blocks) ? count($blocks) : 0;

        $form = new SimpleForm(
            "§l§6Gestion ItemsPlus",
            "§7Modifie les statistiques ou supprime une configuration directement en jeu.\n\n§eLes changements sont sauvegardés immédiatement, mais un redémarrage complet est obligatoire pour que Customies les applique.",
            function(Player $player, int $button) : void{
                if($button === 0){
                    $this->openItemList($player);
                }elseif($button === 1){
                    $this->openMineralList($player);
                }elseif($button === 2){
                    $this->openBlockList($player);
                }
            }
        );

        $form->addButton("§l§bItems, outils et armures §7(" . $itemCount . ")");
        $form->addButton("§l§aMinerais §7(" . $mineralCount . ")");
        $form->addButton("§l§6Blocs normaux §7(" . $blockCount . ")");
        $player->sendForm($form);
    }

    private function openItemList(Player $player) : void{
        $items = $this->plugin->getConfig()->get("items", []);
        if(!is_array($items) || $items === []){
            $player->sendMessage(TF::YELLOW . "Aucun item n'est configuré.");
            return;
        }

        $keys = array_map(static fn(mixed $key) : string => (string) $key, array_keys($items));
        sort($keys);

        $form = new SimpleForm(
            "§l§6Choisir un item",
            "§7Sélectionne l'item à modifier ou supprimer.",
            function(Player $player, int $button) use ($keys) : void{
                if($button === count($keys)){
                    $this->openMainMenu($player);
                    return;
                }
                $key = $keys[$button] ?? null;
                if($key !== null){
                    $this->openItemActions($player, $key);
                }
            }
        );

        foreach($keys as $key){
            $definition = $items[$key] ?? [];
            if(!is_array($definition)){
                $definition = [];
            }
            $name = (string) ($definition["name"] ?? $key);
            $type = $this->friendlyItemType($definition);
            $form->addButton("§f" . $name . "\n§7" . $type . " • " . $key);
        }
        $form->addButton("§cRetour");
        $player->sendForm($form);
    }

    private function openMineralList(Player $player) : void{
        $minerals = $this->plugin->getConfig()->get("minerals", []);
        if(!is_array($minerals) || $minerals === []){
            $player->sendMessage(TF::YELLOW . "Aucun minerai n'est configuré.");
            return;
        }

        $keys = array_map(static fn(mixed $key) : string => (string) $key, array_keys($minerals));
        sort($keys);

        $form = new SimpleForm(
            "§l§6Choisir un minerai",
            "§7Sélectionne le minerai à modifier ou supprimer.",
            function(Player $player, int $button) use ($keys) : void{
                if($button === count($keys)){
                    $this->openMainMenu($player);
                    return;
                }
                $key = $keys[$button] ?? null;
                if($key !== null){
                    $this->openMineralActions($player, $key);
                }
            }
        );

        foreach($keys as $key){
            $definition = $minerals[$key] ?? [];
            if(!is_array($definition)){
                $definition = [];
            }
            $name = (string) ($definition["ore_name"] ?? $key);
            $id = (string) ($definition["ore_id"] ?? ($key . "_ore"));
            $form->addButton("§f" . $name . "\n§7Minerai • " . $id);
        }
        $form->addButton("§cRetour");
        $player->sendForm($form);
    }


    private function openBlockList(Player $player) : void{
        $blocks = $this->plugin->getConfig()->get("blocks", []);
        if(!is_array($blocks) || $blocks === []){
            $player->sendMessage(TF::YELLOW . "Aucun bloc normal n'est configuré.");
            return;
        }

        $keys = array_map(static fn(mixed $key) : string => (string) $key, array_keys($blocks));
        sort($keys);

        $form = new SimpleForm(
            "§l§6Choisir un bloc normal",
            "§7Sélectionne le bloc à modifier ou supprimer.",
            function(Player $player, int $button) use ($keys) : void{
                if($button === count($keys)){
                    $this->openMainMenu($player);
                    return;
                }
                $key = $keys[$button] ?? null;
                if($key !== null){
                    $this->openBlockActions($player, $key);
                }
            }
        );

        foreach($keys as $key){
            $definition = $blocks[$key] ?? [];
            if(!is_array($definition)){
                $definition = [];
            }
            $name = (string) ($definition["name"] ?? $key);
            $id = (string) ($definition["id"] ?? $key);
            $form->addButton("§f" . $name . "\n§7Bloc normal • " . $id);
        }
        $form->addButton("§cRetour");
        $player->sendForm($form);
    }

    private function openItemActions(Player $player, string $key) : void{
        $definition = $this->getItemDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Cet item n'existe plus dans la configuration.");
            return;
        }

        $name = (string) ($definition["name"] ?? $key);
        $identifier = (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key);
        $form = new SimpleForm(
            "§l§6" . $name,
            "§7Identifiant : §f" . $identifier . "\n§7Type : §f" . $this->friendlyItemType($definition) . "\n\n§eL'identifiant ne peut pas être changé afin d'éviter de casser les items déjà existants.",
            function(Player $player, int $button) use ($key) : void{
                if($button === 0){
                    $this->openItemEditForm($player, $key);
                }elseif($button === 1){
                    $this->openDeleteConfirmation($player, "item", $key);
                }else{
                    $this->openItemList($player);
                }
            }
        );
        $form->addButton("§aModifier les valeurs");
        $form->addButton("§cSupprimer l'item");
        $form->addButton("§7Retour");
        $player->sendForm($form);
    }

    private function openMineralActions(Player $player, string $key) : void{
        $definition = $this->getMineralDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Ce minerai n'existe plus dans la configuration.");
            return;
        }

        $name = (string) ($definition["ore_name"] ?? $key);
        $identifier = (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["ore_id"] ?? ($key . "_ore"));
        $form = new SimpleForm(
            "§l§6" . $name,
            "§7Identifiant : §f" . $identifier . "\n§7Type : §fMinerai\n\n§eL'identifiant ne peut pas être changé afin d'éviter de corrompre les blocs déjà présents.",
            function(Player $player, int $button) use ($key) : void{
                if($button === 0){
                    $this->openMineralEditForm($player, $key);
                }elseif($button === 1){
                    $this->openDeleteConfirmation($player, "mineral", $key);
                }else{
                    $this->openMineralList($player);
                }
            }
        );
        $form->addButton("§aModifier les valeurs");
        $form->addButton("§cSupprimer le minerai");
        $form->addButton("§7Retour");
        $player->sendForm($form);
    }


    private function openBlockActions(Player $player, string $key) : void{
        $definition = $this->getBlockDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Ce bloc normal n'existe plus dans la configuration.");
            return;
        }

        $name = (string) ($definition["name"] ?? $key);
        $identifier = (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key);
        $form = new SimpleForm(
            "§l§6" . $name,
            "§7Identifiant : §f" . $identifier . "\n§7Type : §fBloc normal\n\n§eL'identifiant ne peut pas être changé afin d'éviter de corrompre les blocs déjà placés.",
            function(Player $player, int $button) use ($key) : void{
                if($button === 0){
                    $this->openBlockEditForm($player, $key);
                }elseif($button === 1){
                    $this->openDeleteConfirmation($player, "block", $key);
                }else{
                    $this->openBlockList($player);
                }
            }
        );
        $form->addButton("§aModifier les valeurs");
        $form->addButton("§cSupprimer le bloc");
        $form->addButton("§7Retour");
        $player->sendForm($form);
    }

    private function openItemEditForm(Player $player, string $key) : void{
        $definition = $this->getItemDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Cet item n'existe plus dans la configuration.");
            return;
        }

        $type = strtolower((string) ($definition["type"] ?? "item"));
        if($type === "armor" || $type === "armure"){
            $this->openArmorEditForm($player, $key, $definition);
            return;
        }
        if($type === "tool" || $type === "outil" || $type === "weapon" || $type === "arme"){
            $this->openToolEditForm($player, $key, $definition);
            return;
        }
        $this->openBasicEditForm($player, $key, $definition);
    }

    /** @param array<string, mixed> $definition */
    private function openBasicEditForm(Player $player, string $key, array $definition) : void{
        $form = new CustomForm("§l§6Modifier : " . (string) ($definition["name"] ?? $key), function(Player $player, array $data) use ($key, $definition) : void{
            $name = trim((string) ($data[1] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[2] ?? ""));
            if($name === "" || $texture === ""){
                $player->sendMessage(TF::RED . "Le nom et la texture ne peuvent pas être vides.");
                return;
            }
            $updated = $definition;
            $updated["name"] = $name;
            $updated["texture"] = $texture;
            $updated["creative"] = (bool) ($data[3] ?? true);
            $updated["fireproof"] = (bool) ($data[4] ?? false);
            $this->saveItemEdit($player, $key, $updated);
        });

        $form->addLabel("§7Identifiant verrouillé : §f" . (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key))
            ->addInput("Nom affiché", "", (string) ($definition["name"] ?? $key))
            ->addInput("Nom de la texture", "", (string) ($definition["texture"] ?? $key))
            ->addToggle("Afficher dans le menu créatif", (bool) ($definition["creative"] ?? true))
            ->addToggle("Résiste au feu / lave", (bool) ($definition["fireproof"] ?? $definition["fireProof"] ?? false));
        $player->sendForm($form);
    }

    /** @param array<string, mixed> $definition */
    private function openToolEditForm(Player $player, string $key, array $definition) : void{
        $form = new CustomForm("§l§6Modifier : " . (string) ($definition["name"] ?? $key), function(Player $player, array $data) use ($key, $definition) : void{
            $name = trim((string) ($data[1] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[2] ?? ""));
            if($name === "" || $texture === ""){
                $player->sendMessage(TF::RED . "Le nom et la texture ne peuvent pas être vides.");
                return;
            }

            $updated = $definition;
            $updated["name"] = $name;
            $updated["texture"] = $texture;
            $updated["durability"] = $this->toInt($data[3] ?? 1, 1, 1000000);
            $updated["damage"] = $this->toInt($data[4] ?? 0, 0, 10000);
            $updated["breaking_speed"] = $this->toFloat($data[5] ?? 1, 0.1, 1000.0);
            $updated["harvest_level"] = $this->toInt($data[6] ?? 1, 1, 1000);
            $updated["enchantability"] = $this->toInt($data[7] ?? 0, 0, 1000);
            $updated["block_durability_damage"] = $this->toInt($data[8] ?? 1, 0, 1000);
            $updated["entity_durability_damage"] = $this->toInt($data[9] ?? 1, 0, 1000);
            $updated["armor_durability_damage"] = $this->toInt($data[10] ?? -1, -1, 1000);
            $updated["digger_tags"] = $this->parseTags((string) ($data[11] ?? ""));
            $updated["creative"] = (bool) ($data[12] ?? true);
            $updated["fireproof"] = (bool) ($data[13] ?? false);
            $this->saveItemEdit($player, $key, $updated);
        });

        $tags = $definition["digger_tags"] ?? $definition["diggerTags"] ?? [];
        $tagText = is_array($tags) ? implode(", ", array_map(static fn(mixed $tag) : string => (string) $tag, $tags)) : (string) $tags;
        $form->addLabel("§7Identifiant verrouillé : §f" . (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key) . "\n§7Type d'outil : §f" . (string) ($definition["tool_type"] ?? "outil"))
            ->addInput("Nom affiché", "", (string) ($definition["name"] ?? $key))
            ->addInput("Nom de la texture", "", (string) ($definition["texture"] ?? $key))
            ->addInput("Durabilité maximale", "1 à 1 000 000", (string) ($definition["durability"] ?? 100))
            ->addInput("Dégâts", "0 à 10 000", (string) ($definition["damage"] ?? 1))
            ->addInput("Vitesse de minage", "0.1 à 1000", (string) ($definition["breaking_speed"] ?? 1.0))
            ->addInput("Niveau de récolte", "1 à 1000", (string) ($definition["harvest_level"] ?? 1))
            ->addInput("Enchantabilité", "0 à 1000", (string) ($definition["enchantability"] ?? 10))
            ->addInput("Durabilité perdue sur un bloc", "0 à 1000", (string) ($definition["block_durability_damage"] ?? 1))
            ->addInput("Durabilité perdue sur une entité", "0 à 1000", (string) ($definition["entity_durability_damage"] ?? 1))
            ->addInput("Usure maximale aléatoire de chaque pièce d'armure", "-1 = automatique, 1 = toujours 1, 2 = aléatoirement 1 ou 2", (string) ($definition["armor_durability_damage"] ?? -1))
            ->addInput("Tags de blocs, séparés par des virgules", "exemple : mineable/pickaxe", $tagText)
            ->addToggle("Afficher dans le menu créatif", (bool) ($definition["creative"] ?? true))
            ->addToggle("Résiste au feu / lave", (bool) ($definition["fireproof"] ?? $definition["fireProof"] ?? false));
        $player->sendForm($form);
    }

    /** @param array<string, mixed> $definition */
    private function openArmorEditForm(Player $player, string $key, array $definition) : void{
        $form = new CustomForm("§l§6Modifier : " . (string) ($definition["name"] ?? $key), function(Player $player, array $data) use ($key, $definition) : void{
            $name = trim((string) ($data[1] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[2] ?? ""));
            if($name === "" || $texture === ""){
                $player->sendMessage(TF::RED . "Le nom et la texture ne peuvent pas être vides.");
                return;
            }

            $updated = $definition;
            $updated["name"] = $name;
            $updated["texture"] = $texture;
            $updated["durability"] = $this->toInt($data[3] ?? 1, 1, 1000000);
            $updated["defense"] = $this->toInt($data[4] ?? 0, 0, 10000);
            $updated["toughness"] = $this->toInt($data[5] ?? 0, 0, 10000);
            $updated["creative"] = (bool) ($data[6] ?? true);
            $updated["fireproof"] = (bool) ($data[7] ?? false);
            $this->saveItemEdit($player, $key, $updated);
        });

        $form->addLabel("§7Identifiant verrouillé : §f" . (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key) . "\n§7Emplacement : §f" . (string) ($definition["slot"] ?? "armure"))
            ->addInput("Nom affiché", "", (string) ($definition["name"] ?? $key))
            ->addInput("Nom de la texture", "", (string) ($definition["texture"] ?? $key))
            ->addInput("Durabilité maximale", "1 à 1 000 000", (string) ($definition["durability"] ?? 100))
            ->addInput("Points de défense", "0 à 10 000", (string) ($definition["defense"] ?? 1))
            ->addInput("Résistance / toughness", "0 à 10 000", (string) ($definition["toughness"] ?? 0))
            ->addToggle("Afficher dans le menu créatif", (bool) ($definition["creative"] ?? true))
            ->addToggle("Résiste au feu / lave", (bool) ($definition["fireproof"] ?? $definition["fireProof"] ?? false));
        $player->sendForm($form);
    }


    private function openBlockEditForm(Player $player, string $key) : void{
        $definition = $this->getBlockDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Ce bloc normal n'existe plus dans la configuration.");
            return;
        }

        $form = new CustomForm("§l§6Modifier : " . (string) ($definition["name"] ?? $key), function(Player $player, array $data) use ($key, $definition) : void{
            $name = trim((string) ($data[1] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[2] ?? ""));
            if($name === "" || $texture === ""){
                $player->sendMessage(TF::RED . "Le nom et la texture ne peuvent pas être vides.");
                return;
            }

            $updated = $definition;
            $updated["name"] = $name;
            $updated["texture"] = $texture;
            $updated["hardness"] = $this->toFloat($data[3] ?? 1.5, 0.0, 1000.0);
            $updated["creative"] = (bool) ($data[4] ?? true);
            $this->saveBlockEdit($player, $key, $updated);
        });

        $form->addLabel("§7Identifiant verrouillé : §f" . (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["id"] ?? $key) . "\n§7Ce bloc ne se génère pas automatiquement et redonne son propre bloc quand il est cassé.")
            ->addInput("Nom affiché", "", (string) ($definition["name"] ?? $key))
            ->addInput("Nom de la texture du bloc", "", (string) ($definition["texture"] ?? $key))
            ->addInput("Dureté du bloc", "0 à 1000", (string) ($definition["hardness"] ?? 1.5))
            ->addToggle("Afficher dans le menu créatif", (bool) ($definition["creative"] ?? true));
        $player->sendForm($form);
    }

    private function openMineralEditForm(Player $player, string $key) : void{
        $definition = $this->getMineralDefinition($key);
        if($definition === null){
            $player->sendMessage(TF::RED . "Ce minerai n'existe plus dans la configuration.");
            return;
        }

        $drops = $definition["drops"] ?? [];
        if(!is_array($drops)){
            $drops = [];
        }
        $firstDrop = $drops[0] ?? [];
        if(!is_array($firstDrop)){
            $firstDrop = [];
        }
        $dropMin = (int) ($firstDrop["min"] ?? $firstDrop["amount"] ?? $definition["drop_amount"] ?? 1);
        $dropMax = (int) ($firstDrop["max"] ?? $firstDrop["amount"] ?? $definition["drop_amount"] ?? 1);

        $form = new CustomForm("§l§6Modifier : " . (string) ($definition["ore_name"] ?? $key), function(Player $player, array $data) use ($key, $definition, $drops) : void{
            $name = trim((string) ($data[1] ?? ""));
            $texture = $this->normalizeTexture((string) ($data[2] ?? ""));
            $dropIdentifier = strtolower(trim((string) ($data[8] ?? "")));
            if($name === "" || $texture === "" || $dropIdentifier === ""){
                $player->sendMessage(TF::RED . "Le nom, la texture et l'identifiant du drop ne peuvent pas être vides.");
                return;
            }

            $minY = $this->toInt($data[4] ?? -64, -64, 320);
            $maxY = $this->toInt($data[5] ?? 320, -64, 320);
            if($maxY < $minY){
                [$minY, $maxY] = [$maxY, $minY];
            }
            $dropMin = $this->toInt($data[9] ?? 1, 0, 64);
            $dropMax = $this->toInt($data[10] ?? 1, 0, 64);
            if($dropMax < $dropMin){
                [$dropMin, $dropMax] = [$dropMax, $dropMin];
            }

            $updated = $definition;
            $updated["ore_name"] = $name;
            $updated["ore_texture"] = $texture;
            $updated["hardness"] = $this->toFloat($data[3] ?? 3.0, 0.1, 1000.0);
            $updated["min_y"] = $minY;
            $updated["max_y"] = $maxY;
            $updated["vein_count"] = $this->toInt($data[6] ?? 1, 1, 1000);
            $updated["vein_size"] = $this->toInt($data[7] ?? 1, 1, 128);
            $updated["drop_amount"] = max(1, $dropMin);
            $newDrops = $drops;
            $newDrops[0] = [
                "item" => $dropIdentifier,
                "min" => $dropMin,
                "max" => $dropMax,
                "chance" => $this->toInt($data[11] ?? 100, 0, 100)
            ];
            $updated["drops"] = array_values($newDrops);
            $updated["full_map_veins_per_chunk"] = $this->toInt($data[12] ?? 1, 1, 64);
            $updated["full_map_chance_percent"] = $this->toInt($data[13] ?? 65, 1, 100);
            $updated["creative"] = (bool) ($data[14] ?? true);
            $this->saveMineralEdit($player, $key, $updated);
        });

        $form->addLabel("§7Identifiant verrouillé : §f" . (string) ($definition["namespace"] ?? "itemsplus") . ":" . (string) ($definition["ore_id"] ?? ($key . "_ore")))
            ->addInput("Nom affiché", "", (string) ($definition["ore_name"] ?? $key))
            ->addInput("Nom de la texture du bloc", "", (string) ($definition["ore_texture"] ?? $definition["ore_id"] ?? $key))
            ->addInput("Dureté du bloc", "0.1 à 1000", (string) ($definition["hardness"] ?? 3.0))
            ->addInput("Hauteur minimale Y", "-64 à 320", (string) ($definition["min_y"] ?? -60))
            ->addInput("Hauteur maximale Y", "-64 à 320", (string) ($definition["max_y"] ?? 32))
            ->addInput("Nombre de filons avec /minerais c", "1 à 1000", (string) ($definition["vein_count"] ?? 12))
            ->addInput("Taille d'un filon", "1 à 128", (string) ($definition["vein_size"] ?? 4))
            ->addInput("Identifiant de l'item obtenu", "itemsplus:item", (string) ($firstDrop["item"] ?? "minecraft:raw_iron"))
            ->addInput("Quantité minimale du drop", "0 à 64", (string) $dropMin)
            ->addInput("Quantité maximale du drop", "0 à 64", (string) $dropMax)
            ->addInput("Chance du drop en %", "0 à 100", (string) ($firstDrop["chance"] ?? 100))
            ->addInput("Filons par chunk pour la génération de map", "1 à 64", (string) ($definition["full_map_veins_per_chunk"] ?? 1))
            ->addInput("Chance de génération par chunk en %", "1 à 100", (string) ($definition["full_map_chance_percent"] ?? 65))
            ->addToggle("Afficher dans le menu créatif", (bool) ($definition["creative"] ?? true));
        $player->sendForm($form);
    }

    private function openDeleteConfirmation(Player $player, string $kind, string $key) : void{
        $warning = match($kind){
            "mineral" => "§cATTENTION : les blocs de ce minerai déjà présents dans les mondes peuvent devenir des blocs inconnus après le redémarrage. Remplace-les avant de supprimer définitivement la configuration.",
            "block" => "§cATTENTION : les exemplaires de ce bloc déjà placés dans les mondes peuvent devenir des blocs inconnus après le redémarrage.",
            default => "§cATTENTION : les exemplaires de cet item déjà présents dans les inventaires peuvent devenir inconnus après le redémarrage."
        };

        $form = new CustomForm("§l§cConfirmation de suppression", function(Player $player, array $data) use ($kind, $key) : void{
            if(strtoupper(trim((string) ($data[1] ?? ""))) !== "SUPPRIMER"){
                $player->sendMessage(TF::YELLOW . "Suppression annulée : le mot SUPPRIMER n'a pas été saisi correctement.");
                return;
            }
            if($kind === "mineral"){
                $this->deleteMineral($player, $key);
            }elseif($kind === "block"){
                $this->deleteBlock($player, $key);
            }else{
                $this->deleteItem($player, $key);
            }
        });
        $form->addLabel($warning . "\n\n§7Écris exactement §fSUPPRIMER§7 pour confirmer.")
            ->addInput("Confirmation", "SUPPRIMER");
        $player->sendForm($form);
    }

    /** @param array<string, mixed> $updated */
    private function saveItemEdit(Player $player, string $key, array $updated) : void{
        $config = $this->plugin->getConfig();
        $items = $config->get("items", []);
        if(!is_array($items) || !array_key_exists($key, $items)){
            $player->sendMessage(TF::RED . "Cet item n'existe plus dans la configuration.");
            return;
        }
        $items[$key] = $updated;
        $config->set("items", $items);
        $config->set("deleted-items", $this->removeFromList($config->get("deleted-items", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Item modifié dans config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour appliquer les nouvelles valeurs.");
    }


    /** @param array<string, mixed> $updated */
    private function saveBlockEdit(Player $player, string $key, array $updated) : void{
        $config = $this->plugin->getConfig();
        $blocks = $config->get("blocks", []);
        if(!is_array($blocks) || !array_key_exists($key, $blocks)){
            $player->sendMessage(TF::RED . "Ce bloc normal n'existe plus dans la configuration.");
            return;
        }
        $blocks[$key] = $updated;
        $config->set("blocks", $blocks);
        $config->set("deleted-blocks", $this->removeFromList($config->get("deleted-blocks", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Bloc normal modifié dans config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour appliquer les nouvelles valeurs.");
    }

    /** @param array<string, mixed> $updated */
    private function saveMineralEdit(Player $player, string $key, array $updated) : void{
        $config = $this->plugin->getConfig();
        $minerals = $config->get("minerals", []);
        if(!is_array($minerals) || !array_key_exists($key, $minerals)){
            $player->sendMessage(TF::RED . "Ce minerai n'existe plus dans la configuration.");
            return;
        }
        $minerals[$key] = $updated;
        $config->set("minerals", $minerals);
        $config->set("deleted-minerals", $this->removeFromList($config->get("deleted-minerals", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Minerai modifié dans config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour appliquer les nouvelles valeurs.");
    }

    private function deleteItem(Player $player, string $key) : void{
        $config = $this->plugin->getConfig();
        $items = $config->get("items", []);
        if(!is_array($items) || !array_key_exists($key, $items)){
            $player->sendMessage(TF::RED . "Cet item n'existe plus dans la configuration.");
            return;
        }
        unset($items[$key]);
        $config->set("items", $items);
        $config->set("deleted-items", $this->addToList($config->get("deleted-items", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Item supprimé de config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour terminer la suppression.");
    }


    private function deleteBlock(Player $player, string $key) : void{
        $config = $this->plugin->getConfig();
        $blocks = $config->get("blocks", []);
        if(!is_array($blocks) || !array_key_exists($key, $blocks)){
            $player->sendMessage(TF::RED . "Ce bloc normal n'existe plus dans la configuration.");
            return;
        }
        unset($blocks[$key]);
        $config->set("blocks", $blocks);
        $config->set("deleted-blocks", $this->addToList($config->get("deleted-blocks", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Bloc normal supprimé de config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour terminer la suppression.");
    }

    private function deleteMineral(Player $player, string $key) : void{
        $config = $this->plugin->getConfig();
        $minerals = $config->get("minerals", []);
        if(!is_array($minerals) || !array_key_exists($key, $minerals)){
            $player->sendMessage(TF::RED . "Ce minerai n'existe plus dans la configuration.");
            return;
        }
        unset($minerals[$key]);
        $config->set("minerals", $minerals);
        $config->set("deleted-minerals", $this->addToList($config->get("deleted-minerals", []), $key));
        $config->save();
        $player->sendMessage(TF::GREEN . "✓ Minerai supprimé de config.yml.");
        $player->sendMessage(TF::YELLOW . "Redémarre complètement le serveur pour terminer la suppression.");
    }

    /** @return array<string, mixed>|null */
    private function getItemDefinition(string $key) : ?array{
        $items = $this->plugin->getConfig()->get("items", []);
        if(!is_array($items) || !isset($items[$key]) || !is_array($items[$key])){
            return null;
        }
        return $items[$key];
    }


    /** @return array<string, mixed>|null */
    private function getBlockDefinition(string $key) : ?array{
        $blocks = $this->plugin->getConfig()->get("blocks", []);
        if(!is_array($blocks) || !isset($blocks[$key]) || !is_array($blocks[$key])){
            return null;
        }
        return $blocks[$key];
    }

    /** @return array<string, mixed>|null */
    private function getMineralDefinition(string $key) : ?array{
        $minerals = $this->plugin->getConfig()->get("minerals", []);
        if(!is_array($minerals) || !isset($minerals[$key]) || !is_array($minerals[$key])){
            return null;
        }
        return $minerals[$key];
    }

    /** @param array<string, mixed> $definition */
    private function friendlyItemType(array $definition) : string{
        $type = strtolower((string) ($definition["type"] ?? "item"));
        if($type === "armor" || $type === "armure"){
            return "Armure " . (string) ($definition["slot"] ?? "");
        }
        if($type === "tool" || $type === "outil" || $type === "weapon" || $type === "arme"){
            return "Outil " . (string) ($definition["tool_type"] ?? "");
        }
        return "Item basic";
    }

    /** @return list<string> */
    private function parseTags(string $value) : array{
        $parts = array_map(static fn(string $tag) : string => trim($tag), explode(",", $value));
        return array_values(array_filter(array_unique($parts), static fn(string $tag) : bool => $tag !== ""));
    }

    /** @return list<string> */
    private function addToList(mixed $value, string $key) : array{
        $list = is_array($value) ? array_map(static fn(mixed $entry) : string => (string) $entry, $value) : [];
        $list[] = $key;
        return array_values(array_unique($list));
    }

    /** @return list<string> */
    private function removeFromList(mixed $value, string $key) : array{
        $list = is_array($value) ? array_map(static fn(mixed $entry) : string => (string) $entry, $value) : [];
        return array_values(array_filter($list, static fn(string $entry) : bool => $entry !== $key));
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
}
