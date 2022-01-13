<?php

namespace qpi\world;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;
use qpi\world\populator\SkyIsland;

class SkyBlockGenerator extends Generator {

    private array $populators;

    public function __construct(int $seed, string $preset) {
        parent::__construct($seed, $preset);

        $this->populators = [
            new SkyIsland(),
        ];
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        $chunk = $world->getChunk($chunkX, $chunkZ);

        for($x = 0; $x < 16; $x++){
            for($z = 0; $z < 16; $z++){
                $chunk->setBiomeId($x, $z, BiomeIds::PLAINS);
            }
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        foreach ($this->populators as $populator){
            $populator->populate($world, $chunkX, $chunkZ, $this->random);
        }
    }

}