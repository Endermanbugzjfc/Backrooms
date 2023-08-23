<?php

namespace Endermanbugzjfc\Backrooms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\generator\GeneratorManager;

final class BackroomsListener implements Listener {
    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $data = $player->getWorld()->getProvider()->getWorldData();
        $gen = GeneratorManager::getInstance()->getGenerator($data->getGenerator())?->getGeneratorClass();
        switch (false) {
            case $gen !== null:
            case $event->getAction() === $event::RIGHT_CLICK_BLOCK:
            case is_a($gen, Backrooms::class, true):
            case $gen::GAMEPLAY_NO_CEIL_INTERACTION:
            case $gen::calculateCeilY($data->getSeed()) !== $event->getBlock()->getPosition()->getFloorY():
                return;
        }

        $event->cancel();
    }
}