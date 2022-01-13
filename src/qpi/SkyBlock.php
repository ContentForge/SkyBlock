<?php

namespace qpi;

use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;
use qpi\command\PlayCommand;
use qpi\island\IslandManager;
use qpi\task\DatabaseTask;
use qpi\world\SkyBlockGenerator;
use qpi\world\VoidGenerator;

class SkyBlock extends PluginBase {

    private \mysqli $db;
    private IslandManager $islandManager;

    protected function onLoad(): void {
        $this->db = new \mysqli("p:dragonestia.ru", "ЛОГИН", "ПАРОЛЬ", "skyblock");

        $generatorManager = GeneratorManager::getInstance();
        $generatorManager->addGenerator(VoidGenerator::class, "void", fn() => null);
        $generatorManager->addGenerator(SkyBlockGenerator::class, "island", fn() => null);

        $this->islandManager = new IslandManager($this);
    }

    protected function onEnable(): void {
        $this->getServer()->getCommandMap()->registerAll("SkyBlock", [
            new PlayCommand($this),
        ]);

        $pluginManager = $this->getServer()->getPluginManager();
        $pluginManager->registerEvents($this->islandManager, $this);

        $this->getScheduler()->scheduleRepeatingTask(new DatabaseTask($this->db), 20 * 60);
    }

    protected function onDisable(): void {

    }

    public function getIslandManager(): IslandManager {
        return $this->islandManager;
    }

    public function getDatabase(): \mysqli{
        return $this->db;
    }

}