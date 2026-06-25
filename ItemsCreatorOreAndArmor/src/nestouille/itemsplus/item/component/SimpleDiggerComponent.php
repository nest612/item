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

    /** @param string[] $tags */
    public function __construct(array $tags, int $speed, bool $useEfficiency = true){
        $this->tags = array_values($tags);
        $this->speed = $speed;
        $this->useEfficiency = $useEfficiency;
    }

    public function getName() : string{
        return "minecraft:digger";
    }

    /** @return array<string, mixed> */
    public function getValue() : array{
        if($this->tags === []){
            return [
                "use_efficiency" => $this->useEfficiency,
                "destroy_speeds" => []
            ];
        }

        $query = implode(",", array_map(static fn(string $tag) : string => "'" . $tag . "'", $this->tags));

        return [
            "use_efficiency" => $this->useEfficiency,
            "destroy_speeds" => [
                [
                    "block" => [
                        "tags" => "query.any_tag(" . $query . ")"
                    ],
                    "speed" => $this->speed
                ]
            ]
        ];
    }

    public function isProperty() : bool{
        return false;
    }
}
