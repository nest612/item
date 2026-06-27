<?php

declare(strict_types=1);

namespace nestouille\itemsplus\item;

use customiesdevs\customies\item\component\WearableComponent;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Utils;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use function class_exists;
use function constant;
use function defined;
use function max;
use function method_exists;
use function str_contains;
use function strtolower;
use function trim;

final class CustomArmorItem extends Armor implements ItemComponents{
    use ItemComponentsTrait;

    private const DURABILITY_LORE_WORD = "Durabilité";

    /**
     * @var array<string, array{texture: string, defense: int, durability: int, armorSlot: int, toughness: int, fireProof: bool, creativeInfo: CreativeInventoryInfo|null}>
     */
    private static array $configs = [];

    public static function configure(
        string $displayName,
        string $textureName,
        int $defense,
        int $durability,
        int $armorSlot,
        int $toughness = 0,
        bool $fireProof = false,
        ?CreativeInventoryInfo $creativeInfo = null
    ) : void{
        $textureName = strtolower(trim($textureName));
        if($textureName === ""){
            $textureName = "unknown";
        }

        self::$configs[$displayName] = [
            "texture" => $textureName,
            "defense" => max(0, $defense),
            "durability" => max(1, $durability),
            "armorSlot" => $armorSlot,
            "toughness" => max(0, $toughness),
            "fireProof" => $fireProof,
            "creativeInfo" => $creativeInfo
        ];
    }

    public function __construct(ItemIdentifier $identifier, string $name = "Unknown"){
        $config = self::$configs[$name] ?? [];

        $textureName = (string) ($config["texture"] ?? strtolower(trim($name)));
        if($textureName === ""){
            $textureName = "unknown";
        }

        $defense = (int) ($config["defense"] ?? 1);
        $durability = (int) ($config["durability"] ?? 100);
        $armorSlot = (int) ($config["armorSlot"] ?? ArmorInventory::SLOT_CHEST);

        $armorInfo = new ArmorTypeInfo(
            $defense,
            $durability,
            $armorSlot,
            (int) ($config["toughness"] ?? 0),
            (bool) ($config["fireProof"] ?? false)
        );

        parent::__construct($identifier, $name, $armorInfo);

        /** @var CreativeInventoryInfo|null $creativeInfo */
        $creativeInfo = $config["creativeInfo"] ?? null;
        $this->initCustomiesComponent($textureName, $creativeInfo);
        $this->ensureWearableComponent($armorSlot, $defense);
        $this->refreshDurabilityLore();
    }


    /**
     * Applique Solidité exactement comme une armure vanilla de PocketMine.
     * Chaque point d'usure personnalisé est testé séparément : sur une armure,
     * Solidité ne peut protéger que 40 % des tentatives au maximum, puis le
     * niveau de l'enchantement détermine la chance d'annuler l'usure.
     */
    protected function getUnbreakingDamageReduction(int $amount) : int{
        $unbreakingLevel = $this->getEnchantmentLevel(VanillaEnchantments::UNBREAKING());
        if($unbreakingLevel <= 0 || $amount <= 0){
            return 0;
        }

        $negated = 0;
        $damageChance = 1 / ($unbreakingLevel + 1);

        for($i = 0; $i < $amount; ++$i){
            if(mt_rand(1, 100) > 60 && Utils::getRandomFloat() > $damageChance){
                ++$negated;
            }
        }

        return $negated;
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

    private function ensureWearableComponent(int $armorSlot, int $defense) : void{
        if(!class_exists(WearableComponent::class)){
            return;
        }
        if(method_exists($this, "hasComponent") && $this->hasComponent("minecraft:wearable")){
            return;
        }

        $slot = match($armorSlot){
            ArmorInventory::SLOT_HEAD => $this->wearableSlotConstant("SLOT_ARMOR_HEAD", "slot.armor.head"),
            ArmorInventory::SLOT_CHEST => $this->wearableSlotConstant("SLOT_ARMOR_CHEST", "slot.armor.chest"),
            ArmorInventory::SLOT_LEGS => $this->wearableSlotConstant("SLOT_ARMOR_LEGS", "slot.armor.legs"),
            ArmorInventory::SLOT_FEET => $this->wearableSlotConstant("SLOT_ARMOR_FEET", "slot.armor.feet"),
            default => $this->wearableSlotConstant("SLOT_ARMOR", "slot.armor")
        };

        try{
            $reflection = new ReflectionClass(WearableComponent::class);
            $constructor = $reflection->getConstructor();
            $parameterCount = $constructor !== null ? $constructor->getNumberOfParameters() : 1;

            if($parameterCount >= 2){
                $this->addComponent(new WearableComponent((string) $slot, $defense));
            }else{
                /** @phpstan-ignore-next-line - anciennes versions éventuelles */
                $this->addComponent(new WearableComponent((string) $slot));
            }
        }catch(Throwable){
            // Si une ancienne version de Customies ne supporte pas ce composant, on laisse initComponent gérer le reste.
        }
    }

    private function wearableSlotConstant(string $constantName, string $fallback) : string{
        $fullName = WearableComponent::class . "::" . $constantName;
        return defined($fullName) ? (string) constant($fullName) : $fallback;
    }
}
