<?php

namespace flxiboy\Perks\event;

use pocketmine\event\player\{
    PlayerJoinEvent,
    PlayerExhaustEvent,
    PlayerDeathEvent,
    PlayerJumpEvent,
    PlayerRespawnEvent
};
use flxiboy\Perks\event\{
    addXP,
    removeDoubleJump
};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;
use pocketmine\event\block\BlockBreakEvent;
use flxiboy\Perks\api\API;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityLevelChangeEvent;

/**
 * Class EventListener
 * @package flxiboy\Perks\event
 */
class EventListener implements Listener 
{

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
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) 
    {
        $player = $event->getPlayer();
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if (!$players->exists("speed")) {
            foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility"] as $name) {
                $players->set($name, false);
                $players->set($name . "-buy", false);
            }
        }
        if (!$players->exists("keep-xp")) {
            foreach (["keep-xp", "double-jump", "auto-smelting"] as $name) {
                $players->set($name, false);
                $players->set($name . "-buy", false);
            }
        }
        if ($config->getNested("settings.economy-api") == true && $config->getNested("settings.perk-time.enable") == true) {
            $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            $date = new \DateTime("now");
            $datas = explode(":", $date->format("Y:m:d:H:i"));
            $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
            if ($eco->isEnabled()) {
                foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility", "keep-xp", "double-jump", "auto-smelting"] as $check) {
                    $effect = $api->getPerkEffect($player, $check);
                    if ($eco->myMoney($player) >= $config->getNested("perk." . $check . ".price") || $players->exists($check . "-buy-count")) {
                        if ($players->exists($check . "-buy-count") && $data >= $players->get($check . "-buy-count")) {
                            $players->set($check, false);
                            $players->set($check . "-buy", false);
                            $players->remove($check . "-buy-count");
                            $msg = $api->getLanguage($player, "close-time");
                            $msg = str_replace("%perk%", $api->getLanguage($player, $check . "-msg"), $msg);
                            $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                            if ($effect !== null) {
                                $player->removeEffect($effect);
                            }
                        }
                    }
                }
            }
        }
        $players->save();
    }

    /**
     * @param PlayerExHaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        if ($players->get("no-hunger") == true && $player->getFood() != 20) {
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
        $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $config = Main::getInstance()->getConfig();
        if ($players->get("dopple-xp") == true) {
            $event->setXpDropAmount($event->getXpDropAmount() * 2);
        }
        if ($eco->isEnabled() && $config->getNested("settings.auto-smelting.enable") == true && $players->get("auto-smelting") == true) {
            if (in_array($block->getId(), [14, 15]) && $eco->myMoney($player) >= $config->getNested("settings.auto-smelting.price") && in_array($player->getGamemode(), [0, 2])) {
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
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
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
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new addXP($this, $player), 20 / 2);
        }
    }
    
    /**
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event) 
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
            if($event->getCause() == EntityDamageEvent::CAUSE_FALL) {
                if ($players->get("no-falldamage") == true) {
                    $event->setCancelled();
                }
                if (in_array($player->getName(), $this->playerjumpdamage)) {
                    $event->setCancelled();
                    unset($this->playerjumpdamage[array_search($player->getName(), $this->playerjumpdamage)]);
                }
            }
        }
    }

    /**
     * @param PlayerJumpEvent $event
     */
    public function onJump(PlayerJumpEvent $event) 
    {
        $player = $event->getPlayer();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $config = Main::getInstance()->getConfig();
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
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new removeDoubleJump($this, $player), 20);
            }
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     */
    public function onWorldChange(EntityLevelChangeEvent $event) 
    {
    	$player = $event->getEntity();
        $api = new API();
        $config = Main::getInstance()->getConfig();
    	if($player instanceof Player && $config->getNested("settings.per-world.enable") == true) {
            $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
            $block = false;
            foreach ($config->getNested("settings.per-world.worlds") as $level) {
                if ($event->getTarget() !== $level) {
                    $block = true;
                }
            }
            if ($block == true) {
                $player->removeAllEffects();
                foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility", "keep-xp", "double-jump", "auto-smelting"] as $check) {
                    $players->set($check, false);
                }
                $players->save();
                $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "perks-disable"));
            }
    	}
    }
}
