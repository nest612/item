<?php

declare(strict_types=1);

namespace nestouille\itemsplus\blocks;

use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\item\Item;

/**
 * Bloc solide simple.
 *
 * Le nom personnalisé est aussi appliqué directement à l'ItemBlock. Cela évite
 * l'affichage brut "tile.namespace:id.name" lorsque le resource pack ne
 * contient pas encore de traduction pour un bloc créé en jeu.
 */
final class CustomNormalBlock extends Block{
    public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo, string $texture = ""){
        parent::__construct($idInfo, $name, $typeInfo);
    }

    public function asItem() : Item{
        $item = parent::asItem();
        $item->setCustomName($this->getName());
        return $item;
    }
}
