<?php

declare(strict_types=1);

namespace nestouille\itemsplus\listener;

use nestouille\itemsplus\item\CustomToolItem;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

/**
 * Gère l'animation vanilla des outils personnalisés.
 *
 * La vitesse de casse reste calculée par PocketMine à partir de la dureté du
 * bloc et de la compatibilité de l'outil : le bon outil utilise sa vitesse
 * personnalisée, tandis qu'un mauvais outil casse à la vitesse de base.
 */
final class ToolBehaviorListener implements Listener{

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::LEFT_CLICK_BLOCK || $event->isCancelled()){
            return;
        }

        $player = $event->getPlayer();
        $item = $event->getItem();
        if(!$item instanceof CustomToolItem){
            return;
        }

        // Ne jamais annuler le minage avec un mauvais outil : PocketMine
        // applique automatiquement une casse lente basée sur la dureté du bloc.
        // Le composant hand_equipped conserve la position vanilla de l'outil,
        // et cette animation rend le mouvement de frappe visible immédiatement.
        $player->broadcastAnimation(new ArmSwingAnimation($player), [$player]);
    }
}
