<?php

declare(strict_types=1);

namespace Endermanbugzjfc\Backrooms;

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\populator\Populator;

class Backrooms extends Generator {

    public function __construct(int $seed, string $preset){
        parent::__construct($seed, $preset);
        $this->populators = [
            new Pillar,
        ];
        $this->generateBaseChunk();
    }

    public static function calculateCeilY(int $seed) : int {
        return $seed & 8;
    }

    public static function calculateFloorAxis(int $seed) : int {
        return 0b10 << ($seed & 1);
    }

    private Chunk $chunk;
    protected function generateBaseChunk() : void{
        $seed = $this->seed;
        $floorAxis = self::calculateFloorAxis($seed);
        $height = self::calculateCeilY($seed);
        $floor = VanillaBlocks::BIRCH_LOG()
        ->setAxis(Facing::axis($floorAxis))
        ->setStripped(true)
        ->getStateId();
        $ceil = VanillaBlocks::LOOM()->getStateId();
        $light = VanillaBlocks::SEA_LANTERN()->getStateId();

        $this->chunk = new Chunk([], false);
        $floorSub = $this->chunk->getSubChunk(0);
        $ceilSub = $this->chunk->getSubChunk($height >> SubChunk::COORD_BIT_SIZE);
        for ($X = 0; $X < SubChunk::EDGE_LENGTH; $X++){
            for ($Z = 0; $Z < SubChunk::EDGE_LENGTH; $Z++){
                $interupt = null;
                foreach ([$X, $Z] as $point) if ($interupt = match ($point) {
                    8, 9, 0, 15 => false,
                    default => true,
                }) break;
                $floorSub->setBlockStateId($X, 0, $Z, $floor);
                $ceilSub->setBlockStateId($X, $height & SubChunk::COORD_MASK, $Z, $interupt ? $ceil : $light);
            }
        }
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
        $world->setChunk($chunkX, $chunkZ, clone $this->chunk);
    }

    /**
     * @var Populator[]
     */
    private array $populators = [];

    /**
     * Walls and pillars.
     */
    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void{
        $this->random->setSeed(0x19890604 ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
        foreach ($this->populators as $populator) $populator->populate(
            $world,
            $chunkX,
            $chunkZ,
            $this->random,
        );
    }
}

class Pillar implements Populator {
    /**
     * @param int $densityRate non-zero unsigned.
     */
    public function __construct(
        public readonly int $densityRate = 1 << 16,
    ) {

    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void {
        $XZ = [0, 0];
        foreach ($XZ as &$n) $n = $random->nextBoundedInt(SubChunk::EDGE_LENGTH);
        [$X, $Z] = $XZ;
        $air = VanillaBlocks::AIR()->getStateId();
        $wall = VanillaBlocks::END_STONE()->getStateId();

        $chunk = $world->getChunk($chunkX, $chunkZ);
        $thisRolled = $lastRolled = null;
        $corners = -1;
        $rollMoveAxis = fn(&$thisRolled) => $thisRolled = (bool)$random->nextBoundedInt(2);
        $move = function (&$n) use (&$chunk, &$X, &$Z, $world) {
            if (++$n === SubChunk::EDGE_LENGTH) $chunk = $world->getChunk(
                $X >> SubChunk::COORD_BIT_SIZE,
                $Z >> SubChunk::COORD_BIT_SIZE,
            ) ?? $chunk;
        };

        $lenWall = $random->nextBoundedInt(63) + 1;
        for ($cWall = 0; $cWall < $lenWall; $cWall++) {
            $sub = null;
            for ($Y = 1; ($sub?->getBlockStateId($X, $Y & SubChunk::COORD_MASK, $Z) ?? $air) === $air; $Y++) {
                $sub = $chunk->getSubChunk($Y >> SubChunk::COORD_BIT_SIZE);
                $sub->setBlockStateId($X, $Y & SubChunk::COORD_MASK, $Z, $wall);
            }

            if ($corners < 2 ? $rollMoveAxis($thisRolled) : $lastRolled) $move($X);
            else $move($Z);

            if ($lastRolled !== $thisRolled) $corners++;
            $lastRolled = $thisRolled;
        }
    }
}