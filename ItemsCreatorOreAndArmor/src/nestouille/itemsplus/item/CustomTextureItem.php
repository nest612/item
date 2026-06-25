<?php

declare(strict_types=1);

namespace nestouille\itemsplus\item;

use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use function strtolower;
use function trim;

final class CustomTextureItem extends Item implements ItemComponents{
    use ItemComponentsTrait;

    /** @var array<string, string> display name => texture key */
    private static array $textures = [];

    /** @var array<string, CreativeInventoryInfo|null> display name => creative info */
    private static array $creativeInfos = [];

    public static function configure(string $displayName, string $textureName, ?CreativeInventoryInfo $creativeInfo = null) : void{
        $textureName = strtolower(trim($textureName));
        if($textureName === ""){
            $textureName = "unknown";
        }

        self::$textures[$displayName] = $textureName;
        self::$creativeInfos[$displayName] = $creativeInfo;
    }

    public function __construct(ItemIdentifier $identifier, string $name = "Unknown"){
        parent::__construct($identifier, $name);

        $textureName = self::$textures[$name] ?? strtolower(trim($name));
        if($textureName === ""){
            $textureName = "unknown";
        }
        $creativeInfo = self::$creativeInfos[$name] ?? null;

        $this->initCustomiesComponent($textureName, $creativeInfo);
    }

    private function initCustomiesComponent(string $textureName, ?CreativeInventoryInfo $creativeInfo) : void{
        // Customies Poggit 1.4.0 utilise initComponent(string $texture, ?CreativeInventoryInfo $creativeInfo).
        // Certaines branches récentes n'acceptent que initComponent(string $texture). On détecte la signature.
        try{
            $method = new ReflectionMethod($this, "initComponent");
            $parameters = $method->getParameters();
            $secondType = isset($parameters[1]) ? $parameters[1]->getType() : null;

            if($secondType instanceof ReflectionNamedType && $secondType->getName() === CreativeInventoryInfo::class){
                $this->initComponent($textureName, $creativeInfo);
                return;
            }
        }catch(Throwable){
            // Fallback juste en dessous.
        }

        $this->initComponent($textureName);
    }
}
