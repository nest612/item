<?php

declare(strict_types=1);

namespace nestouille\itemsplus\item;

use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use customiesdevs\customies\item\component\HandEquippedComponent;
use nestouille\itemsplus\item\component\SimpleDiggerComponent;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Dirt;
use pocketmine\block\Grass;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DirtType;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\ItemEnchantmentTags;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemUseResult;
use pocketmine\item\Tool;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ItemUseOnBlockSound;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use pocketmine\utils\TextFormat as TF;
use function array_values;
use function class_exists;
use function in_array;
use function max;
use function min;
use function str_contains;
use function strtolower;
use function trim;

final class CustomToolItem extends Tool implements ItemComponents{
    use ItemComponentsTrait;

    private const DURABILITY_LORE_WORD = "Durabilité";

    /**
     * @var array<string, array{texture: string, toolType: string, blockToolType: int, harvestLevel: int, attackDamage: int, durability: int, miningEfficiency: float, enchantability: int, fireProof: bool, blockDurabilityDamage: int, entityDurabilityDamage: int, diggerTags: string[], creativeInfo: CreativeInventoryInfo|null}>
     */
    private static array $configs = [];

    public static function configure(
        string $displayName,
        string $textureName,
        string $toolType,
        int $blockToolType,
        int $harvestLevel,
        int $attackDamage,
        int $durability,
        float $miningEfficiency,
        int $enchantability = 10,
        bool $fireProof = false,
        int $blockDurabilityDamage = 1,
        int $entityDurabilityDamage = 2,
        array $diggerTags = [],
        ?CreativeInventoryInfo $creativeInfo = null
    ) : void{
        $textureName = strtolower(trim($textureName));
        if($textureName === ""){
            $textureName = "unknown";
        }

        $toolType = strtolower(trim($toolType));
        if($toolType === ""){
            $toolType = "tool";
        }

        self::$configs[$displayName] = [
            "texture" => $textureName,
            "toolType" => $toolType,
            "blockToolType" => $blockToolType,
            "harvestLevel" => max(1, $harvestLevel),
            "attackDamage" => max(0, $attackDamage),
            "durability" => max(1, $durability),
            "miningEfficiency" => max(0.1, $miningEfficiency),
            "enchantability" => max(0, $enchantability),
            "fireProof" => $fireProof,
            "blockDurabilityDamage" => max(0, $blockDurabilityDamage),
            "entityDurabilityDamage" => max(0, $entityDurabilityDamage),
            "diggerTags" => array_values($diggerTags),
            "creativeInfo" => $creativeInfo
        ];
    }

    public function __construct(ItemIdentifier $identifier, string $name = "Unknown"){
        $config = self::$configs[$name] ?? [];
        $toolType = (string) ($config["toolType"] ?? "tool");

        parent::__construct($identifier, $name, self::getEnchantmentTagsForType($toolType));

        /** @var CreativeInventoryInfo|null $creativeInfo */
        $creativeInfo = $config["creativeInfo"] ?? null;
        $textureName = (string) ($config["texture"] ?? strtolower(trim($name)));
        if($textureName === ""){
            $textureName = "unknown";
        }

        $this->initCustomiesComponent($textureName, $creativeInfo);
        $this->addVanillaToolAnimation($toolType);
        $this->addDiggerComponent($toolType);
        $this->refreshDurabilityLore();
    }

    public function getMaxDurability() : int{
        return (int) ((self::$configs[$this->name]["durability"] ?? 100));
    }

    public function getAttackPoints() : int{
        return (int) ((self::$configs[$this->name]["attackDamage"] ?? 1));
    }

    public function getBlockToolType() : int{
        return (int) ((self::$configs[$this->name]["blockToolType"] ?? 0));
    }

    public function getBlockToolHarvestLevel() : int{
        return (int) ((self::$configs[$this->name]["harvestLevel"] ?? 1));
    }

    public function getEnchantability() : int{
        return (int) ((self::$configs[$this->name]["enchantability"] ?? 10));
    }

    public function isFireProof() : bool{
        return (bool) ((self::$configs[$this->name]["fireProof"] ?? false));
    }

    protected function getBaseMiningEfficiency() : float{
        return (float) ((self::$configs[$this->name]["miningEfficiency"] ?? 1.0));
    }

    public function getMiningEfficiency(bool $isCorrectTool) : float{
        // Mode simple : la valeur breaking_speed/mining_efficiency s'applique à tous les blocs côté serveur.
        // Le boost Haste ajouté par Main synchronise aussi la sensation côté client Bedrock.
        return $this->getBaseMiningEfficiency();
    }

    /**
     * Reproduit le labourage vanilla pour les houes Customies.
     * Les blocs Dirt/Grass de PocketMine vérifient instanceof Hoe, ce qui exclut
     * les outils personnalisés héritant de Tool. Cette méthode est appelée après
     * l'évènement d'interaction, donc les protections de claims restent respectées.
     */
    public function onInteractBlock(
        Player $player,
        Block $blockReplace,
        Block $blockClicked,
        int $face,
        Vector3 $clickVector,
        array &$returnedItems
    ) : ItemUseResult{
        $toolType = strtolower((string) (self::$configs[$this->name]["toolType"] ?? ""));
        if(!in_array($toolType, ["hoe", "houe"], true) || $face === Facing::DOWN){
            return ItemUseResult::NONE;
        }

        // Comme la houe vanilla, aucun labourage n'est possible si un bloc
        // occupe l'espace directement au-dessus du bloc ciblé.
        if($blockClicked->getSide(Facing::UP)->getTypeId() !== BlockTypeIds::AIR){
            return ItemUseResult::NONE;
        }

        $newBlock = null;
        $dropHangingRoots = false;

        if($blockClicked instanceof Grass){
            $newBlock = VanillaBlocks::FARMLAND();
        }elseif($blockClicked instanceof Dirt){
            $dirtType = $blockClicked->getDirtType();
            $newBlock = $dirtType === DirtType::NORMAL
                ? VanillaBlocks::FARMLAND()
                : VanillaBlocks::DIRT();
            $dropHangingRoots = $dirtType === DirtType::ROOTED;
        }

        if($newBlock === null){
            return ItemUseResult::NONE;
        }

        $position = $blockClicked->getPosition();
        $world = $position->getWorld();
        $center = $position->add(0.5, 0.5, 0.5);

        $this->applyDamage(1);
        $this->refreshDurabilityLore();
        $world->addSound($center, new ItemUseOnBlockSound($newBlock));
        $world->setBlock($position, $newBlock);

        if($dropHangingRoots){
            $world->dropItem($center, VanillaBlocks::HANGING_ROOTS()->asItem());
        }

        return ItemUseResult::SUCCESS;
    }

    public function onDestroyBlock(Block $block, array &$returnedItems) : bool{
        if($block->getBreakInfo()->breaksInstantly()){
            return false;
        }

        $damage = (int) ((self::$configs[$this->name]["blockDurabilityDamage"] ?? 1));
        $changed = $damage > 0 ? $this->applyDamage($damage) : false;
        $this->refreshDurabilityLore();
        return $changed;
    }

    public function onAttackEntity(Entity $victim, array &$returnedItems) : bool{
        $damage = (int) ((self::$configs[$this->name]["entityDurabilityDamage"] ?? 2));
        $changed = $damage > 0 ? $this->applyDamage($damage) : false;
        $this->refreshDurabilityLore();
        return $changed;
    }

    public function refreshDurabilityLore() : bool{
        $maxDurability = max(1, $this->getMaxDurability());
        $remaining = max(0, $maxDurability - $this->getDamage());
        $line = TF::RESET . TF::GRAY . "Durabilité : " . TF::GREEN . $remaining . TF::GRAY . " / " . TF::GREEN . $maxDurability;

        $oldLore = $this->getLore();
        $newLore = [$line];

        if($newLore === $oldLore){
            return false;
        }

        // L'infobulle reste volontairement propre : nom de l'item + durabilité uniquement.
        $this->setLore($newLore);
        return true;
    }

    /**
     * Force le rendu tenu en main des outils vanilla.
     * Le client Bedrock utilise alors la position et le mouvement de frappe
     * classiques des pioches, haches et pelles pendant le minage.
     */
    private function addVanillaToolAnimation(string $toolType) : void{
        $toolType = strtolower(trim($toolType));
        if(!in_array($toolType, ["pickaxe", "pioche", "axe", "hache", "shovel", "pelle"], true)){
            return;
        }

        // Customies 1.4.0 fournit ce composant. Le garde-fou évite un crash
        // avec une éventuelle ancienne build personnalisée de Customies.
        if(class_exists(HandEquippedComponent::class)){
            // addComponent remplace proprement la propriété si initComponent
            // l'avait déjà ajoutée automatiquement.
            $this->addComponent(new HandEquippedComponent(true));
        }
    }

    private function addDiggerComponent(string $toolType) : void{
        $config = self::$configs[$this->name] ?? [];
        $tags = $config["diggerTags"] ?? [];
        if($tags === []){
            $tags = self::getDefaultDiggerTagsForType($toolType);
        }

        if($tags === [] || $this->hasComponent("minecraft:digger")){
            return;
        }

        $speed = (int) max(1, min(100, (int) ($config["miningEfficiency"] ?? 1)));
        $this->addComponent(new SimpleDiggerComponent($tags, $speed, true));
    }

    private function initCustomiesComponent(string $textureName, ?CreativeInventoryInfo $creativeInfo) : void{
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

    /** @return string[] */
    private static function getDefaultDiggerTagsForType(string $toolType) : array{
        return match($toolType){
            "pickaxe", "pioche" => [
                "stone",
                "metal",
                "rail",
                "mob_spawner",
                "wood_pick_diggable",
                "stone_pick_diggable",
                "iron_pick_diggable",
                "gold_pick_diggable",
                "diamond_pick_diggable",
                "minecraft:is_pickaxe_item_destructible",
                "minecraft:stone_tier_destructible",
                "minecraft:iron_tier_destructible",
                "minecraft:diamond_tier_destructible",
                "minecraft:netherite_tier_destructible"
            ],
            "axe", "hache" => [
                "wood",
                "log",
                "oak",
                "spruce",
                "birch",
                "jungle",
                "acacia",
                "dark_oak",
                "pumpkin",
                "text_sign",
                "trapdoors",
                "minecraft:is_axe_item_destructible",
                "minecraft:is_hatchet_item_destructible"
            ],
            "shovel", "pelle" => [
                "dirt",
                "grass",
                "sand",
                "gravel",
                "snow",
                "minecraft:is_shovel_item_destructible"
            ],
            "hoe", "houe" => [
                "plant",
                "minecraft:crop",
                "minecraft:is_hoe_item_destructible"
            ],
            "sword", "epee", "épée" => [
                "plant",
                "minecraft:is_sword_item_destructible"
            ],
            default => []
        };
    }

    /** @return string[] */
    private static function getEnchantmentTagsForType(string $toolType) : array{
        return match($toolType){
            "sword", "epee", "épée" => [ItemEnchantmentTags::SWORD],
            "pickaxe", "pioche" => [ItemEnchantmentTags::PICKAXE, ItemEnchantmentTags::BLOCK_TOOLS],
            "axe", "hache" => [ItemEnchantmentTags::AXE, ItemEnchantmentTags::BLOCK_TOOLS],
            "shovel", "pelle" => [ItemEnchantmentTags::SHOVEL, ItemEnchantmentTags::BLOCK_TOOLS],
            "hoe", "houe" => [ItemEnchantmentTags::HOE, ItemEnchantmentTags::BLOCK_TOOLS],
            default => [ItemEnchantmentTags::BLOCK_TOOLS]
        };
    }
}
