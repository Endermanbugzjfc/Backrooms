<?php

declare(strict_types=1);

namespace Endermanbugzjfc\Backrooms;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\populator\Populator;

class Backrooms extends Generator {
    /**
     * This option exists because the ceil is made out of looms.
     * Interactions with the ceil will result in normal block-placing.
     * Override me.
     */
    public const GAMEPLAY_NO_CEIL_INTERACTION = true;
    /**
     * This option exists as an extended world generation in the main thread
     * in order to place {@link \pocketmine\block\tile\Sign} (wall signs).
     * Override me.
     */
    public const GAMEPLAY_BLOODY_ARROW_WALL_SIGNS = true;
    /**
     * Override me.
     */
    public const POPULATOR_PILLAR = Pillar::class;

    public function __construct(int $seed, string $preset){
        parent::__construct($seed, $preset);
        $this->populators = [
            new ($this::POPULATOR_PILLAR),
        ];
        $this->generateBaseChunk();
    }

    public static function calculateCeilY(int $seed) : int {
        return ($seed & 0b111) + 3;
    }

    public static function calculateFloorAxis(int $seed) : int {
        return Facing::NORTH << ($seed & 1);
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
    public const BLOODY_ARROW_WALL_SIGNS_MIN_Y = 1;
    public const BLOODY_ARROW_WALL_SIGNS_MAX_Y = 2;

    public function __construct(
        public readonly int $maxWallLength = 1 << 6,
        public readonly int $maxWallCorners = 2,
        public readonly bool $bloodyArrowWallSigns = true,
    ) {

    }

    public static function getWallSignPlaceholder() : Block {
        return VanillaBlocks::INFO_UPDATE();
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random) : void {
        $_ = null;
        $XZ = [0, 0];

        foreach ($XZ as &$n) $n = $random->nextBoundedInt(SubChunk::EDGE_LENGTH);
        [$X, $Z] = $XZ;
        $air = VanillaBlocks::AIR()->getStateId();
        $wall = VanillaBlocks::END_STONE()->getStateId();
        $foot = VanillaBlocks::SMOOTH_SANDSTONE()->getStateId();
        $placeholder = $this::getWallSignPlaceHolder()->getStateId();

        $chunk = $world->getChunk($chunkX, $chunkZ);
        $thisRolled = $lastRolled = null;
        $corners = -1;
        $rollFifty = fn(&$thisRolled) => $thisRolled = (bool)$random->nextBoundedInt(2);
        $move = function (&$n, $step) use (&$chunk, &$X, &$Z, $world) {
            $n += $step;
            if ($n === SubChunk::EDGE_LENGTH) $chunk = $world->getChunk(
                $X >> SubChunk::COORD_BIT_SIZE,
                $Z >> SubChunk::COORD_BIT_SIZE,
            ) ?? $chunk;
        };
        $X = $Y = $Z = $sub = null;
        $place = function ($block) use (&$sub, &$X, &$Y, &$Z, &$chunk) {
            $sub = $chunk->getSubChunk($Y >> SubChunk::COORD_BIT_SIZE);
            $sub->setBlockStateId(
                $X & SubChunk::COORD_MASK,
                $Y & SubChunk::COORD_MASK,
                $Z & SubChunk::COORD_MASK,
                $block,
            );
        };

        $lenWall = $random->nextBoundedInt($this->maxWallLength - 1) + 1;
        for ($cWall = 1; $cWall <= $lenWall; $cWall++) {
            $sub = null;
            $Y = -1;
            for ($Y = 1; ($sub?->getBlockStateId(
                $X & SubChunk::COORD_MASK,
                $Y & SubChunk::COORD_MASK,
                $Z & SubChunk::COORD_MASK,
            ) ?? $air) === $air; $Y++) {
                $place($Y !== 1 ? $wall : $foot,);
            }

            if ($cWall === $lenWall) {
                if (!$this->bloodyArrowWallSigns) break;
                if ($rollFifty($_)) break;
                $_ = null;

                $fallbackMove = fn(&$n) => $move($n, $rollFifty($_) ? 1 : -1);
                if ($lastRolled) $fallbackMove($Z); // Axis flip is intented.
                else $fallbackMove($X);
                $Y = $rollFifty($_) ? 1 : 2;
                $place($placeholder);
                break;
            }

            if ($corners < $this->maxWallCorners ? $rollFifty($thisRolled) : $lastRolled) $move($X, 1);
            else $move($Z, 1);

            if ($lastRolled !== $thisRolled) $corners++;
            $lastRolled = $thisRolled;
        }
    }
}