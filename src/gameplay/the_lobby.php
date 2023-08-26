<?php

namespace Endermanbugzjfc\Backrooms;

use Endermanbugzjfc\Backrooms\TheLobby\TheLobby;
use pocketmine\block\Block;
use pocketmine\block\MobHead;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SignText;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\ChunkEvent;
use pocketmine\event\world\ChunkPopulateEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\format\SubChunk;

/**
 * @extends SceneListener<TheLobby>
 */
#[TargetGen(TheLobby::class)]
final class TheLobbyListener extends SceneListener {

    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        $gen = $this->gen($event, fn($gen) => $gen::GAMEPLAY_NO_CEIL_INTERACTION);
        if ($gen === null) return;

        $player = $event->getPlayer();
        $data = $player->getWorld()->getProvider()->getWorldData();
        switch (false) {
            case !$player->isSneaking():
            case $event->getAction() === $event::RIGHT_CLICK_BLOCK:
            case $gen::calculateCeilY($data->getSeed()) === $event->getBlock()->getPosition()->getFloorY():
            return;
        }

        $event->cancel();
        $item = $event->getItem();
        $player->getWorld()->useItemOn(
            $event->getBlock()->getPosition(),
            $item,
            $event->getFace(),
            $event->getTouchVector(),
            playSound: true,
        );
        if ($player->hasFiniteResources()) $player->getInventory()->setItemInHand($item);
    }

    public function onChunkPopulate(ChunkPopulateEvent $event) : void {
        $flags = null;
        $gen = $this->gen($event, function ($gen) use (&$flags) {
            return in_array(true, $flags = [
                $gen::GAMEPLAY_SPAWN_SKULLS,
                $gen::GAMEPLAY_BLOODY_ARROW_WALL_SIGNS,
            ]);
        }, true);
        if ($gen === null) return;

        // Note: the order matters.
        if ($flags[0]) $this->skulls($event, $gen);
        if ($flags[1]) $this->signs($event, $gen);
    }

    /**
     * @phpstan-param class-string<TheLobby> $gen
     */
    private function signs(ChunkEvent $event, string $gen) : void {
        $world = $event->getWorld();
        $from = $gen::POPULATOR_PILLAR::BLOODY_ARROW_WALL_SIGNS_MIN_Y;
        $to = $gen::POPULATOR_PILLAR::BLOODY_ARROW_WALL_SIGNS_MAX_Y;
        $placeholder = $gen::POPULATOR_PILLAR::getWallSignPlaceholder();
        for ($X = 0; $X < SubChunk::EDGE_LENGTH; $X++) {
            for ($Z = 0; $Z < SubChunk::EDGE_LENGTH; $Z++) {
                for ($Y = $from; $Y <= $to; $Y++) {
                    $at = $world->getBlockAt(
                        $worldX = ($event->getChunkX() << SubChunk::COORD_BIT_SIZE) + $X,
                        $Y,
                        $worldZ = ($event->getChunkZ() << SubChunk::COORD_BIT_SIZE) + $Z,
                    );
                    if (!$this::checkPlaceholder($at, $placeholder)) continue;

                    $pos = new Vector3($worldX, $Y, $worldZ);
                    foreach ([
                        Facing::NORTH,
                        Facing::SOUTH,
                        Facing::WEST,
                        Facing::EAST,
                    ] as $side) {
                        if ($world->getBlock($pos->getSide($side))->asItem()->equals(VanillaItems::AIR())) continue;
                        $world->setBlock(
                            $pos,
                            VanillaBlocks::BIRCH_WALL_SIGN()
                            ->setFacing(Facing::opposite($side))
                            ->setText(new SignText([
                                "=====>",
                                "=====>",
                                "=====>",
                            ], DyeColor::RED()->getRgbValue(), true)),
                            update: false,
                        );
                        return;
                    }
                }
            }
        }
    }

    /**
     * @phpstan-param class-string<TheLobby> $gen
     */
    private function skulls(ChunkEvent $event, string $gen) : void {
        $world = $event->getWorld();
        for ($X = 0; $X < SubChunk::EDGE_LENGTH; $X++) {
            for ($Z = 0; $Z < SubChunk::EDGE_LENGTH; $Z++) {
                $at = $world->getBlockAt(
                    $worldX = ($event->getChunkX() << SubChunk::COORD_BIT_SIZE) + $X,
                    $worldY = $gen::POPULATOR_CORPSE::PROCESS_Y,
                    $worldZ = ($event->getChunkZ() << SubChunk::COORD_BIT_SIZE) + $Z,
                );
                if (!$at instanceof MobHead) continue;

                $rotation = 6 + $at->getFacing();
                $hack = $world->getBlockAt(
                    $worldX,
                    $worldY += $gen::POPULATOR_CORPSE::ROTATION_HACK_OFFSET_Y,
                    $worldZ,
                );
                $hackPlaceholder = $gen::POPULATOR_CORPSE::getRotationHackPlaceholder();
                if ($this::checkPlaceholder($hack, $hackPlaceholder)) {
                    $rotation += 4;
                    $world->setBlock($hack->getPosition(), VanillaBlocks::AIR(), update: false);
                }

                $at
                ->setRotation($rotation)
                ->setFacing(Facing::UP)
                ->writeStateToWorld();
            }
        }
    }

    private static function checkPlaceholder(Block $block, Block $placeholder) : bool {
        $ids = array_map(
            fn($block) => $block->getIdInfo()->getBlockTypeId(),
            [$block, $placeholder],
        );

        return array_unique($ids) !== $ids;

    }

}