<?php

namespace Endermanbugzjfc\Backrooms;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SignText;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\format\SubChunk;

/**
 * @extends SceneListener<Backrooms>
 */
#[TargetGen(Backrooms::class)]
final class BackroomsListener extends SceneListener {

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

    public function onChunkLoad(ChunkLoadEvent $event) : void {
        $gen = $this->gen($event, fn($gen) => $gen::GAMEPLAY_BLOODY_ARROW_WALL_SIGNS);
        if ($gen === null) return;

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
                    $ids = array_map(
                        fn($block) => $block->getIdInfo()->getBlockTypeId(),
                        [$at, $placeholder],
                    );
                    if (array_unique($ids) === $ids) continue;

                    $pos = new Vector3($worldX, $Y, $worldZ);
                    foreach ([
                        Facing::NORTH,
                        Facing::SOUTH,
                        Facing::WEST,
                        Facing::EAST,
                    ] as $side) {
                        var_dump($pos->getSide($side));
                        if ($world->getBlock($pos->getSide($side))->asItem()->equals(VanillaItems::AIR())) continue;
                        $world->setBlock(
                            $pos,
                            VanillaBlocks::BIRCH_WALL_SIGN()
                            ->setFacing($side)
                            ->setText(new SignText(["HE"], DyeColor::RED()->getRgbValue(), true)),
                        );
                    }
                }
            }
        }
    }
}