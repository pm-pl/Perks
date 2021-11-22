<?php

namespace flxiboy\Perks\task;

use flxiboy\Perks\api\API;
use flxiboy\Perks\Main;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;

/**
 * Class PerkCheckTask
 * @package flxiboy\Perks\task
 */
class PerkCheckTask extends Task
{

    public function onRun(): void
    {
        $config = Main::getInstance()->getConfig();
        if ($config->getNested("settings.perk-time.enable") == true) {
            $api = new API();
            $date = new \DateTime("now");
            $datas = explode(":", $date->format("Y:m:d:H:i"));
            $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
                foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility", "keep-xp", "double-jump", "auto-smelting"] as $check) {
                    $effect = $api->getPerkEffect($player, $check);
                    if ($players->exists($check . "-buy-count") && $data >= $players->get($check . "-buy-count")) {
                        $players->set($check, false);
                        $players->set($check . "-buy", false);
                        $players->remove($check . "-buy-count");
                        $players->save();
                        $msg = $api->getLanguage($player, "close-time");
                        $msg = str_replace("%perk%", $api->getLanguage($player, $check . "-msg"), $msg);
                        $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                        if ($effect !== null) {
                            $player->getEffects()->remove($effect);
                        }
                    }
                }
            }
        } else {
            $this->getHandler()->cancel();
        }
    }
}
