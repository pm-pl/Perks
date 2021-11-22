<?php

namespace flxiboy\Perks\cmd;

use pocketmine\command\{
    Command,
    CommandSender
};
use pocketmine\player\Player;
use flxiboy\Perks\Main;
use flxiboy\Perks\form\PerkForm;
use flxiboy\Perks\api\API;

/**
 * Class PerkCommand
 * @package flxiboy\Perks\cmd
 */
class PerkCommand extends Command
{

    /**
	 * Commands constructor.
	 */
	public function __construct() 
    {
        $config = Main::getInstance()->getConfig();
        parent::__construct($config->getNested("command.cmd"), $config->getNested("command.desc"), $config->getNested("command.usage"), [$config->getNested("command.aliases")]);
    }

    /**
     * @param CommandSender $player
     * @param string $alias
     * @param array $args
     * @return bool|void
     */
    public function execute(CommandSender $player, string $alias, array $args)
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $config->reload();

        if ($config->getNested("command.permission") !== false && !$player->hasPermission($config->getNested("command.permission"))) {
            $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "no-perms"));
            return;
        }

        if (!$player instanceof Player) {
            $player->sendMessage("§cPlease go InGame for this command.");
            return;
        }

        if ($config->getNested("settings.per-world.enable") == true) {
            foreach ($config->getNested("settings.per-world.worlds") as $level) {
                if ($player->getWorld()->getFolderName() !== $level) {
                    $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "not-enable"));
                    return true;
                }
            }
        }

        $perk = new PerkForm();
        $perk->getPerks($player);
        return true;
    }
}