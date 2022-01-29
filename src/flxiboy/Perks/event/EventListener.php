<?php
declare(strict_types=1);
namespace flxiboy\Perks\event;

use pocketmine\event\player\{
    PlayerJoinEvent,
    PlayerExhaustEvent,
    PlayerDeathEvent,
    PlayerJumpEvent,
    PlayerRespawnEvent
};
use pocketmine\event\entity\{
    EntityDamageEvent,
    EntityTeleportEvent
};
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use flxiboy\Perks\api\API;
use flxiboy\Perks\Main;

/**
 * Class EventListener
 * @package flxiboy\Perks\event
 */
class EventListener implements Listener 
{
    /**
     * @var array $playerjump
     */
    public array $playerjump = [];
    /**
     * @var array $playerjumpdamage
     */
    public array $playerjumpdamage = [];
    /**
     * @var array $playerxp
     */
    public array $playerxp = [];

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) 
    {
        $player = $event->getPlayer();
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $players = Main::getInstance()->getPlayers($player->getName());
        if (!$players->exists("speed")) {
            foreach (Main::getInstance()->perklist as $name) {
                $players->set($name, false);
                $players->set($name . "-buy", false);
            }
        }
        if ($config->getNested("settings.economy-api") == true && $config->getNested("settings.perk-time.enable") == true) {
            $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            $date = new \DateTime("now");
            $datas = explode(":", $date->format("Y:m:d:H:i"));
            $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
            foreach (Main::getInstance()->perklist as $check) {
                $effect = $api->getPerkEffect($check);
                if ($eco->myMoney($player) >= $config->getNested("perk." . $check . ".price") || $players->exists($check . "-buy-count")) {
                    if ($players->exists($check . "-buy-count") && $data >= $players->get($check . "-buy-count")) {
                        $players->set($check, false);
                        $players->set($check . "-buy", false);
                        $players->remove($check . "-buy-count");
                        $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("close-time", ["%perk%" => $api->getLanguage($check . "-msg")]));
                        if ($effect !== null) {
                            $player->getEffects()->clear();
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
        $players = Main::getInstance()->getPlayers($player->getName());
        if ($players->get("no-hunger") == true && $player->getHungerManager()->getFood() != 20) {
            $player->getHungerManager()->setFood(20);
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
        $players = Main::getInstance()->getPlayers($player->getName());
        $config = Main::getInstance()->getConfig();
        if ($players->get("dopple-xp") == true) {
            $event->setXpDropAmount($event->getXpDropAmount() * 2);
        }
        if ($config->getNested("settings.auto-smelting.enable") == true && $players->get("auto-smelting") == true) {
            if (in_array($block->getId(), [14, 15]) && $eco->myMoney($player) >= $config->getNested("settings.auto-smelting.price") && in_array($player->getGamemode(), [0, 2])) {
                $drops = [];
                if ($block->getId() == 14) {
                    $drops[] =  VanillaItems::GOLD_INGOT();
                } elseif ($block->getId() == 15) {
                    $drops[] =  VanillaItems::IRON_INGOT();
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
        $players = Main::getInstance()->getPlayers($player->getName());
        if ($players->get("keep-inventory") == true) {
            $event->setKeepInventory(true);
        }
        if ($players->get("keep-xp") == true) {
            $this->playerxp[$player->getName()] = $player->getXpDropAmount();
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
        if ($player instanceof Player && $event->getCause() == EntityDamageEvent::CAUSE_FALL) {
            $players = Main::getInstance()->getPlayers($player->getName());
            if ($players->get("no-falldamage") == true) {
                $event->cancel();
            }
            if (in_array($player->getName(), $this->playerjumpdamage)) {
                $event->cancel();
                unset($this->playerjumpdamage[array_search($player->getName(), $this->playerjumpdamage)]);
            }
        }
    }

    /**
     * @param PlayerJumpEvent $event
     */
    public function onJump(PlayerJumpEvent $event) 
    {
        $player = $event->getPlayer();
        $players = Main::getInstance()->getPlayers($player->getName());
        $config = Main::getInstance()->getConfig();
        if ($players->get("double-jump") == true) {
            if (isset($this->playerjump[$player->getName()])) {
                $this->playerjump[$player->getName()]++;
                if ($this->playerjump[$player->getName()] == 2) {
                    $player->knockBack($player->getDirectionVector()->getX(), $player->getDirectionVector()->getZ(), (float)$config->getNested("settings.double-jump.strength"));
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
     * @param EntityTeleportEvent $event
     * @return bool
     */
    public function onWorldChange(EntityTeleportEvent $event): bool
    {
        $player = $event->getEntity();
        $api = new API();
        $config = Main::getInstance()->getConfig();
        if ($player instanceof Player && $config->getNested("settings.per-world.enable") == true) {
            $players = Main::getInstance()->getPlayers($player->getName());
            foreach ($config->getNested("settings.per-world.worlds") as $level) {
                if ($event->getTo() !== $level) {
                    $player->getEffects()->clear();
                    foreach (Main::getInstance()->perklist as $check) {
                        $players->set($check, false);
                    }
                    $players->save();
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("perks-disable"));
                    return true;
                }
            }
        }
        return true;
    }
}
