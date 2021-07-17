<?php

namespace flxiboy\Perks;

use pocketmine\plugin\PluginBase;
use flxiboy\Perks\event\EventListener;
use flxiboy\Perks\cmd\PerkCommand;
use pocketmine\utils\Config;

/**
 * Class Main
 * @package flxiboy\Perks
 */
class Main extends PluginBase 
{

    /**
     * @var array
     */
    public $playernewperkname = [];
    
    /**
     * Enable function: registering Command and Event
     */
    public function onEnable()
    {
        @mkdir($this->getDataFolder() . "players/");
        $this->loadFiles();
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        if (!file_exists($this->getDataFolder() . "lang/" . $config->get("language") . ".yml")) {
            $this->getLogger()->warning("§cThis language was not found. This Plugin was disable.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        } else {
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
            $this->getServer()->getCommandMap()->register("Perks", new PerkCommand($this));
        }
    }

    public function loadFiles() 
    {
        $this->saveResource("config.yml");
        $this->saveResource("lang/english.yml");
        $this->saveResource("lang/german.yml");
        $this->saveResource("lang/russian.yml");
    }
}