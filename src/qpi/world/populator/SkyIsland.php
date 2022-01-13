<?php

namespace qpi\world\populator;

use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\object\OakTree;
use pocketmine\world\generator\populator\Populator;

class SkyIsland implements Populator {

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
        if($chunkX != 0 || $chunkZ != 0) return;

        for($y = 51; $y < 55; $y++){
            for($x = 0; $x < 6; $x++){
                for($z = 0; $z < 6; $z++){
                    if($x > 2 && $z > 2) continue;

                    $world->setBlockAt($x, $y, $z, $y === 54? VanillaBlocks::GRASS() : VanillaBlocks::DIRT());
                }
            }
        }
        
        (new OakTree())->getBlockTransaction($world, 5, 55, 1, $random)->apply();
    }

}