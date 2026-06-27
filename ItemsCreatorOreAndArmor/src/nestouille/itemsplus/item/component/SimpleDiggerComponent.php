<?php

declare(strict_types=1);

namespace nestouille\itemsplus\item\component;

use customiesdevs\customies\item\component\ItemComponent;
use function array_map;
use function array_values;
use function implode;

/**
 * Petit composant compatible Customies pour envoyer minecraft:digger au client Bedrock.
 * Il évite de dépendre d'une version précise de Customies qui aurait ou non DiggerComponent.
 */
final class SimpleDiggerComponent implements ItemComponent{

    /** @var string[] */
    private array $tags;
    private int $speed;
    private bool $useEfficiency;

    /** @var string[] */
    private array $blocks;

    /**
     * @param string[] $tags
     * @param string[] $blocks Identifiants complets de blocs, par exemple itemsplus:ruby_ore
     */
    public function __construct(array $tags, int $speed, bool $useEfficiency = true, array $blocks = []){
        $this->tags = array_values($tags);
        $this->speed = $speed;
        $this->useEfficiency = $useEfficiency;
        $this->blocks = array_values($blocks);
    }

    public function getName() : string{
        return "minecraft:digger";
    }

    /** @return array<string, mixed> */
    public function getValue() : array{
        $destroySpeeds = [];

        if($this->tags !== []){
            $query = implode(",", array_map(static fn(string $tag) : string => "'" . $tag . "'", $this->tags));
            $destroySpeeds[] = [
                "block" => [
                    "tags" => "query.any_tag(" . $query . ")"
                ],
                "speed" => $this->speed
            ];
        }

        foreach($this->blocks as $identifier){
            $destroySpeeds[] = [
                "block" => $identifier,
                "speed" => $this->speed
            ];
        }

        return [
            "use_efficiency" => $this->useEfficiency,
            "destroy_speeds" => $destroySpeeds
        ];
    }

    public function isProperty() : bool{
        return false;
    }
}
