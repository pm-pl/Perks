<?php
declare(strict_types=1);
namespace flxiboy\Perks\event;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;

/**
 * Class removeDoubleJump
 * @package flxiboy\Perks\event
 */
class removeDoubleJump extends Task
{
    /**
     * @var EventListener
     */
   public EventListener $plugin;
    /**
     * @var Player
     */
   public Player $player;

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
     * @return void
     */
    public function onRun(): void
    {
        if (isset($this->plugin->playerjump[$this->player->getName()]) && $this->plugin->playerjump[$this->player->getName()] == 1) {
            unset($this->plugin->playerjump[$this->player->getName()]);
        }
    }
}