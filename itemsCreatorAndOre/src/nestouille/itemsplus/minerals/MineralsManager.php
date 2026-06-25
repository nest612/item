<?php

declare(strict_types=1);

namespace nestouille\itemsplus\minerals;

use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\CustomiesItemFactory;
use nestouille\itemsplus\minerals\task\FullMapGenerationTask;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\LoadedChunkData;
use pocketmine\world\World;
use Throwable;
use function array_keys;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function method_exists;
use function min;
use function mt_rand;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function trim;

final class MineralsManager implements Listener{

    /** @var array<string, Block> */
    private array $oreBlocks = [];

    /** @var array<int, string> block type ID => mineral key */
    private array $oreDrops = [];

    /** @var array<string, array<string, mixed>> */
    private array $mineralConfigs = [];

    /** @var string[] */
    private array $replaceBlocks = [];

    /** @var array<string, bool> */
    private array $warnedInvalidDrops = [];

    public function __construct(private \nestouille\itemsplus\Main $plugin){
    }

    public function enable() : void{
        $this->loadReplaceBlocks();
        $this->registerMinerals();
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        $this->plugin->getLogger()->info(TF::GREEN . "Module minerais actif : " . \count($this->oreBlocks) . " minerais enregistrés.");
    }

    private function loadReplaceBlocks() : void{
        $replace = $this->plugin->getConfig()->getNested("generation.replace-blocks", ["Stone", "Deepslate", "Tuff"]);
        if(!is_array($replace)){
            $replace = ["Stone", "Deepslate", "Tuff"];
        }
        $this->replaceBlocks = array_map(static fn(mixed $name) : string => strtolower((string) $name), $replace);
    }

    private function registerMinerals() : void{
        $minerals = $this->plugin->getConfig()->get("minerals", []);
        if(!is_array($minerals)){
            $this->plugin->getLogger()->warning("La section 'minerals' du config.yml est invalide.");
            return;
        }

        foreach($minerals as $key => $data){
            if(!is_array($data)){
                continue;
            }

            $key = strtolower((string) $key);
            $namespace = strtolower((string) ($data["namespace"] ?? "mineraisplus"));
            $oreId = strtolower((string) ($data["ore_id"] ?? ($key . "_ore")));
            $ingotId = strtolower((string) ($data["ingot_id"] ?? ($key . "_ingot")));
            $oreIdentifier = $namespace . ":" . $oreId;
            $ingotIdentifier = strtolower((string) ($data["ingot_identifier"] ?? ($namespace . ":" . $ingotId)));
            $oreName = (string) ($data["ore_name"] ?? ("Minerai " . $key));
            $ingotName = (string) ($data["ingot_name"] ?? ("Lingot " . $key));
            $hardness = (float) ($data["hardness"] ?? 3.0);
            $oreTexture = $this->resolveTextureKey((string) ($data["ore_texture"] ?? $oreId), $oreId);
            $ingotTexture = $this->resolveTextureKey((string) ($data["ingot_texture"] ?? $ingotId), $ingotId);
            $creative = (bool) ($data["creative"] ?? true);

            $creativeInfo = $creative ? $this->makeBlockCreativeInfo($data) : $this->makeHiddenCreativeInfo();
            $this->registerMineralBlock(
                static fn() : Block => new CustomMineralBlock(
                    new BlockIdentifier(BlockTypeIds::newId()),
                    $oreName,
                    new BlockTypeInfo(BlockBreakInfo::pickaxe($hardness)),
                    $oreTexture
                ),
                $oreIdentifier,
                $oreTexture,
                $creativeInfo
            );

            $block = CustomiesBlockFactory::getInstance()->get($oreIdentifier);
            if($block instanceof Block){
                $this->oreBlocks[$key] = $block;
                $this->oreDrops[$block->getTypeId()] = $key;
                if($creative){
                    $this->plugin->forceCreativePlacement($block->asItem(), $creativeInfo);
                }
            }

            $data["ore_identifier"] = $oreIdentifier;
            $data["ore_texture"] = $oreTexture;
            $data["ingot_identifier"] = $ingotIdentifier;
            $data["ingot_texture"] = $ingotTexture;
            $this->mineralConfigs[$key] = $data;
        }
    }


    /**
     * Enregistre un minerai avec les deux signatures connues de Customies :
     * - API avec Model en 3e argument : registerBlock($factory, $id, $model, $creativeInfo)
     * - API récente : registerBlock($factory, $id, $creativeInfo)
     *
     * @param \Closure(): Block $blockFactory
     */
    private function registerMineralBlock(\Closure $blockFactory, string $identifier, string $texture, ?CreativeInventoryInfo $creativeInfo) : void{
        $factory = CustomiesBlockFactory::getInstance();
        $method = new \ReflectionMethod($factory, "registerBlock");
        $parameters = $method->getParameters();
        $thirdParameter = $parameters[2] ?? null;
        $thirdType = $thirdParameter?->getType();
        $thirdTypeName = $thirdType instanceof \ReflectionNamedType ? ltrim($thirdType->getName(), "\\") : "";

        $modelClass = "customiesdevs\\customies\\block\\Model";
        $usesModelArgument = $thirdParameter !== null && (
            strtolower($thirdParameter->getName()) === "model" ||
            $thirdTypeName === $modelClass
        );

        if($usesModelArgument){
            // Customies 1.4.0 / API PMMP 5 utilisée par le serveur :
            // le 3e argument est bien ?Model. Le module MineraisPlus d'origine
            // enregistrait ses blocs avec un modèle null et la texture fournie
            // par blocks.json / terrain_texture.json du resource pack.
            $method->invokeArgs($factory, [$blockFactory, $identifier, null, $creativeInfo]);
            return;
        }

        $method->invokeArgs($factory, [$blockFactory, $identifier, $creativeInfo]);
    }

    private function resolveTextureKey(string $texture, string $defaultTexture) : string{
        // Pour Customies, le nom donné à minecraft:icon doit correspondre à une clé
        // de textures/item_texture.json. Les clés simples évitent les soucis d'icône invisible.
        $texture = strtolower(trim($texture));
        if($texture === ""){
            $texture = strtolower($defaultTexture);
        }

        if(str_contains($texture, ":")){
            $parts = explode(":", $texture, 2);
            return strtolower($parts[1] ?? $defaultTexture);
        }

        return $texture;
    }

    /** @param array<string, mixed> $data */
    private function makeBlockCreativeInfo(array $data) : ?CreativeInventoryInfo{
        if(!class_exists(CreativeInventoryInfo::class)){
            return null;
        }

        $category = trim((string) ($data["creative_category"] ?? ""));
        if($category === ""){
            $category = defined(CreativeInventoryInfo::class . "::CATEGORY_NATURE")
                ? (string) constant(CreativeInventoryInfo::class . "::CATEGORY_NATURE")
                : "nature";
        }

        $group = trim((string) ($data["creative_group"] ?? ""));
        if($group === ""){
            $group = defined(CreativeInventoryInfo::class . "::GROUP_ORE")
                ? (string) constant(CreativeInventoryInfo::class . "::GROUP_ORE")
                : "itemGroup.name.ore";
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

    /** @return string[] */
    public function getMineralNames() : array{
        return array_keys($this->oreBlocks);
    }

    public function getDefaultRadius() : int{
        return (int) $this->plugin->getConfig()->getNested("generation.default-radius", 64);
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $block = $event->getBlock();
        $mineralName = $this->oreDrops[$block->getTypeId()] ?? null;
        if($mineralName === null){
            return;
        }

        $config = $this->mineralConfigs[$mineralName] ?? [];
        $drops = $this->createDropsFromConfig($this->getDropConfigList($config));
        $event->setDrops($drops);
    }

    /** @param array<string, mixed> $config @return array<int, mixed> */
    private function getDropConfigList(array $config) : array{
        $drops = $config["drops"] ?? null;
        if(is_array($drops) && $drops !== []){
            return $drops;
        }

        $identifier = (string) ($config["ingot_identifier"] ?? "");
        $amount = max(1, (int) ($config["drop_amount"] ?? 1));
        return [["item" => $identifier, "amount" => $amount, "chance" => 100]];
    }

    /** @param array<int, mixed> $dropConfigs @return Item[] */
    private function createDropsFromConfig(array $dropConfigs) : array{
        $drops = [];

        foreach($dropConfigs as $dropData){
            if(is_string($dropData)){
                $itemName = $dropData;
                $amount = 1;
                $chance = 100;
            }elseif(is_array($dropData)){
                $itemName = (string) ($dropData["item"] ?? $dropData["id"] ?? "");
                $chance = max(0, min(100, (int) ($dropData["chance"] ?? 100)));
                if(isset($dropData["min"]) || isset($dropData["max"])){
                    $minAmount = max(0, (int) ($dropData["min"] ?? 1));
                    $maxAmount = max($minAmount, (int) ($dropData["max"] ?? $minAmount));
                    $amount = mt_rand($minAmount, $maxAmount);
                }else{
                    $amount = max(0, (int) ($dropData["amount"] ?? 1));
                }
            }else{
                continue;
            }

            if($itemName === "" || $amount <= 0 || $chance <= 0 || mt_rand(1, 100) > $chance){
                continue;
            }

            $item = $this->makeDropItem($itemName, $amount);
            if($item instanceof Item && !$item->isNull()){
                $drops[] = $item;
            }
        }

        return $drops;
    }

    private function makeDropItem(string $identifier, int $amount) : ?Item{
        $identifier = strtolower(str_replace(" ", "_", trim($identifier)));
        if($identifier === ""){
            return null;
        }

        if(str_contains($identifier, ":") && !str_starts_with($identifier, "minecraft:")){
            try{
                return CustomiesItemFactory::getInstance()->get($identifier, $amount);
            }catch(Throwable){
                // On continue : l'item peut venir d'un parser PMMP, ou l'autre plugin peut ne pas être chargé.
            }
        }

        try{
            $item = StringToItemParser::getInstance()->parse($identifier);
            if($item instanceof Item){
                return $item->setCount($amount);
            }
        }catch(Throwable){
        }

        if(!str_contains($identifier, ":")){
            try{
                $item = StringToItemParser::getInstance()->parse("minecraft:" . $identifier);
                if($item instanceof Item){
                    return $item->setCount($amount);
                }
            }catch(Throwable){
            }
        }

        try{
            return CustomiesItemFactory::getInstance()->get($identifier, $amount);
        }catch(Throwable){
        }

        if(!isset($this->warnedInvalidDrops[$identifier])){
            $this->warnedInvalidDrops[$identifier] = true;
            $this->plugin->getLogger()->warning("Drop invalide dans config.yml : " . $identifier);
        }
        return null;
    }

    public function generateOresAround(Player $player, int $radius) : int{
        $world = $player->getWorld();
        $pos = $player->getPosition();
        $centerX = (int) $pos->getX();
        $centerZ = (int) $pos->getZ();
        $placed = 0;

        foreach($this->oreBlocks as $key => $oreBlock){
            $config = $this->mineralConfigs[$key] ?? [];
            $veinCount = max(1, (int) ($config["vein_count"] ?? 10));
            $veinSize = max(1, (int) ($config["vein_size"] ?? 4));
            [$minY, $maxY] = $this->getMineralYRange($config);

            for($vein = 0; $vein < $veinCount; $vein++){
                $x = $centerX + mt_rand(-$radius, $radius);
                $y = mt_rand($minY, $maxY);
                $z = $centerZ + mt_rand(-$radius, $radius);
                $placed += $this->generateVein($world, $oreBlock, $x, $y, $z, $veinSize);
            }
        }

        return $placed;
    }

    public function startFullMapGeneration(CommandSender $sender, string $worldName, string $mineralName) : void{
        if(!isset($this->oreBlocks[$mineralName])){
            $sender->sendMessage(TF::RED . "Minerai inconnu : " . $mineralName);
            $sender->sendMessage(TF::GRAY . "Minerais disponibles : " . implode(", ", $this->getMineralNames()));
            return;
        }

        $worldManager = $this->plugin->getServer()->getWorldManager();
        if(!$worldManager->isWorldLoaded($worldName)){
            try{
                $worldManager->loadWorld($worldName);
            }catch(Throwable $e){
                $sender->sendMessage(TF::RED . "Impossible de charger le monde " . $worldName . " : " . $e->getMessage());
                return;
            }
        }

        $world = $worldManager->getWorldByName($worldName);
        if($world === null){
            $sender->sendMessage(TF::RED . "Monde introuvable : " . $worldName);
            return;
        }

        try{
            $provider = $world->getProvider();
            $totalChunks = max(0, $provider->calculateChunkCount());
            $chunks = $provider->getAllChunks(true, $this->plugin->getLogger());
        }catch(Throwable $e){
            $sender->sendMessage(TF::RED . "Impossible de lire les chunks du monde : " . $e->getMessage());
            return;
        }

        if($totalChunks <= 0){
            $sender->sendMessage(TF::RED . "Aucun chunk généré trouvé dans le monde " . $worldName . ".");
            return;
        }

        $chunksPerTick = max(1, (int) $this->plugin->getConfig()->getNested("generation.full-map-chunks-per-tick", 8));
        $this->plugin->getScheduler()->scheduleRepeatingTask(new FullMapGenerationTask($this, $sender, $world, $worldName, $mineralName, $chunks, $totalChunks, $chunksPerTick), 1);
        $sender->sendMessage(TF::GREEN . "Génération lancée : " . $mineralName . " dans le monde " . $worldName . " (" . $totalChunks . " chunks).");
    }

    public function generateMineralInChunkData(World $world, string $mineralName, int $chunkX, int $chunkZ, mixed $loadedChunkData) : int{
        $oreBlock = $this->oreBlocks[$mineralName] ?? null;
        $config = $this->mineralConfigs[$mineralName] ?? [];
        if(!$oreBlock instanceof Block || !$loadedChunkData instanceof LoadedChunkData){
            return 0;
        }

        if(method_exists($world, "isChunkLoaded") && $world->isChunkLoaded($chunkX, $chunkZ)){
            return $this->generateMineralInWorldChunk($world, $oreBlock, $config, $chunkX, $chunkZ);
        }

        $chunkData = $loadedChunkData->getData();
        $placed = $this->generateMineralInStoredChunkData($chunkData, $oreBlock, $config);
        if($placed > 0){
            $newData = new ChunkData($chunkData->getSubChunks(), $chunkData->isPopulated(), $chunkData->getEntityNBT(), $chunkData->getTileNBT());
            $world->getProvider()->saveChunk($chunkX, $chunkZ, $newData, Chunk::DIRTY_FLAG_BLOCKS);
        }

        return $placed;
    }

    /** @param array<string, mixed> $config */
    private function generateMineralInWorldChunk(World $world, Block $oreBlock, array $config, int $chunkX, int $chunkZ) : int{
        [$minY, $maxY] = $this->getMineralYRange($config);
        $veinSize = max(1, (int) ($config["vein_size"] ?? 4));
        $veinsPerChunk = max(1, (int) ($config["full_map_veins_per_chunk"] ?? $this->plugin->getConfig()->getNested("generation.full-map-veins-per-chunk", 1)));
        $chance = max(1, min(100, (int) ($config["full_map_chance_percent"] ?? $this->plugin->getConfig()->getNested("generation.full-map-chance-percent", 65))));
        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;
        $placed = 0;

        for($vein = 0; $vein < $veinsPerChunk; $vein++){
            if(mt_rand(1, 100) > $chance){
                continue;
            }
            $placed += $this->generateVein($world, $oreBlock, $baseX + mt_rand(0, 15), mt_rand($minY, $maxY), $baseZ + mt_rand(0, 15), $veinSize);
        }

        return $placed;
    }

    /** @param array<string, mixed> $config */
    private function generateMineralInStoredChunkData(ChunkData $chunkData, Block $oreBlock, array $config) : int{
        [$minY, $maxY] = $this->getMineralYRange($config);
        $veinSize = max(1, (int) ($config["vein_size"] ?? 4));
        $veinsPerChunk = max(1, (int) ($config["full_map_veins_per_chunk"] ?? $this->plugin->getConfig()->getNested("generation.full-map-veins-per-chunk", 1)));
        $chance = max(1, min(100, (int) ($config["full_map_chance_percent"] ?? $this->plugin->getConfig()->getNested("generation.full-map-chance-percent", 65))));
        $placed = 0;

        for($vein = 0; $vein < $veinsPerChunk; $vein++){
            if(mt_rand(1, 100) > $chance){
                continue;
            }
            $placed += $this->generateVeinInChunkData($chunkData, $oreBlock, mt_rand(0, 15), mt_rand($minY, $maxY), mt_rand(0, 15), $veinSize);
        }

        return $placed;
    }

    private function generateVein(World $world, Block $oreBlock, int $x, int $y, int $z, int $size) : int{
        $placed = 0;
        $px = $x;
        $py = $y;
        $pz = $z;

        for($i = 0; $i < $size; $i++){
            if($py >= -64 && $py <= 320 && $this->canReplace($world->getBlockAt($px, $py, $pz))){
                $world->setBlockAt($px, $py, $pz, clone $oreBlock);
                $placed++;
            }

            $px += mt_rand(-1, 1);
            $py += mt_rand(-1, 1);
            $pz += mt_rand(-1, 1);
        }

        return $placed;
    }

    private function generateVeinInChunkData(ChunkData $chunkData, Block $oreBlock, int $x, int $y, int $z, int $size) : int{
        $subChunks = $chunkData->getSubChunks();
        $stateId = $oreBlock->getStateId();
        $placed = 0;
        $px = $x;
        $py = $y;
        $pz = $z;

        for($i = 0; $i < $size; $i++){
            $subChunkY = $py >> 4;
            if($px >= 0 && $px <= 15 && $pz >= 0 && $pz <= 15 && $py >= -64 && $py <= 320 && isset($subChunks[$subChunkY])){
                $subChunk = $subChunks[$subChunkY];
                $oldStateId = $subChunk->getBlockStateId($px, $py & 15, $pz);
                if($this->canReplaceStateId($oldStateId)){
                    $subChunk->setBlockStateId($px, $py & 15, $pz, $stateId);
                    $placed++;
                }
            }

            $px += mt_rand(-1, 1);
            $py += mt_rand(-1, 1);
            $pz += mt_rand(-1, 1);
        }

        return $placed;
    }

    /** @param array<string, mixed> $config @return array{int, int} */
    private function getMineralYRange(array $config) : array{
        $minY = max(-64, (int) ($config["min_y"] ?? -60));
        $maxY = min(320, (int) ($config["max_y"] ?? 40));

        if($maxY < $minY){
            [$minY, $maxY] = [$maxY, $minY];
        }

        return [$minY, $maxY];
    }

    private function canReplace(Block $block) : bool{
        return in_array(strtolower($block->getName()), $this->replaceBlocks, true);
    }

    private function canReplaceStateId(int $stateId) : bool{
        try{
            return $this->canReplace(RuntimeBlockStateRegistry::getInstance()->fromStateId($stateId));
        }catch(Throwable){
            return false;
        }
    }
}
