<?php

namespace flxiboy\Perks\event;

use pocketmine\scheduler\Task;
use flxiboy\Perks\event\EventListener;
use pocketmine\Player;

/**
 * Class addXP
 * @package flxiboy\Perks\event
 */
class addXP extends Task
{

    /**
     * Listener constructor.
     * 
     * @param EventListener $plugin
     * @param Player $player
     */
    public function __construct(EventListener $plugin, Player $player) 
    {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) 
    {
        $this->player->addXp($this->plugin->playerxp[$this->player->getName()]);
        unset($this->plugin->playerxp[$this->player->getName()]);
    }
}