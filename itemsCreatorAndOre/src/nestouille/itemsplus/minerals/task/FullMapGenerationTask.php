<?php

declare(strict_types=1);

namespace nestouille\itemsplus\minerals\task;

use Generator;
use nestouille\itemsplus\minerals\MineralsManager;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\World;
use Throwable;
use function count;
use function is_array;
use function max;
use function round;

final class FullMapGenerationTask extends Task{

    private int $processedChunks = 0;
    private int $placedBlocks = 0;
    private int $lastPercent = -1;

    /**
     * @param Generator<array{int, int}, mixed, void, void> $chunks
     */
    public function __construct(
        private MineralsManager $plugin,
        private CommandSender $sender,
        private World $world,
        private string $worldName,
        private string $mineralName,
        private Generator $chunks,
        private int $totalChunks,
        private int $chunksPerTick
    ){
        $this->chunksPerTick = max(1, $this->chunksPerTick);
    }

    public function onRun() : void{
        if(!$this->world->isLoaded()){
            $this->sender->sendMessage(TF::RED . "Génération annulée : le monde a été déchargé.");
            $this->getHandler()?->cancel();
            return;
        }

        $doneThisTick = 0;
        while($doneThisTick < $this->chunksPerTick && $this->chunks->valid()){
            $chunkPos = $this->chunks->key();
            $loadedChunkData = $this->chunks->current();

            if(is_array($chunkPos) && count($chunkPos) === 2){
                $chunkX = (int) $chunkPos[0];
                $chunkZ = (int) $chunkPos[1];

                try{
                    $this->placedBlocks += $this->plugin->generateMineralInChunkData($this->world, $this->mineralName, $chunkX, $chunkZ, $loadedChunkData);
                }catch(Throwable $e){
                    $this->plugin->getLogger()->warning("Erreur pendant la génération du chunk {$chunkX}:{$chunkZ} : " . $e->getMessage());
                }
            }

            $this->processedChunks++;
            $doneThisTick++;
            $this->chunks->next();
        }

        $percent = $this->totalChunks > 0 ? (int) round(($this->processedChunks / $this->totalChunks) * 100) : 100;
        if($percent >= $this->lastPercent + 10 && $percent < 100){
            $this->lastPercent = $percent;
            $this->sender->sendMessage(TF::GRAY . "Génération " . $this->mineralName . " : " . $percent . "% (" . $this->processedChunks . "/" . $this->totalChunks . " chunks)");
        }

        if(!$this->chunks->valid()){
            try{
                $this->world->save();
            }catch(Throwable $e){
                $this->plugin->getLogger()->warning("Impossible de sauvegarder le monde après génération : " . $e->getMessage());
            }

            $this->sender->sendMessage(TF::GREEN . "✓ Génération terminée dans le monde " . $this->worldName . " : " . $this->placedBlocks . " blocs de " . $this->mineralName . " ajoutés sur " . $this->processedChunks . " chunks.");
            $this->getHandler()?->cancel();
        }
    }
}
