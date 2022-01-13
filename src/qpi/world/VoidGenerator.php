<?php

namespace qpi\world;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class VoidGenerator extends Generator {

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        for($x = 0; $x < 16; $x++){
            for($z = 0; $z < 16; $z++){
                $chunk->setBiomeId($x, $z, BiomeIds::THE_END);

                if($chunkX === 0 && $chunkZ == 0){
                    $world->setBlockAt(0, 60, 0, VanillaBlocks::STONE());
                }
            }
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {

    }

}