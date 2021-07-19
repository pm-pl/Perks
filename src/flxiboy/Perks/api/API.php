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
        $playernewperk = [];
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $effect = $this->getPerkEffect($player, $check, "normal");
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        if ($config->getNested("settings.economy-api") == true) {
            $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            if ($players->get("$check-buy") == true) {
                $playernewperk[] = $player->getName();
            } else {
                if ($eco->myMoney($player) >= $config->getNested("perk.$check.price")) {
                    $players->set("$check", false);
                    $players->set("$check-buy", true);
                    $msg = $this->getLanguage($player, "buy-economyapi");
                    $msg = str_replace("%perk%", $this->getLanguage($player, "$check-msg"), $msg);
                    $msg = str_replace("%moneyp%", $config->getNested("perk.$check.price"), $msg);
                    $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                    $eco->reduceMoney($player, $config->getNested("perk.$check.price"));
                } else {
                    $msg = $this->getLanguage($player, "no-money-economyapi");
                    $msg = str_replace("%need-money%", $config->getNested("perk.$check.price") - $eco->myMoney($player), $msg);
                    $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                }
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                        $playernewperk[] = $player->getName();
                } else {
                    if ($player->hasPermission($config->getNested("perk.$check.perms"))) {
                        $playernewperk[] = $player->getName();
                    } else {
                        $player->sendMessage($this->getLanguage($player, "prefix") . $this->getLanguage($player, "no-perms"));
                    }
                }
            } else {
                $playernewperk[] = $player->getName();
            }
        }
        if (in_array($player->getName(), $playernewperk)) {
            unset($playernewperk[array_search($player->getName(), $playernewperk)]);
            if ($players->get($check) == true) {
                if (in_array($check, $block)) {
                    $players->set($check, false);
                    if ($check == "fly") {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                    }
                    $msg = $this->getLanguage($player, "disable-perk");
                    $msg = str_replace("%perk%", $this->getLanguage($player, "$check-msg"), $msg);
                    $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
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
                    $player->setAllowFlight(true);
                }
                $msg = $this->getLanguage($player, "enable-perk");
                $msg = str_replace("%perk%", $this->getLanguage($player, "$check-msg"), $msg);
                $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
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
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        if ($players->get($check) == true and $effect !== null) {
            if (!$player->hasEffect($effect) and !in_array($check, $block)) {
                $players->set($check, false);
                $players->save();
            }
        }
        if (($config->getNested("settings.economy-api") == true and $players->get($check . "-buy") == true) or ($config->getNested("perk.$check.perms") !== false and $player->hasPermission($config->getNested("perk.$check.perms"))) or ($players->get($check . "-buy") == false and $players->get($check) == true)) {
            if ($players->get($check) == false and $effect !== null) {
                if ($player->hasEffect($effect) and !in_array($check, $block)) {
                    $players->set($check, true);
                    $players->save();
                }
            }
        }
        if ($config->getNested("settings.economy-api") == true) {
            if ($players->get("$check-buy") == true) {
                if ($players->get($check) == true) {
                    $speedcheck = $this->getLanguage($player, "enable-button");
                } else {
                    $speedcheck = $this->getLanguage($player, "disable-button");
                }
            } else {
                $msg = $this->getLanguage($player, "buy-button");
                $msg = str_replace("%moneyp%", $config->getNested("perk.$check.price"), $msg);
                $speedcheck = $msg;
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                    if ($players->get($check) == true) {
                        $speedcheck = $this->getLanguage($player, "enable-button");
                    } else {
                        $speedcheck = $this->getLanguage($player, "disable-button");
                    }
                } else {
                    if ($player->hasPermission($config->getNested("perk.$check.perms"))) {
                        if ($players->get($check) == true) {
                            $speedcheck = $this->getLanguage($player, "enable-button");
                        } else {
                            $speedcheck = $this->getLanguage($player, "disable-button");
                        } 
                    } else {
                        $speedcheck = $this->getLanguage($player, "no-perms-button");
                    }
                }
            } else {
                $speedcheck = $this->getLanguage($player, "disable-button");
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

/**
 * @param Player $player
 * @param string $message
 */
    public function getLanguage(Player $player, string $message) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        if (file_exists($this->plugin->getDataFolder() . "lang/" . $config->get("language") . ".yml")) {
            $messages = new Config($this->plugin->getDataFolder() . "lang/" . $config->get("language") . ".yml", Config::YAML);
            $msg = $messages->get($message);
        } else {
            $msg = "§cThis language was not found. please change the language!";
        }
        return $msg;
    }
}