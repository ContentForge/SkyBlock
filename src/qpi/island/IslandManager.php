<?php

namespace qpi\island;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use qpi\SkyBlock;

class IslandManager implements Listener {

    /** @var Island[] */
    private array $islands = [];
    /** @var int[] */
    private array $assoc = [];
    private bool $started = false;

    public function __construct(private SkyBlock $main) {
        @mkdir($this->main->getDataFolder() . "/worlds/");
        @mkdir($this->main->getDataFolder() . "/options/");
    }

    public function getIsland(int $id): Island {
        $key = (string) $id;
        if(!isset($this->islands[$key])) $this->islands[$key] = new Island($id, $this->main);
        return $this->islands[$key];
    }

    public function isInGame(Player $player): bool {
        return $player->getWorld()->getFolderName() !== $this->main->getServer()->getWorldManager()->getDefaultWorld()->getFolderName();
    }

    public function getGame(Player $player): ?Island {
        return $this->isInGame($player)? $this->getIsland($player->getWorld()->getFolderName()) : null;
    }

    public function getPlayerId(Player $player): int {
        return $this->assoc[$player->getXuid()];
    }

    public function onDamage(EntityDamageEvent $event){
        $player = $event->getEntity();

        if(!$player instanceof Player) return;
        if($this->isInGame($player) && $event instanceof EntityDamageByEntityEvent && $event->getDamager() instanceof Player){
            $island = $this->getGame($player);

            if(!$island->canPvp()) $event->cancel();
            return;
        }

        $event->cancel();

        if($event->getCause() !== EntityDamageEvent::CAUSE_VOID) return;
        $player->teleport($player->getWorld()->getSpawnLocation());
    }

    public function onLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();

        $db = $this->main->getDatabase();
        $data = (int) $db->query("SELECT COUNT(*) FROM assoc WHERE xuid = '{$player->getXuid()}';")->fetch_row()[0];

        if($data === 0){
            $db->query("INSERT INTO assoc (name, xuid) VALUES ('{$player->getName()}', '{$player->getXuid()}');");
        }
        $this->assoc[$player->getXuid()] = (int) $db->query("SELECT uid FROM assoc WHERE xuid = '{$player->getXuid()}' LIMIT 1;")->fetch_row()[0];

        if(!$this->started){
            $this->started = true;
            $world = $this->main->getServer()->getWorldManager()->getDefaultWorld();
            $world->setTime(5000);
            $world->stopTime();
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage("");

        $player->setGamemode(GameMode::ADVENTURE());
        $player->teleport($player->getWorld()->getSpawnLocation());

        $player->sendMessage(TextFormat::YELLOW . "Добро пожаловать на сервер! Введите команду /play для того чтобы начать играть.");
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $island = $this->getGame($player);

        $event->setQuitMessage("");
        unset($this->assoc[$player->getXuid()]);
        if($island === null) return;

        $island->onQuit($player);
    }

    public function onDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        $island = $this->getGame($player);

        $event->setDeathMessage("");
        if($island === null) return;

        $player->setSpawn($island->getWorld()->getSpawnLocation());
    }

}