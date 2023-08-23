<?php

declare(strict_types=1);

namespace Endermanbugzjfc\Backrooms;

use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\InvalidGeneratorOptionsException;

final class Main extends PluginBase
{
    protected function onEnable(): void {}

    protected function onLoad(): void
    {
        GeneratorManager::getInstance()->addGenerator(
            Backrooms::class,
            "backrooms",
            function (string $preset): ?InvalidGeneratorOptionsException {
                if ($preset === "") return null;
                return new InvalidGeneratorOptionsException("This server is using a Backrooms version that supports NO generator preset. (Please consider checking for updates.)");
            }
        );
    }
}
