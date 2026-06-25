<?php

declare(strict_types=1);

namespace nestouille\itemsplus\command;

use nestouille\itemsplus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use function implode;
use function max;
use function min;
use function strtolower;

final class ItemsPlusCommand implements CommandExecutor{

    public function __construct(private Main $plugin){
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!isset($args[0]) || strtolower((string) $args[0]) === "list"){
            $sender->sendMessage(TF::YELLOW . "ItemsPlus : " . implode(", ", $this->plugin->getItemKeys()));
            $sender->sendMessage(TF::GRAY . "Donner à soi-même : /itemsplus give pioche_obsidienne 1");
            $sender->sendMessage(TF::GRAY . "Donner à un joueur : /itemsplus give <joueur> epee_dragon 1");
            $sender->sendMessage(TF::GRAY . "Équiper armure : /itemsplus equip tank|nexium ou /itemsplus equip <joueur> tank|nexium");
            return true;
        }

        $subCommand = strtolower((string) $args[0]);

        if($subCommand === "equip"){
            return $this->handleEquip($sender, $args);
        }

        if($subCommand === "id" || $subCommand === "identifier"){
            if(!isset($args[1])){
                $sender->sendMessage(TF::RED . "Utilisation : /itemsplus id <item>");
                return true;
            }
            $key = strtolower((string) $args[1]);
            $identifier = $this->plugin->getIdentifierForKey($key);
            if($identifier === null){
                $sender->sendMessage(TF::RED . "Item inconnu : " . $key);
                $sender->sendMessage(TF::GRAY . "Items : " . implode(", ", $this->plugin->getItemKeys()));
                return true;
            }
            $sender->sendMessage(TF::GREEN . $key . " = " . $identifier);
            return true;
        }

        if($subCommand !== "give"){
            $sender->sendMessage(TF::RED . "Utilisation : /itemsplus list | /itemsplus give [joueur] <item> [quantite] | /itemsplus equip [joueur] tank|nexium");
            return true;
        }

        return $this->handleGive($sender, $args);
    }

    /** @param string[] $args */
    private function handleGive(CommandSender $sender, array $args) : bool{
        if(!isset($args[1])){
            $sender->sendMessage(TF::RED . "Utilisation : /itemsplus give [joueur] <item> [quantite]");
            return true;
        }

        $target = null;
        $keyArgIndex = 1;

        if(isset($args[2])){
            $possiblePlayer = $this->plugin->getServer()->getPlayerExact((string) $args[1]) ?? $this->plugin->getServer()->getPlayerByPrefix((string) $args[1]);
            if($possiblePlayer instanceof Player){
                $target = $possiblePlayer;
                $keyArgIndex = 2;
            }
        }

        if($target === null){
            if($sender instanceof Player){
                $target = $sender;
                $keyArgIndex = 1;
            }else{
                $sender->sendMessage(TF::RED . "Depuis la console : /itemsplus give <joueur> <item> [quantite]");
                return true;
            }
        }

        $key = strtolower((string) ($args[$keyArgIndex] ?? ""));
        if($key === ""){
            $sender->sendMessage(TF::RED . "Item manquant.");
            return true;
        }

        $amountIndex = $keyArgIndex + 1;
        $amount = isset($args[$amountIndex]) ? max(1, min(64, (int) $args[$amountIndex])) : 1;
        $item = $this->plugin->getCustomItem($key, $amount);
        if($item === null){
            $sender->sendMessage(TF::RED . "Item inconnu ou pas enregistré : " . $key);
            $sender->sendMessage(TF::GRAY . "Items : " . implode(", ", $this->plugin->getItemKeys()));
            return true;
        }

        $target->getInventory()->addItem($item);
        $sender->sendMessage(TF::GREEN . "✓ " . $amount . "x " . $key . " donné à " . $target->getName() . ".");
        return true;
    }

    /** @param string[] $args */
    private function handleEquip(CommandSender $sender, array $args) : bool{
        $target = null;
        $set = "tank";

        if(isset($args[1])){
            $possiblePlayer = $this->plugin->getServer()->getPlayerExact((string) $args[1]) ?? $this->plugin->getServer()->getPlayerByPrefix((string) $args[1]);
            if($possiblePlayer instanceof Player){
                $target = $possiblePlayer;
                $set = strtolower((string) ($args[2] ?? "tank"));
            }else{
                if($sender instanceof Player){
                    $target = $sender;
                    $set = strtolower((string) $args[1]);
                }else{
                    $sender->sendMessage(TF::RED . "Depuis la console : /itemsplus equip <joueur> tank|nexium");
                    return true;
                }
            }
        }else{
            if($sender instanceof Player){
                $target = $sender;
            }else{
                $sender->sendMessage(TF::RED . "Depuis la console : /itemsplus equip <joueur> tank|nexium");
                return true;
            }
        }

        if($set === "tank"){
            return $this->equipTankSet($sender, $target);
        }

        if($set === "nexium"){
            return $this->equipNexiumSet($sender, $target);
        }

        $sender->sendMessage(TF::RED . "Set inconnu : " . $set);
        $sender->sendMessage(TF::GRAY . "Sets disponibles : tank, nexium");
        return true;
    }

    private function equipTankSet(CommandSender $sender, Player $player) : bool{
        $helmet = $this->plugin->getCustomItem("casque_tank", 1);
        $chestplate = $this->plugin->getCustomItem("plastron_tank", 1);
        $leggings = $this->plugin->getCustomItem("jambieres_tank", 1);
        $boots = $this->plugin->getCustomItem("bottes_tank", 1);

        if($helmet === null || $chestplate === null || $leggings === null || $boots === null){
            $sender->sendMessage(TF::RED . "Impossible d'équiper le set Tank : un item d'armure n'est pas enregistré.");
            $sender->sendMessage(TF::GRAY . "Fais /itemsplus list pour vérifier que casque_tank, plastron_tank, jambieres_tank et bottes_tank existent.");
            return true;
        }

        $armorInventory = $player->getArmorInventory();
        if(!$armorInventory->getHelmet()->isNull() || !$armorInventory->getChestplate()->isNull() || !$armorInventory->getLeggings()->isNull() || !$armorInventory->getBoots()->isNull()){
            $sender->sendMessage(TF::RED . "Retire d'abord l'armure actuelle de " . $player->getName() . " pour éviter d'écraser un item.");
            return true;
        }

        $armorInventory->setHelmet($helmet);
        $armorInventory->setChestplate($chestplate);
        $armorInventory->setLeggings($leggings);
        $armorInventory->setBoots($boots);

        $sender->sendMessage(TF::GREEN . "✓ Set Tank équipé sur " . $player->getName() . ".");
        return true;
    }

    private function equipNexiumSet(CommandSender $sender, Player $player) : bool{
        $helmet = $this->plugin->getCustomItem("nexium_helmet", 1);
        $chestplate = $this->plugin->getCustomItem("nexium_chestplate", 1);
        $leggings = $this->plugin->getCustomItem("nexium_leggings", 1);
        $boots = $this->plugin->getCustomItem("nexium_boots", 1);

        if($helmet === null || $chestplate === null || $leggings === null || $boots === null){
            $sender->sendMessage(TF::RED . "Impossible d'équiper le set Nexium : un item d'armure n'est pas enregistré.");
            $sender->sendMessage(TF::GRAY . "Fais /itemsplus list pour vérifier que nexium_helmet, nexium_chestplate, nexium_leggings et nexium_boots existent.");
            return true;
        }

        $armorInventory = $player->getArmorInventory();
        if(!$armorInventory->getHelmet()->isNull() || !$armorInventory->getChestplate()->isNull() || !$armorInventory->getLeggings()->isNull() || !$armorInventory->getBoots()->isNull()){
            $sender->sendMessage(TF::RED . "Retire d'abord l'armure actuelle de " . $player->getName() . " pour éviter d'écraser un item.");
            return true;
        }

        $armorInventory->setHelmet($helmet);
        $armorInventory->setChestplate($chestplate);
        $armorInventory->setLeggings($leggings);
        $armorInventory->setBoots($boots);

        $sender->sendMessage(TF::GREEN . "✓ Set Nexium équipé sur " . $player->getName() . ".");
        return true;
    }

}
