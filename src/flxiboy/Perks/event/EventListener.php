<?php

namespace flxiboy\Perks\event;

use pocketmine\event\player\{
    PlayerJoinEvent,
    PlayerExhaustEvent,
    PlayerDeathEvent,
    PlayerJumpEvent,
    PlayerRespawnEvent
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
use flxiboy\Perks\event\addXP;
use pocketmine\item\Item;

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
     * @var array $playerjump
     */
    public $playerjump = [];
    /**
     * @var array $playerjumpdamage
     */
    public $playerjumpdamage = [];
    /**
     * @var array $playerxp
     */
    public $playerxp = [];

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
        if (!$players->exists("keep-xp")) {
            foreach (["keep-xp", "double-jump", "auto-smelting"] as $name) {
                $players->set($name, false);
                $players->set($name . "-buy", false);
            }
            $players->save();
        }
        foreach (["speed", "jump", "haste", "night-vision", "fast-regeneration", "strength", "no-firedamage", "water-breathing", "invisibility"] as $name) {
            foreach ($config->getNested("perk.order") as $enable) {
                if ($enable == $name) {
                    $effect = $api->getPerkEffect($player, $name, "normal");
                    if ($players->get($name) == false and $effect !== null and $player->hasEffect($effect)) {
                        $player->removeEffect($effect);
                    }
                    if ($players->get($name) == true and $effect == null and !$player->hasEffect($effect)) {
                        $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 0, false)); 
                    }
                }
            }
        }
        if ($players->get("fly") == true and $player->isFlying(false)) {
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
        $block = $event->getBlock();
        $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        if ($players->get("dopple-xp") == true) {
            $event->setXpDropAmount($event->getXpDropAmount() * 2);
        }
        if ($config->getNested("settings.auto-smelting.enable") == true and $players->get("auto-smelting") == true) {
            if (in_array($block->getId(), [14, 15]) and $eco->myMoney($player) >= $config->getNested("settings.auto-smelting.price") and in_array($player->getGamemode(), [0, 2])) {
                $drops = [];
                if ($block->getId() == 14) {
                    $drops[] =  new Item(Item::GOLD_INGOT);
                } elseif ($block->getId() == 15) {
                    $drops[] =  new Item(Item::IRON_INGOT);
                }
                $event->setDrops($drops);
                $eco->reduceMoney($player, $config->getNested("settings.auto-smelting.price"));
            }
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
        if ($players->get("keep-xp") == true) {
            $this->playerxp[$player->getName()] = $player->getCurrentTotalXp();
            $event->setXpDropAmount(0);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onRespawn(PlayerRespawnEvent $event)
    {
        $player = $event->getPlayer();
        if (isset($this->playerxp[$player->getName()])) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new addXP($this, $player), 20 / 2);
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
        if (in_array($player->getName(), $this->playerjumpdamage)) {
            $event->setCancelled();
            unset($this->playerjumpdamage[array_search($player->getName(), $this->playerjumpdamage)]);
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onJump(PlayerJumpEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        if ($players->get("double-jump") == true) {
            if (isset($this->playerjump[$player->getName()])) {
                $this->playerjump[$player->getName()]++;
                if ($this->playerjump[$player->getName()] == 2) {
                    $player->knockBack($player, 0, $player->getDirectionVector()->getX(), $player->getDirectionVector()->getZ(), $config->getNested("settings.double-jump.strength"));
                    unset($this->playerjump[$player->getName()]);
                    if ($config->getNested("settings.double-jump.falldamage") == true) {
                        $this->playerjumpdamage[] = $player->getName();
                    }
                }
            } else {
                $this->playerjump[$player->getName()] = 1;
            }
        }
    }
}