<?php
declare(strict_types=1);
namespace flxiboy\Perks\task;

use pocketmine\scheduler\Task;
use flxiboy\Perks\api\API;
use flxiboy\Perks\Main;
use pocketmine\Server;

/**
 * Class PerkCheckTask
 * @package flxiboy\Perks\task
 */
class PerkCheckTask extends Task
{

    /**
     * @return void
     */
    public function onRun(): void
    {
        $config = Main::getInstance()->getConfig();
        if ($config->getNested("settings.perk-time.enable") == true) {
            $api = new API();
            $date = new \DateTime("now");
            $datas = explode(":", $date->format("Y:m:d:H:i"));
            $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $players = Main::getInstance()->getPlayers($player->getName());
                foreach (Main::getInstance()->perklist as $check) {
                    $effect = $api->getPerkEffect($check);
                    if ($players->exists($check . "-buy-count") && $data >= $players->get($check . "-buy-count")) {
                        $players->set($check, false);
                        $players->set($check . "-buy", false);
                        $players->remove($check . "-buy-count");
                        $players->save();;
                        $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("close-time", ["%perk%" => $api->getLanguage($check . "-msg")]));
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
