<?php

namespace flxiboy\Perks\cmd;

use pocketmine\command\{
    PluginCommand,
    CommandSender
};
use pocketmine\Player;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;
use flxiboy\Perks\form\PerkForm;
use flxiboy\Perks\api\API;

/**
 * Class PerkCommand
 * @package flxiboy\Perks\cmd
 */
class PerkCommand extends PluginCommand 
{

    /**
	 * Commands constructor.
	 */
	public function __construct() 
    {
        $config = Main::getInstance()->getConfig();
        parent::__construct($config->getNested("command.cmd"), Main::getInstance());
		$this->setAliases([$config->getNested("command.aliases")]);
		$this->setDescription($config->getNested("command.desc"));
		$this->setUsage($config->getNested("command.usage"));
    }

    /**
	 * @param CommandSender $player
	 * @param string $alias
	 * @param string[] $args
	 */
    public function execute(CommandSender $player, string $alias, array $args) 
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();

        if (!$player instanceof Player) {
            $player->sendMessage("§cPlease go InGame for this command.");
            return;
        }

        if ($config->getNested("command.permission") !== false and !$player->hasPermission($config->getNested("command.permission"))) {
            $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "no-perms"));
            return;
        }

        if (isset($args[0])) {
            if ($player->hasPermission($config->getNested("command.reload.perms"))) {
                if ($args[0] == $api->getLanguage($player, "reload-cmd")) {
                    Main::getInstance()->loadFiles();
                    if (file_exists(Main::getInstance()->getDataFolder() . "config.yml")) {
                        $config->reload();
                    }
                    if (file_exists(Main::getInstance()->getDataFolder() . "lang/" . $config->get("language") . ".yml")) {
                        $language = new Config(Main::getInstance()->getDataFolder() . "lang/" . $config->get("language") . ".yml", Config::YAML);
                        $language->reload();
                    }
                    $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "reload-success"));
                } else {
                    $player->sendMessage($api->getLanguage($player, "prefix") . $config->getNested("command.reload.usage"));
                }
            } else {
                $perk = new PerkForm();
                $perk->getPerks($player);
            }
        } else {
            $perk = new PerkForm();
            $perk->getPerks($player);
        }
        return true;
    }
}