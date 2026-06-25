<?php

declare(strict_types=1);

namespace nestouille\itemsplus\minerals;

use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;

/**
 * Bloc de minerai compatible avec les versions de Customies ne fournissant
 * pas BlockComponentsTrait. La texture est gérée par le modèle Customies ou
 * par blocks.json dans le resource pack.
 */
final class CustomMineralBlock extends Block{
    public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo, string $texture = ""){
        parent::__construct($idInfo, $name, $typeInfo);
    }
}
