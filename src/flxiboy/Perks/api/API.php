<?php

namespace flxiboy\Perks\api;

use pocketmine\entity\{
    EffectInstance,
    Effect
};
use pocketmine\Player;
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
	 * @param Player $player
     * @param string $check
	 */
    public function getCheckPerk(Player $player, string $check) 
    {
        $playernewperk = [];
        $perk = new PerkForm();
        $config = Main::getInstance()->getConfig();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $effect = $this->getPerkEffect($player, $check);
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        if ($config->getNested("settings.economy-api") == true) {
            $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            if ($config->getNested("settings.perk-time.enable") == true) {
                if ($players->get($check) == false) {
                    $date = new \DateTime('now');
                    $datas = explode(":", $date->format("Y:m:d:H:i:s"));
                    $data = ($datas[0] - 0) . ":" . ($datas[1] - 0) . ":" . ($datas[2] - 0) . ":" . ($datas[3] - 0) . ":" . ($datas[4] - 0) . ":" . ($datas[5] - 0);
                    if ($eco->myMoney($player) >= $config->getNested("perk.$check.price")) {
                        if ($players->exists("$check-buy-count")) {
                            if ($data >= $players->get("$check-buy-count")) {
                                $players->set("$check", false);
                                $players->set("$check-buy", false);
                                $players->remove("$check-buy-count");
                                $msg = $this->getLanguage($player, "close-time");
                                $msg = str_replace("%perk%", $check, $msg);
                                $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                            } else {
                                $playernewperk[] = $player->getName();
                            }
                        } else {
                            if ($config->getNested("settings.buy-confirm.enable") == true) {
                                $perk->getPerkBuyConfirm($player, $check, "time");
                            } else {
                                if (in_array($date->format("m"), [1, 3, 5, 7, 9, 11])) { $months = 32; } elseif (in_array($date->format("m"), [4, 6, 8, 10, 12])) { $months = 31; } else { $months = 29; }
                                $format = explode(":", $config->getNested("perk.$check.time"));
                                $formats = explode(":", $data);
                                $year = ($formats[0] + $format[0]);
                                $month = ($formats[1] + $format[1]);
                                $day = ($formats[2] + $format[2]);
                                $hour = ($formats[3] + $format[3]);
                                $minute = ($formats[4] + $format[4]);
                                $second = ($formats[5] + $format[5]);
                                if ($second >= 60) { $second = ($second - 61); $minute++; }
                                if ($minute >= 60) { $minute = ($minute - 61); $hour++; }
                                if ($hour >= 24) { $hour = ($hour - 25); $minute++; }
                                if ($day >= $months) { $day = ($day - $months); $month++; }
                                if ($month >= 12) { $month = ($month - 13); $year++; }
                                $players->set("$check", false);
                                $players->set("$check-buy", true);
                                $players->set("$check-buy-count", $year . ":" . $month . ":" . $day . ":" . $hour . ":" . $minute . ":0");
                                $players->save();
                                $msg = $this->getLanguage($player, "buy-time");
                                $msg = str_replace("%perk%", $this->getLanguage($player, "$check-msg"), $msg);
                                $msg = str_replace("%moneyp%", $config->getNested("perk.$check.price"), $msg);
                                $msg2 = $config->getNested("settings.perk-time.format");
                                $msg2 = str_replace("%year%", $year, $msg2);
                                $msg2 = str_replace("%month%", $month, $msg2);
                                $msg2 = str_replace("%day%", $day, $msg2);
                                $msg2 = str_replace("%hour%", $hour, $msg2);
                                $msg2 = str_replace("%minute%", $minute, $msg2);
                                $msg = str_replace("%time%", $msg2, $msg);
                                $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                                $eco->reduceMoney($player, $config->getNested("perk.$check.price"));
                            }
                        }
                    } else {
                        $msg = $this->getLanguage($player, "no-money-economyapi");
                        $msg = str_replace("%need-money%", $config->getNested("perk.$check.price") - $eco->myMoney($player), $msg);
                        $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                    }
                } else {
                    $playernewperk[] = $player->getName();    
                }
            } else {
                if ($players->get("$check-buy") == true) {
                    $playernewperk[] = $player->getName();
                } else {
                    if ($config->getNested("settings.buy-confirm.enable") == true) {
                        $perk->getPerkBuyConfirm($player, $check, "notime");
                    } else {
                        if ($eco->myMoney($player) >= $config->getNested("perk.$perk.price")) {
                            $players->set("$perk", false);
                            $players->set("$perk-buy", true);
                            $msg = $this->getLanguage($player, "buy-economyapi");
                            $msg = str_replace("%perk%", $this->getLanguage($player, "$perk-msg"), $msg);
                            $msg = str_replace("%moneyp%", $config->getNested("perk.$perk.price"), $msg);
                            $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                            $eco->reduceMoney($player, $config->getNested("perk.$perk.price"));
                        } else {
                            $msg = $this->getLanguage($player, "no-money-economyapi");
                            $msg = str_replace("%need-money%", $config->getNested("perk.$perk.price") - $eco->myMoney($player), $msg);
                            $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
                        } 
                    }
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
        $config = Main::getInstance()->getConfig();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $effect = $this->getPerkEffect($player, $check);
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        $date = new \DateTime('now');
        $datas = explode(":", $date->format("Y:m:d:H:i:s"));
        $data = ($datas[0] - 0) . ":" . ($datas[1] - 0) . ":" . ($datas[2] - 0) . ":" . ($datas[3] - 0) . ":" . ($datas[4] - 0) . ":" . ($datas[5] - 0);
        $speedcheck = null;
        if ($players->get($check) == true and $effect !== null) {
            if (!$player->hasEffect($effect) and !in_array($check, $block)) {
                $players->set($check, false);
            }
        }
        if ($config->getNested("settings.perk-time.enable") == true and $players->exists("$check-buy-count") and $data >= $players->get("$check-buy-count")) {
            $players->set($check, false);
            $players->set($check . "-buy", false);
            $players->remove($check . "-buy-count");
            $msg = $this->getLanguage($player, "close-time");
            $msg = str_replace("%perk%", $check, $msg);
            $player->sendMessage($this->getLanguage($player, "prefix") . $msg);
        }
        if (($config->getNested("settings.economy-api") == true and $players->get($check . "-buy") == true) or ($config->getNested("perk.$check.perms") !== false and $player->hasPermission($config->getNested("perk.$check.perms"))) or ($players->get($check . "-buy") == false and $players->get($check) == true)) {
            if ($players->get($check) == false and $effect !== null) {
                if ($player->hasEffect($effect) and !in_array($check, $block)) {
                    $players->set($check, true);
                }
            }
        }
        $players->save();
        if ($config->getNested("settings.economy-api") == true) {
            if ($players->get("$check-buy") == true) {
                if ($players->get($check) == true) {
                    $speedcheck = $this->getLanguage($player, "enable-button");
                } else {
                    $speedcheck = $this->getLanguage($player, "disable-button");
                }
            } else {
                if ($players->get($check) == true and $effect !== null) {
                    if ($player->hasEffect($effect) and !in_array($check, $block)) {
                        $speedcheck = $this->getLanguage($player, "enable-button");
                    }
                } else {
                    $msg = $this->getLanguage($player, "buy-button");
                    $msg = str_replace("%moneyp%", $config->getNested("perk.$check.price"), $msg);
                    $speedcheck = $msg;
                }
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
     */
    public function getPerkEffect(Player $player, string $check) 
    {
        $effect = null;
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
        return $effect;
    }

/**
 * @param Player $player
 * @param string $message
 */
    public function getLanguage(Player $player, string $message) 
    {
        $config = Main::getInstance()->getConfig();
        if (file_exists(Main::getInstance()->getDataFolder() . "lang/" . $config->get("language") . ".yml")) {
            $messages = new Config(Main::getInstance()->getDataFolder() . "lang/" . $config->get("language") . ".yml", Config::YAML);
            $msg = $messages->get($message);
        } else {
            if (file_exists(Main::getInstance()->getDataFolder() . "lang/english.yml")) {
                $messages = new Config(Main::getInstance()->getDataFolder() . "lang/english.yml", Config::YAML);
                $msg = $messages->get($message);
            } else {
                $msg = "§cThis language was not found. please change the language!";
            }
        }
        return $msg;
    }
}