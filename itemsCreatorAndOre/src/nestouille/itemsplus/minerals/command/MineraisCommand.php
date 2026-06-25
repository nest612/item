<?php

declare(strict_types=1);

namespace nestouille\itemsplus\minerals\command;

use nestouille\itemsplus\minerals\MineralsManager;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use function implode;
use function max;
use function min;
use function strtolower;

final class MineraisCommand implements CommandExecutor{

    public function __construct(private MineralsManager $plugin){
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!isset($args[0])){
            $sender->sendMessage(TF::YELLOW . "Utilisation : /minerais c [rayon] ou /minerais <monde> <minerais>");
            $sender->sendMessage(TF::GRAY . "Minerais : " . implode(", ", $this->plugin->getMineralNames()));
            return true;
        }

        if(strtolower((string) $args[0]) === "c"){
            if(!$sender instanceof Player){
                $sender->sendMessage(TF::RED . "La commande /minerais c doit être utilisée en jeu.");
                return true;
            }

            $radius = isset($args[1]) ? (int) $args[1] : $this->plugin->getDefaultRadius();
            $radius = max(8, min(256, $radius));
            $count = $this->plugin->generateOresAround($sender, $radius);

            $sender->sendMessage(TF::GREEN . "✓ " . $count . " blocs de minerais custom générés dans un rayon de " . $radius . " blocs.");
            return true;
        }

        if(!isset($args[1])){
            $sender->sendMessage(TF::YELLOW . "Utilisation : /minerais <monde> <minerais>");
            $sender->sendMessage(TF::GRAY . "Exemple : /minerais world azurite");
            $sender->sendMessage(TF::GRAY . "Minerais : " . implode(", ", $this->plugin->getMineralNames()));
            return true;
        }

        $worldName = (string) $args[0];
        $mineralName = strtolower((string) $args[1]);
        $this->plugin->startFullMapGeneration($sender, $worldName, $mineralName);
        return true;
    }
}
