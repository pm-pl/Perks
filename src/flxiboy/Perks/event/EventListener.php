<?php

namespace flxiboy\Perks\event;

use pocketmine\event\player\{
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
use flxiboy\Perks\api\API;

/**
 * Class EventListener
 * @package flxiboy\Perks\event
 */
class EventListener implements Listener 
{

    /**
     * @var $plugin
     */
    public $plugin;

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
        $api = new API($this->plugin, $player);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if (!$players->exists("speed")) {
            foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility"] as $name) {
                $players->set($name, false);
                $players->set($name . "-buy", false);
            }
            $players->save();
        }
        foreach (["speed", "jump", "haste", "night-vision", "fast-regeneration", "strength", "no-firedamage", "water-breathing", "invisibility"] as $name) {
            foreach ($config->getNested("perk.order") as $enable) {
                if ($enable == $name) {
                    $effect = $api->getPerkEffect($player, $name, "normal");
                    if ($players->get($name) == false and $effect !== null) {
                        if ($player->hasEffect($effect)) {
                            $player->removeEffect($effect);
                        }
                    }
                    if ($players->get($name) == true and $effect == null) {
                        $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 0, false)); 
                    }
                }
            }
        }
        if ($players->get("fly") == true and $player->isFlying(false)) {
            $player->setFlying(true);
            $player->setAllowFlight(true);
        }
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
            $event->setKeepInventory(true);
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