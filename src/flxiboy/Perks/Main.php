<?php

namespace flxiboy\Perks;

use pocketmine\plugin\PluginBase;
use flxiboy\Perks\event\EventListener;
use flxiboy\Perks\cmd\PerkCommand;

/**
 * Class Main
 * @package flxiboy\Perks
 */
class Main extends PluginBase 
{

    /**
     * @var array
     */
    public $playernewperk = [];
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
        $this->saveResource("config.yml");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("Perks", new PerkCommand($this));
    }
}