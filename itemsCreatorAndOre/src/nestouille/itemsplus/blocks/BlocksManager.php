<?php

declare(strict_types=1);

namespace nestouille\itemsplus\blocks;

use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\item\CreativeInventoryInfo;
use nestouille\itemsplus\Main;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use function array_keys;
use function class_exists;
use function constant;
use function defined;
use function is_array;
use function ltrim;
use function strtolower;
use function trim;

final class BlocksManager implements Listener{

    /** @var array<string, Block> */
    private array $blocks = [];

    /** @var array<int, string> block type ID => configuration key */
    private array $keysByTypeId = [];

    /** @var array<string, string> configuration key => Customies identifier */
    private array $identifiers = [];

    public function __construct(private Main $plugin){
    }

    public function enable() : void{
        $this->registerBlocks();
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        $this->plugin->getLogger()->info(TF::GREEN . "Module blocs normaux actif : " . \count($this->blocks) . " blocs enregistrés.");
    }

    private function registerBlocks() : void{
        $definitions = $this->plugin->getConfig()->get("blocks", []);
        if(!is_array($definitions)){
            $this->plugin->getLogger()->warning("La section 'blocks' du config.yml est invalide.");
            return;
        }

        foreach($definitions as $key => $data){
            if(!is_array($data)){
                continue;
            }

            $key = strtolower((string) $key);
            $namespace = strtolower((string) ($data["namespace"] ?? "itemsplus"));
            $id = strtolower((string) ($data["id"] ?? $key));
            $identifier = $namespace . ":" . $id;
            $name = (string) ($data["name"] ?? $key);
            $texture = $this->resolveTextureKey((string) ($data["texture"] ?? $id), $id);
            $hardness = max(0.0, (float) ($data["hardness"] ?? 1.5));
            $creative = (bool) ($data["creative"] ?? true);

            try{
                $creativeInfo = $creative ? $this->makeCreativeInfo($data) : $this->makeHiddenCreativeInfo();
                $this->registerBlock(
                    static fn() : Block => new CustomNormalBlock(
                        new BlockIdentifier(BlockTypeIds::newId()),
                        $name,
                        new BlockTypeInfo(new BlockBreakInfo($hardness)),
                        $texture
                    ),
                    $identifier,
                    $creativeInfo
                );

                $block = CustomiesBlockFactory::getInstance()->get($identifier);
                if($block instanceof Block){
                    $this->blocks[$key] = $block;
                    $this->keysByTypeId[$block->getTypeId()] = $key;
                    $this->identifiers[$key] = $identifier;
                    if($creative){
                        $this->plugin->forceCreativePlacement($block->asItem(), $creativeInfo);
                    }
                }
            }catch(Throwable $e){
                $this->plugin->getLogger()->warning("Impossible d'enregistrer le bloc normal " . $identifier . " : " . $e->getMessage());
            }
        }
    }

    /** @param \Closure(): Block $blockFactory */
    private function registerBlock(\Closure $blockFactory, string $identifier, ?CreativeInventoryInfo $creativeInfo) : void{
        $factory = CustomiesBlockFactory::getInstance();
        $method = new ReflectionMethod($factory, "registerBlock");
        $parameters = $method->getParameters();
        $thirdParameter = $parameters[2] ?? null;
        $thirdType = $thirdParameter?->getType();
        $thirdTypeName = $thirdType instanceof ReflectionNamedType ? ltrim($thirdType->getName(), "\\") : "";
        $usesModelArgument = $thirdParameter !== null && (
            strtolower($thirdParameter->getName()) === "model" ||
            $thirdTypeName === "customiesdevs\\customies\\block\\Model"
        );

        if($usesModelArgument){
            $method->invokeArgs($factory, [$blockFactory, $identifier, null, $creativeInfo]);
            return;
        }

        $method->invokeArgs($factory, [$blockFactory, $identifier, $creativeInfo]);
    }

    private function resolveTextureKey(string $texture, string $defaultTexture) : string{
        $texture = strtolower(trim($texture));
        if($texture === ""){
            return strtolower($defaultTexture);
        }
        if(str_contains($texture, ":")){
            $parts = explode(":", $texture, 2);
            return strtolower((string) ($parts[1] ?? $defaultTexture));
        }
        return $texture;
    }

    /** @param array<string, mixed> $data */
    private function makeCreativeInfo(array $data) : ?CreativeInventoryInfo{
        if(!class_exists(CreativeInventoryInfo::class)){
            return null;
        }

        $category = trim((string) ($data["creative_category"] ?? ""));
        if($category === ""){
            $category = defined(CreativeInventoryInfo::class . "::CATEGORY_CONSTRUCTION")
                ? (string) constant(CreativeInventoryInfo::class . "::CATEGORY_CONSTRUCTION")
                : "construction";
        }

        $group = trim((string) ($data["creative_group"] ?? ""));
        if($group === ""){
            $group = defined(CreativeInventoryInfo::class . "::GROUP_STONE")
                ? (string) constant(CreativeInventoryInfo::class . "::GROUP_STONE")
                : "itemGroup.name.stone";
        }

        return new CreativeInventoryInfo($category, $group);
    }

    private function makeHiddenCreativeInfo() : ?CreativeInventoryInfo{
        if(!class_exists(CreativeInventoryInfo::class)){
            return null;
        }

        $category = defined(CreativeInventoryInfo::class . "::CATEGORY_COMMANDS") ? constant(CreativeInventoryInfo::class . "::CATEGORY_COMMANDS") : "commands";
        $group = defined(CreativeInventoryInfo::class . "::NONE") ? constant(CreativeInventoryInfo::class . "::NONE") : "none";
        return new CreativeInventoryInfo((string) $category, (string) $group);
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        if($event->isCancelled() || $event->getPlayer()->isCreative()){
            return;
        }

        $block = $event->getBlock();
        if(!isset($this->keysByTypeId[$block->getTypeId()])){
            return;
        }

        // En survie, un bloc normal redonne un exemplaire de lui-même.
        $event->setDrops([$block->asItem()]);
    }

    /** @return string[] */
    public function getBlockKeys() : array{
        return array_keys($this->blocks);
    }

    public function getIdentifierForKey(string $key) : ?string{
        return $this->identifiers[strtolower($key)] ?? null;
    }

    public function getBlockItem(string $key, int $amount = 1) : ?Item{
        $block = $this->blocks[strtolower($key)] ?? null;
        if(!$block instanceof Block){
            return null;
        }
        $item = $block->asItem();
        $item->setCount($amount);
        return $item;
    }
}
