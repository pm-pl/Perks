<?php

namespace flxiboy\Perks\api;

use pocketmine\Player;
use pocketmine\entity\{
    EffectInstance,
    Effect
};
use pocketmine\utils\Config;
use flxiboy\Perks\Main;
use flxiboy\Perks\form\PerkForm;

/**
 * Class API
 * @package flxiboy\Perks\api
 */
class API 
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
	 * @param Player $player
     * @param string $check
	 */
    public function getCheckPerk(Player $player, string $check) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $effect = $this->getPerkEffect($player, $check, "normal");
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly"];
        if ($config->getNested("command.economy-api") == true) {
            $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            if ($players->get("$check-buy") == true) {
                $this->plugin->playernewperk[] = $player->getName();
            } else {
                if ($eco->myMoney($player) >= $config->getNested("perk.$check.price")) {
                    $players->set("$check", false);
                    $players->set("$check-buy", true);
                    $buy = $config->getNested("message.buy");
                    $buy = str_replace("%perk%", $config->getNested("perk.$check.msg"), $buy);
                    $buy = str_replace("%money%", $config->getNested("perk.$check.price"), $buy);
                    $player->sendMessage($config->getNested("message.prefix") . $buy);
                    $eco->reduceMoney($player, $config->getNested("perk.$check.price"));
                } else {
                    $msg = $config->getNested("message.no-money");
                    $msg = str_replace("%need-money%", $config->getNested("perk.$check.price") - $eco->myMoney($player), $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                }
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                        $this->plugin->playernewperk[] = $player->getName();
                } else {
                    if ($player->hasPermission($config->getNested("perk.$check.perms"))) {
                        $this->plugin->playernewperk[] = $player->getName();
                    } else {
                        $player->sendMessage($config->getNested("message.prefix") . $config->getNested("message.no-perms"));
                    }
                }
            } else {
                $this->plugin->playernewperk[] = $player->getName();
            }
        }
        if (in_array($player->getName(), $this->plugin->playernewperk)) {
            unset($this->plugin->playernewperk[array_search($player->getName(), $this->plugin->playernewperk)]);
            if ($players->get($check) == true) {
                if (in_array($check, $block)) {
                    $players->set($check, false);
                    if ($check == "fly") {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                    }
                    $msg = $config->getNested("message.mode.disable");
                    $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                } else {
                    $this->plugin->playernewperkname[$player->getName()] = $check;
                    $perk = new PerkForm($this->plugin, $player);
                    $perk->getPerkSwitch($player, $check, $effect);
                }
            } else {
                $players->set($check, true);
                if (!in_array($check, $block) and $effect !== null) {
                    $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 0, false));
                }
                if ($check == "fly") {
                    $player->setFlying(true);
                    $player->setAllowFlight(true);
                }
                $msg = $config->getNested("message.mode.enable");
                $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                $player->sendMessage($config->getNested("message.prefix") . $msg);
            }
        }
        $players->save();
        return true;
    }

    /**
	 * @param Player $player
     * @param string $check
	 */
    public function getStatus(Player $player, string $check) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $effect = $this->getPerkEffect($player, $check, "normal");
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly"];
        if ($players->get($check) == true and $effect !== null) {
            if (!$player->hasEffect($effect) and !in_array($check, $block)) {
                $players->set($check, false);
                $players->save();
            }
        }
        if ($config->getNested("command.economy-api") == true) {
            if ($players->get("$check-buy") == true) {
                if ($players->get($check) == true) {
                    $speedcheck = $config->getNested("message.button.enable");
                } else {
                    $speedcheck = $config->getNested("message.button.disable");
                }
            } else {
                $price = $config->getNested("message.button.buy");
                $price = str_replace("%money%", $config->getNested("perk.$check.price"), $price);
                $speedcheck = $price;
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                    if ($players->get($check) == true) {
                        $speedcheck = $config->getNested("message.button.enable");
                    } else {
                        $speedcheck = $config->getNested("message.button.disable");
                    }
                } else {
                    if ($player->hasPermission($config->getNested("perk.$check.perms"))) {
                        if ($players->get($check) == true) {
                            $speedcheck = $config->getNested("message.button.enable");
                        } else {
                            $speedcheck = $config->getNested("message.button.disable");
                        } 
                    } else {
                        $speedcheck = $config->getNested("message.button.no-perms");
                    }
                }
            } else {
                $speedcheck = $config->getNested("message.button.disable");
            }
        }
        return $speedcheck;
    }

    /**
     * @param Player $player
     * @param string $check
     * @param string $type
     */
    public function getPerkEffect(Player $player, string $check, string $type) 
    {
        $effect = null;
        if ($type == "normal") {
            if ($check == "speed") { 
                $effect = Effect::SPEED;
            } elseif ($check == "jump") { 
                $effect = Effect::JUMP_BOOST;
            } elseif ($check == "haste") { 
                $effect = Effect::HASTE;
            } elseif ($check == "night-vision") { 
                $effect = Effect::NIGHT_VISION;
            } elseif ($check == "fast-regeneration") { 
                $effect = Effect::REGENERATION;
            } elseif ($check == "strength") { 
                $effect = Effect::STRENGTH;
            } elseif ($check == "no-firedamage") { 
                $effect = Effect::FIRE_RESISTANCE;
            } elseif ($check == "water-breathing") {
                $effect = Effect::WATER_BREATHING;
            } elseif ($check == "invisibility") {
                $effect = Effect::INVISIBILITY;
            }
        } elseif ($type == "main") {
            if ($this->plugin->playernewperkname[$player->getName()] == "speed") { 
                $effect = Effect::SPEED;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "jump") { 
                $effect = Effect::JUMP_BOOST;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "haste") { 
                $effect = Effect::HASTE;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "night-vision") { 
                $effect = Effect::NIGHT_VISION;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "fast-regeneration") { 
                $effect = Effect::REGENERATION;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "strength") { 
                $effect = Effect::STRENGTH;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "no-firedamage") { 
                $effect = Effect::FIRE_RESISTANCE;
            } elseif ($this->plugin->playernewperkname[$player->getName()]  == "water-breathing") {
                $effect = Effect::WATER_BREATHING;
            } elseif ($this->plugin->playernewperkname[$player->getName()] == "invisibility") {
                $effect = Effect::INVISIBILITY;
            }
        }
        return $effect;
    }
}