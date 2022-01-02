<?php
declare(strict_types=1);
namespace flxiboy\Perks\event;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;

/**
 * Class addXP
 * @package flxiboy\Perks\event
 */
class addXP extends Task
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
        $this->player->getXpManager()->addXp($this->plugin->playerxp[$this->player->getName()]);
        unset($this->plugin->playerxp[$this->player->getName()]);
    }
}