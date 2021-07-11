<?php

namespace flxiboy\Perks\event;

use pocketmine\event\player\{
    PlayerQuitEvent,
    PlayerJoinEvent,
    PlayerExhaustEvent,
    PlayerDeathEvent
};
use pocketmine\entity\{
    EffectInstance,
    Effect
};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;
use pocketmine\event\block\BlockBreakEvent;

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
        if (!$players->exists("strength")) {
            if (!$players->exists("speed")) {
                $players->set("speed", false);
                $players->set("speed-buy", false);
                $players->set("jump", false);
                $players->set("jump-buy", false);
                $players->set("haste", false);
                $players->set("haste-buy", false);
                $players->set("night-vision", false);
                $players->set("night-vision-buy", false);
                $players->set("no-hunger", false);
                $players->set("no-hunger-buy", false);
                $players->set("no-falldamage", false);
                $players->set("no-falldamage-buy", false);
                $players->set("fast-regeneration", false);
                $players->set("fast-regeneration-buy", false);
                $players->set("keep-inventory", false);
                $players->set("keep-inventory-buy", false);
                $players->set("dopple-xp", false);
                $players->set("dopple-xp-buy", false);
            }
            $players->set("strength", false);
            $players->set("strength-buy", false);
            $players->set("no-firedamage", false);
            $players->set("no-firedamage-buy", false);
            $players->set("fly", false);
            $players->set("fly-buy", false);
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
        if ($players->get("strength") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::STRENGTH), 107374182, 1, false));
        }
        if ($players->get("no-firedamage") == true) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 107374182, 1, false));
        }
        if ($players->get("fly") == true) {
            $player->setFlying(true);
            $player->setAllowFlight(true);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) 
    {
        $player = $event->getPlayer();
        $player->removeAllEffects();
        $player->setFlying(false);
        $player->setAllowFlight(false);
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
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if ($players->get("dopple-xp") == true) {
            $event->setXpDropAmount($event->getXpDropAmount() * 2);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onDeath(PlayerDeathEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if ($players->get("keep-inventory") == true) {
            $event->setKeepInventory();
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
