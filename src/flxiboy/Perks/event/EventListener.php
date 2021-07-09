<?php

namespace flxiboy\Perks\event;

use pocketmine\event\player\{
    PlayerQuitEvent,
    PlayerJoinEvent,
    PlayerExhaustEvent
};
use pocketmine\entity\{
    EffectInstance,
    Effect
};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;

/**
 * Class EventListener
 * @package flxiboy\Perks\event
 */
class EventListener implements Listener 
{

    /**
	 * Listener constructor.
	 *
	 * @param Main $plugin
	 */
    public function __construct(Main $plugin) 
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if (!$player->hasPlayedBefore()) {
            $players->set("speed", false);
            $players->set("jump", false);
            $players->set("haste", false);
            $players->set("night-vision", false);
            $players->set("no-hunger", false);
            $players->set("no-falldamage", false);
            $players->set("fast-regeneration", false);
            $players->save();
        }
        if ($players->get("speed") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 107374182, 1, false));
        }
        if ($players->get("jump") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 107374182, 1, false));
        }
        if ($players->get("haste") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::HASTE), 107374182, 1, false));
        }
        if ($players->get("night-vision") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 107374182, 1, false));
        }
        if ($players->get("fast-regeneration") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 107374182, 1, false));
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit (PlayerQuitEvent $event) 
    {
        $player = $event->getPlayer();
        $player->removeAllEffects();
    }

    /**
     * @param PlayerExHaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if ($players->get("no-hunger") == true) {
            $player->setFood(20);
        }
    }
    
    /**
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event) 
    {
        $player = $event->getEntity();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if($event->getCause() == EntityDamageEvent::CAUSE_FALL and $players->get("no-falldamage") == true) {
            $event->setCancelled();
        }
    }
}