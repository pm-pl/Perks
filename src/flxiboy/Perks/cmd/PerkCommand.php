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

/**
 * Class PerkCommand
 * @package flxiboy\Perks\cmd
 */
class PerkCommand extends PluginCommand 
{

    /**
	 * Commands constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) 
    {
        $this->plugin = $plugin;
        $config = new Config($plugin->getDataFolder() . "config.yml", Config::YAML);
        parent::__construct($config->getNested("command.cmd"), $plugin);
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
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);

        if (!$player instanceof Player) {
            $player->sendMessage($config->getNested("message.prefix") . $config->getNested("message.no-ingame"));
            return;
        }
        if ($config->getNested("command.permission") !== false and !$player->hasPermission($config->getNested("command.permission"))) {
            $player->sendMessage($config->getNested("message.prefix") . $config->getNested("message.no-perms"));
            return;
        }

        $perk = new PerkForm($this->plugin, $player);
        $perk->getPerks($player);
        return true;
    }
}