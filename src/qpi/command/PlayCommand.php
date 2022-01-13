<?php

namespace qpi\command;

use form\CustomForm;
use form\ModalForm;
use form\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use qpi\island\Island;
use qpi\island\IslandManager;
use qpi\SkyBlock;

class PlayCommand extends Command {

    private IslandManager $islandManager;

    public function __construct(private SkyBlock $main) {
        parent::__construct("play", "Играть", "/play", []);

        $this->islandManager = $this->main->getIslandManager();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) return;

        $this->sendMainForm($sender);
    }

    public function sendMainForm(Player $player){
        $form = new SimpleForm();
        $island = $this->main->getIslandManager()->getIsland($this->islandManager->getPlayerId($player));
        $currentIsland = $this->islandManager->getGame($player);

        $island->preload();

        if($currentIsland !== null){
            $form->setContent("ID текущей игры: §l§2{$currentIsland->getId()}");
        }

        if($island->isGenerated() && $currentIsland === null){
            $form->addButton("Играть", action: function (Player $player) use ($island) {
                $island->onJoin($player);
            });
        }

        if($currentIsland !== null){
            $form->addButton("Выйти из мира", action: function (Player $player) use ($currentIsland) {
                $currentIsland->onQuit($player);
            });
        }

        $form->addButton("Новая игра", action: function (Player $player) use ($island) {
            if($island->isGenerated()) $this->sendConfirmationForm($player, $island);
            else $this->generate($player, $island);
        });

        $form->addButton("Присоедениться к игре", action: function (Player $player) {
            $this->sendConnectionForm($player);
        });

        if($currentIsland !== null && $currentIsland->isOwner($player)){
            $form->addButton("Настройки игры", action: function (Player $player) use ($island) {
                $this->sendSettingsForm($player);
            });
        }

        if($currentIsland !== null && $currentIsland->isOwner($player)){
            $form->addButton("Изменить точку появления", action: function (Player $player) use ($currentIsland) {
                $currentIsland->getWorld()->setSpawnLocation($player->getLocation());
                $player->sendMessage(TextFormat::YELLOW . "Вы успешно изменили точку появления в мире!");
            });
        }

        $form->sendToPlayer($player);
    }

    public function sendSettingsForm(Player $player): void {
        $island = $this->main->getIslandManager()->getIsland($this->islandManager->getPlayerId($player));
        $form = new CustomForm(function (Player $player, ?array $data) use ($island) {
            if($data === null) return;

            $password = trim($data['password']);
            if(strlen($password) > 0) $island->updatePassword($password);
            $island->setCanPvp($data['pvp']);

            $island->saveConf();
            $player->sendMessage(TextFormat::YELLOW . "Все настройки были успешно сохранены!");
        });

        $form->addLabel("Установите пароль, который вы хотите использовать для своего острова. Если вы хотите пригласить друзей, то ваши друзья должны знать пароль от острова. Оставьте поле пустым, если не хотите его изменять.");
        $form->addToggle("PvP режим", $island->canPvp(), key: "pvp");
        $form->addInput("Новый пароль", key: "password");

        $form->sendToPlayer($player);
    }

    public function sendConnectionForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if($data === null) return;

            $id = (int) trim($data['id']);
            $password = trim($data['password']);

            if($id < 1){
                $player->sendMessage(TextFormat::RED . "Введен неверный id мира.");
                return;
            }

            $island = $this->islandManager->getIsland($id);
            if(!$island->isGenerated()){
                $player->sendMessage(TextFormat::RED . "Введен неверный id мира.");
                return;
            }

            $island->preload();
            if(!$island->comparePassword($password)){
                $player->sendMessage(TextFormat::RED . "Введен неверный пароль от мира.");
                return;
            }

            $island->onJoin($player);
        });

        $form->setTitle("Присоедениться к игре");
        $form->addLabel("Узнайте у друга данные для подклюиения к миру. Вы таже можете играть в мире друга, даже если он не в сети.");
        $form->addInput("ID мира", key: "id");
        $form->addInput("Пароль мира", key: "password");

        $form->sendToPlayer($player);
    }

    public function sendConfirmationForm(Player $player, Island $island){
        $form = new ModalForm(function (Player $player, bool $result) use ($island) {
            if(!$result){
                $this->sendMainForm($player);
                return;
            }

            $this->generate($player, $island);
        });

        $form->setTitle("Новая игра");
        $form->setContent("У вас уже присутствует мир. Подтвердите то, что вы точно хотите перегенерировать его. Весь процесс будет сброшен и восстановить его будет невозможно!");
        $form->setPositiveButton("§l§4Начать новую игру");
        $form->setNegativeButton("Отмена");
        $form->sendToPlayer($player);
    }

    private function generate(Player $player, Island $island): void {
        $island->generate();
        $island->onJoin($player);

        $inv = $player->getInventory();
        foreach ([
                     VanillaItems::LAVA_BUCKET(),
                     VanillaItems::WATER_BUCKET(),
                 ] as $item) $inv->addItem($item);
    }

}