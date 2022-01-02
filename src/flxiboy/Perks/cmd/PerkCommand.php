<?php
declare(strict_types=1);
namespace flxiboy\Perks\cmd;

use pocketmine\command\{
    Command,
    CommandSender
};
use flxiboy\Perks\form\PerkForm;
use pocketmine\player\Player;
use flxiboy\Perks\api\API;
use flxiboy\Perks\Main;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

/**
 * Class PerkCommand
 * @package flxiboy\Perks\cmd
 */
class PerkCommand extends Command implements PluginOwned
{

    /**
     * @return Plugin
     */
    public function getOwningPlugin(): Plugin
    {
        return Main::getInstance();
    }

    /**
	 * Commands constructor.
	 */
	public function __construct() 
    {
        $config = Main::getInstance()->getConfig();
        parent::__construct($config->getNested("command.cmd"), $config->getNested("command.desc"), $config->getNested("command.usage"), $config->getNested("command.aliases"));
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

        if ($config->getNested("command.permission") !== false && !$player->hasPermission("Perks.command")) {
            $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("no-perms"));
            return;
        }

        if (!$player instanceof Player) {
            $player->sendMessage("§cPlease go InGame for this command.");
            return;
        }

        if ($config->getNested("settings.per-world.enable") == true) {
            foreach ($config->getNested("settings.per-world.worlds") as $level) {
                if ($player->getWorld()->getFolderName() !== $level) {
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("not-enable"));
                    return true;
                }
            }
        }

        $perk = new PerkForm();
        $perk->getPerks($player);
        return true;
    }
}