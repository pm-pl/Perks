<?php

namespace flxiboy\Perks\event;

use pocketmine\scheduler\Task;
use flxiboy\Perks\event\EventListener;
use pocketmine\Player;

/**
 * Class removeDoubleJump
 * @package flxiboy\Perks\event
 */
class removeDoubleJump extends Task
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
        if (isset($this->plugin->playerjump[$this->player->getName()])) {
            if ($this->plugin->playerjump[$this->player->getName()] == 1) {
                unset($this->plugin->playerjump[$this->player->getName()]);
            }
        }
    }
}