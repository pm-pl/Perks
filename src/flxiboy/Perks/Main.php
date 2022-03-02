<?php
declare(strict_types=1);
namespace flxiboy\Perks;

use flxiboy\Perks\provider\{
    BedrockEconomyProvider,
    EconomyAPIProvider
};
use pocketmine\permission\DefaultPermissions;
use flxiboy\Perks\event\EventListener;
use pocketmine\permission\Permission;
use flxiboy\Perks\task\PerkCheckTask;
use flxiboy\Perks\cmd\PerkCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

/**
 * Class Main
 * @package flxiboy\Perks
 */
class Main extends PluginBase
{
    /**
     * @var Main
     */
    public static Main $instance;
    /**
     * @var array|string[]
     */
    public array $perklist = ["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "double-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility", "keep-xp", "double-jump", "auto-smelting"];

    /**
     * Enable function: registering Command and Event
     */
    public function onEnable(): void
    {
        self::$instance = $this;
        @mkdir($this->getDataFolder() . "players/");
        $this->loadFiles();
        $config = $this->getConfig();

        if (!file_exists($this->getDataFolder() . "lang/" . $config->get("language") . ".yml")) {
            $this->getLogger()->warning("§cThis language was not found. This Plugin was disable.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        } else {
            $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
            $this->getServer()->getCommandMap()->register("Perks", new PerkCommand());
        }

        # start task
        if ($config->getNested("settings.economy.enable") == true && $config->getNested("settings.perk-time.enable") == true) {
            $this->getScheduler()->scheduleRepeatingTask(new PerkCheckTask(), 20 * ($config->getNested("settings.perk-time.time-task") ? $config->getNested("settings.perk-time.time-task") : 60));
        }

        # check perms
        foreach ($this->perklist as $perks) {
            if ($config->getNested("perk.$perks.perms") !== false) {
                DefaultPermissions::registerPermission(new Permission($config->getNested("perk.$perks.perms")));
            }
        }
    }

    /**
     * @return void
     */
    public function loadFiles()
    {
        $this->saveResource("config.yml");
        $this->saveResource("lang/english.yml");
        $this->saveResource("lang/german.yml");
        $this->saveResource("lang/russian.yml");
        $this->saveResource("lang/template.yml");
        if (is_dir(Main::getInstance()->getDataFolder() . "lang")) {
            foreach (scandir(Main::getInstance()->getDataFolder() . "lang") as $langs) {
                $lang = explode(".", $langs);
                if ($lang[1] == "yml") {
                    $this->saveResource("lang/" . $lang[0] . ".yml");
                }
            }
        }
    }

    /**
     * @return self
     */
    public static function getInstance(): Main
    {
        return self::$instance;
    }

    /**
     * @param string $player
     * @return Config
     */
    public function getPlayers(string $player): Config
    {
        return new Config(Main::getInstance()->getDataFolder() . "players/" . $player . ".yml", Config::YAML);
    }

    /**
     * @return bool|EconomyAPIProvider|BedrockEconomyProvider
     */
    public function getEconomyProvider(): bool|EconomyAPIProvider|BedrockEconomyProvider
    {
        $config = $this->getConfig();

        if ($config->getNested("settings.economy.enable") == true) {
            if ($config->getNested("settings.economy.typ") == "EconomyAPI" && (Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI") && Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI")->isEnabled())) {
                return new EconomyAPIProvider();
            } elseif ($config->getNested("settings.economy.typ") == "BedrockEconomy" && (Main::getInstance()->getServer()->getPluginManager()->getPlugin("BedrockEconomy") && Main::getInstance()->getServer()->getPluginManager()->getPlugin("BedrockEconomy")->isEnabled())) {
                return new BedrockEconomyProvider();
            }
        }
        return false;
    }
}