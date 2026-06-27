<?php

declare(strict_types=1);

namespace nestouille\itemsplus\listener;

use nestouille\itemsplus\item\CustomToolItem;
use nestouille\itemsplus\Main;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use function array_sum;
use function random_int;

/**
 * Permet à une arme personnalisée de choisir l'usure infligée à chaque pièce
 * d'armure, sans modifier les dégâts de vie réellement reçus par la victime.
 */
final class ArmorDurabilityListener implements Listener{

    private const DURABILITY_CONTROL_MODIFIER = 10000;

    public function __construct(private Main $plugin){
    }

    /**
     * @priority HIGHEST
     * @handleCancelled false
     */
    public function onEntityDamage(EntityDamageByEntityEvent $event) : void{
        if($event->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK || !$event->canBeReducedByArmor()){
            return;
        }

        $attacker = $event->getDamager();
        if(!$attacker instanceof Player){
            return;
        }

        $weapon = $attacker->getInventory()->getItemInHand();
        if(!$weapon instanceof CustomToolItem){
            return;
        }

        $maximumArmorDurabilityDamage = $weapon->getArmorDurabilityDamage();
        if($maximumArmorDurabilityDamage === null){
            return;
        }

        // La valeur configurée représente désormais l'usure maximale aléatoire.
        // Exemple : 2 donne 1 ou 2 points d'usure par pièce à chaque coup.
        // Solidité est ensuite appliquée séparément par chaque armure, comme en vanilla.
        $armorDurabilityDamage = $maximumArmorDurabilityDamage <= 1
            ? 1
            : random_int(1, $maximumArmorDurabilityDamage);

        // PocketMine retire floor(dégâts de base / 4), avec un minimum de 1.
        // On adapte uniquement la valeur de base utilisée par l'usure d'armure,
        // puis on compense avec un modificateur afin de conserver exactement les
        // mêmes dégâts de vie qu'avant cette correction.
        $originalBaseDamage = $event->getBaseDamage();
        $originalFinalDamage = $event->getFinalDamage();
        $originalControlModifier = $event->getModifier(self::DURABILITY_CONTROL_MODIFIER);
        $existingModifiersTotal = array_sum($event->getModifiers()) - $originalControlModifier;
        $controlledBaseDamage = (float) ($armorDurabilityDamage * 4);

        $event->setBaseDamage($controlledBaseDamage);
        $event->setModifier(
            $originalFinalDamage - $controlledBaseDamage - $existingModifiersTotal,
            self::DURABILITY_CONTROL_MODIFIER
        );

        // Le dernier évènement de dégâts est conservé par PocketMine pendant le
        // délai anti-spam. On restaure ensuite ses valeurs d'origine pour ne pas
        // influencer le calcul d'un coup suivant.
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
            static function() use ($event, $originalBaseDamage, $originalControlModifier) : void{
                $event->setBaseDamage($originalBaseDamage);
                $event->setModifier($originalControlModifier, self::DURABILITY_CONTROL_MODIFIER);
            }
        ), 1);
    }
}
