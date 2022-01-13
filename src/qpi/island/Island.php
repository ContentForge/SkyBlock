<?php

namespace qpi\island;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use pocketmine\world\WorldManager;
use qpi\SkyBlock;
use qpi\world\SkyBlockGenerator;
use Webmozart\PathUtil\Path;

class Island {

    private WorldManager $worldManager;
    private ?World $world = null;
    private string $contentPath;
    private bool $preloaded = false;
    private array $options = [];

    public function __construct(private int $id, private SkyBlock $main) {
        $this->worldManager = $this->main->getServer()->getWorldManager();
        $this->contentPath = $this->main->getDataFolder() . "/worlds/{$id}/";
    }

    public function getId(): int {
        return $this->id;
    }

    public function isGenerated(): bool {
        return $this->worldManager->isWorldGenerated((string) $this->id);
    }

    public function generate(): void {
        if($this->isLoaded()){
            foreach ($this->world->getPlayers() as $player){
                $this->onQuit($player);

                $player->sendMessage(TextFormat::YELLOW . "Владелец мира начал процесс перегенерации...");
            }
            if($this->isLoaded()) $this->unload();
        }

        $path = Path::join($this->main->getServer()->getDataPath(), "worlds");
        if($this->isGenerated()){
            $this->removeDir(Path::join($path, (string) $this->id));
            $this->removeDir($this->contentPath);
        }

        @mkdir($this->contentPath);
        @mkdir($this->contentPath . "players/");

        //TODO: Создание нового контента

        $opt = new WorldCreationOptions();
        $opt->setSpawnPosition(new Vector3(1, 55, 3));
        $opt->setGeneratorClass(SkyBlockGenerator::class);
        $this->worldManager->generateWorld((string) $this->id, $opt);
    }

    public function isLoaded(): bool {
        return $this->world !== null && $this->world->isLoaded();
    }

    public function preload(): void {
        if($this->preloaded) return;
        $this->preloaded = true;

        $path = $this->main->getDataFolder() . "options/{$this->id}";
        if(!file_exists($path)){
            $this->options = [];
            $this->saveConf();
        } else $this->options = json_decode(file_get_contents($path), true);

        if(!isset($this->options['password'])) $this->options['password'] = 'Саси хуй!';
        if(!isset($this->options['pvp'])) $this->options['pvp'] = true;
    }

    public function saveConf(): void {
        file_put_contents($this->main->getDataFolder() . "options/{$this->id}", json_encode($this->options));
    }

    public function load(): void {
        $this->worldManager->loadWorld((string) $this->id);
        $this->world = $this->worldManager->getWorldByName((string) $this->id);

        //TODO: Загрузка контента
    }

    public function unload(): void {
        if($this->world === null || !$this->world->isLoaded()) return;

        $this->world->save(true);
        $this->worldManager->unloadWorld($this->world, true);
        //TODO: Сохранение контента
    }

    public function getWorld(): ?World {
        return $this->world;
    }

    public function onJoin(Player $player): void {
        if(!$this->isLoaded()) $this->load();
        $player->getInventory()->clearAll();
        $player->getEffects()->clear();

        $fileName = $this->contentPath . "players/{$player->getXuid()}";
        if(file_exists($fileName)){
            $this->unserialize($player, json_decode(file_get_contents($fileName), true));
        } else {
            $player->teleport($this->world->getSpawnLocation());
        }
        $player->setGamemode(GameMode::SURVIVAL());

        foreach ($this->getWorld()->getPlayers() as $p){
            $p->sendMessage(TextFormat::YELLOW . "Игрок {$player->getName()} подключился к миру.");
        }
    }

    public function onQuit(Player $player): void {
        $data = $this->serialize($player);
        file_put_contents($this->contentPath . "players/{$player->getXuid()}", json_encode($data));

        foreach ($this->getWorld()->getPlayers() as $p){
            $p->sendMessage(TextFormat::YELLOW . "Игрок {$player->getName()} вышел из мира.");
        }

        if($player->isOnline()){
            $player->setGamemode(GameMode::ADVENTURE());
            $player->getInventory()->clearAll();
            $player->getEffects()->clear();
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->getHungerManager()->setFood(20);
            $player->teleport($this->worldManager->getDefaultWorld()->getSpawnLocation());
        }
        if(count($this->world->getPlayers()) === 0) $this->unload();
    }

    private function removeDir(string $path){
        if (is_file($path)) unlink($path);
        if (is_dir($path)) {
            foreach(scandir($path) as $p) if (($p!='.') && ($p!='..'))
                $this->removeDir($path.DIRECTORY_SEPARATOR.$p);
            rmdir($path);
        }
    }

    public function updatePassword(string $newPassword): void {
        $this->options['password'] = $newPassword;
    }

    public function comparePassword(string $password): bool {
        return $this->options['password'] == $password;
    }

    public function canPvp(): bool {
        return $this->options['pvp'];
    }

    public function setCanPvp(bool $value): void {
        $this->options['pvp'] = $value;
    }

    public function isOwner(Player $player): bool {
        return $this->main->getIslandManager()->getPlayerId($player) === $this->id;
    }

    private function serialize(Player $player): array {
        $pos = $player->getPosition();

        $effects = array();
        foreach ($player->getEffects()->all() as $effect){
            //TODO
        }

        return [
            'pos' => [$pos->x, $pos->y, $pos->z],
            'hp' => $player->getHealth(),
            'hunger' => $player->getHungerManager()->getFood(),
            'effects' => $effects,
            'inventory' => $player->getInventory()->getContents(),
            'fall' => $player->getFallDistance(),
        ];
    }

    private function unserialize(Player $player, array $data): void {
        $pos = $data['pos'];
        $player->teleport(new Position($pos[0], $pos[1], $pos[2], $this->world));

        $player->setHealth($data['hp']);
        $player->getHungerManager()->setFood($data['hunger']);

        //TODO: Эффекты

        $inv = array();
        foreach ($data['inventory'] as $slot => $itemJson) {
            $inv[$slot] = Item::jsonDeserialize($itemJson);
        }
        $player->getInventory()->setContents($inv);

        $player->setFallDistance($data['fall']);
    }



}