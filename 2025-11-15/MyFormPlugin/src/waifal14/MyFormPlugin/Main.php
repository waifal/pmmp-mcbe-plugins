<?php

namespace waifal14\MyFormPlugin;

use jojoe77777\FormAPI\SimpleForm;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\console\ConsoleCommandSender;

class Main extends PluginBase {
    private Config $config;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->getLogger()->info(TextFormat::GREEN . "MyFormPlugin enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch(strtolower($command->getName())) {
            case "myform":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "Only players can use this command.");
                    return true;
                }
                $this->sendForm($sender);
                return true;

            case "myformreload":
                $this->reloadConfig();
                $this->config = $this->getConfig();
                $sender->sendMessage(TextFormat::GREEN . "MyFormPlugin config reloaded!");
                return true;
        }
        return false;
    }


    private function sendForm(Player $player): void {
        $this->reloadConfig(); // ensure latest config is loaded
        $buttons = $this->getConfig()->get("buttons", []);

        if(!is_array($buttons)) {
            $this->getLogger()->warning("Invalid buttons config! Check config.yml");
            return;
        }

        // $this->getLogger()->info("Loaded buttons: " . json_encode($buttons));

        $form = new SimpleForm(function(Player $player, $data) use ($buttons) {
            if($data === null) return;

            if(isset($buttons[$data])) {
                $button = $buttons[$data];

                if(isset($button["message"])) {
                    $message = str_replace("%player%", $player->getName(), $button["message"]);
                    $player->sendMessage($message);
                }

                if(isset($button["command"])) {
                    $command = str_replace("%player%", $player->getName(), $button["command"]);
                    $runAs = strtolower($button["run_as"] ?? "player");

                    $sender = $runAs === "console"
                        ? new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage())
                        : $player;

                    $this->getServer()->dispatchCommand($sender, $command);
                }
            }
        });

        $form->setTitle($this->getConfig()->get("title", "My Form"));
        $form->setContent($this->getConfig()->get("content", "Choose an option"));

        // $this->getLogger()->info("Buttons loaded: " . json_encode($buttons));

        foreach($buttons as $button) {
            $form->addButton($button["label"] ?? "Button");
        }

        $player->sendForm($form);
    }
}

