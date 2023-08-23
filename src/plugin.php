<?php

declare(strict_types=1);

namespace Endermanbugzjfc\Backrooms;

use Endermanbugzjfc\Backrooms\BackroomsListener;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\world\WorldEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\InvalidGeneratorOptionsException;

final class Main extends PluginBase
{
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

    protected function onEnable(): void {
        foreach ([
            BackroomsListener::class,
        ] as $listener) $this->getServer()->getPluginManager()->registerEvents(new $listener($this->getScheduler()), $this);
    }

}

/**
 * @template-covariant G
 */
abstract class SceneListener implements Listener {
    public function __construct(
        protected readonly TaskScheduler $scheduler,
    ) {

    }
    /**
     * @phpstan-param \Closure(class-string<G> $gen): bool $flag
     * @phpstan-return ?class-string<G>
     */
    protected function gen(PlayerEvent|WorldEvent $event, \Closure $flag) : ?string {
        if ($event instanceof PlayerEvent) $event = $event->getPlayer();
        $world = $event->getWorld();

        $data = $world->getProvider()->getWorldData();
        $gen = GeneratorManager::getInstance()->getGenerator($data->getGenerator())?->getGeneratorClass();
        if ($gen === null) return null;

        $class = new \ReflectionClass($this);
        $targets = $class->getAttributes(TargetGen::class);
        foreach ($targets as $target) {
            [$targetClass] = $target->getArguments();
            if (is_a($gen, $targetClass, true)) return $flag($gen) ? $gen : null;
        }

        return null;
    }
}

#[\Attribute(\Attribute::TARGET_CLASS)]
final class TargetGen {
    /**
     * @phpstan-param class-string<\pocketmine\world\Generator> $gen
     */
    public function __construct(
        public readonly string $gen,
    ) {
    }
}