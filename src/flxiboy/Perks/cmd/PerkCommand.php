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
     * @var $plugin
     */
    public $plugin;

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
        $api = new API($this->plugin, $player);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);

        if (!$player instanceof Player) {
            $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "no-ingame"));
            return;
        }

        if ($config->getNested("command.permission") !== false and !$player->hasPermission($config->getNested("command.permission"))) {
            $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "no-perms"));
            return;
        }

        if (isset($args[0])) {
            if ($player->hasPermission($config->getNested("command.reload.perms"))) {
                if ($args[0] == $api->getLanguage($player, "reload-cmd")) {
                    $this->plugin->loadFiles();
                    $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "reload-success"));
                } else {
                    $player->sendMessage($api->getLanguage($player, "prefix") . $config->getNested("command.reload.usage"));
                }
            } else {
                $perk = new PerkForm($this->plugin, $player);
                $perk->getPerks($player);
            }
        } else {
            $perk = new PerkForm($this->plugin, $player);
            $perk->getPerks($player);
        }
        return true;
    }
}