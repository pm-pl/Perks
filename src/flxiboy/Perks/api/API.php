<?php
declare(strict_types=1);
namespace flxiboy\Perks\api;

use pocketmine\entity\effect\{
    EffectInstance,
    VanillaEffects
};
use flxiboy\Perks\form\PerkForm;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;

/**
 * Class API
 * @package flxiboy\Perks\api
 */
class API 
{

    /**
     * @param Player $player
     * @param string $check
     * @return bool
     */
    public function getCheckPerk(Player $player, string $check): bool
    {
        $playernewperk = [];
        $perk = new PerkForm();
        $config = Main::getInstance()->getConfig();
        $players = Main::getInstance()->getPlayers($player->getName());
        $effect = $this->getPerkEffect($check);
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        if ($config->getNested("settings.economy-api") == true) {
            $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            if ($config->getNested("settings.perk-time.enable") == true) {
                if ($players->get($check) == false) {
                    $date = new \DateTime("now");
                    $datas = explode(":", $date->format("Y:m:d:H:i"));
                    $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
                    if ($eco->myMoney($player) >= $config->getNested("perk." . $check . ".price") || $players->exists($check . "-buy-count")) {
                        if ($players->exists($check . "-buy-count")) {
                            if ($data >= $players->get($check . "-buy-count")) {
                                $players->set($check, false);
                                $players->set($check . "-buy", false);
                                $players->remove($check . "-buy-count");
                                $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("close-time", ["%perk%" => $this->getLanguage($check . "-msg")]));
                                if ($effect !== null) {
                                    $player->getEffects()->clear();
                                }
                            } else {
                                $playernewperk[] = $player->getName();
                            }
                        } else {
                            if ($config->getNested("settings.buy-confirm.enable") == true) {
                                $perk->getPerkBuyConfirm($player, $check, "time");
                            } else {
                                if (in_array($date->format("m"), [1, 3, 5, 7, 9, 11])) { $months = 32; } elseif (in_array($date->format("m"), [4, 6, 8, 10, 12])) { $months = 31; } else { $months = 29; }
                                $format = explode(":", $config->getNested("perk." . $check . ".time"));
                                $formats = explode(":", $data);
                                $year = ((int)$formats[0] + (int)$format[0]);
                                $month = ((int)$formats[1] + (int)$format[1]);
                                $day = ((int)$formats[2] + (int)$format[2]);
                                $hour = ((int)$formats[3] + (int)$format[3]);
                                $minute = ((int)$formats[4] + (int)$format[4]);
                                if ($minute >= 60) { $minute = ((int)$minute - 61); $hour++; }
                                if ($hour >= 24) { $hour = ((int)$hour - 25); $minute++; }
                                if ($day >= $months) { $day = ((int)$day - (int)$months); $month++; }
                                if ($month >= 12) { $month = ((int)$month - 13); $year++; }
                                $players->set($check, false);
                                $players->set($check . "-buy", true);
                                $players->set($check . "-buy-count", $year . ":" . $month . ":" . $day . ":" . $hour . ":" . $minute . ":0");
                                $players->save();
                                $msg2 = $config->getNested("settings.perk-time.format");
                                $msg2 = str_replace("%year%", $year, $msg2);
                                $msg2 = str_replace("%month%", $month, $msg2);
                                $msg2 = str_replace("%day%", $day, $msg2);
                                $msg2 = str_replace("%hour%", (int)$hour - 1, $msg2);
                                $msg2 = str_replace("%minute%", $minute, $msg2);
                                $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("buy-time", ["%perk%" => $this->getLanguage($check . "-msg"), "%moneyp%" => $config->getNested("perk." . $check . ".price"), "%time%" => $msg2]));
                                $eco->reduceMoney($player, $config->getNested("perk." . $check . ".price"));
                            }
                        }
                    } else {
                        $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("no-money-economyapi", ["%need-money%" => ($config->getNested("perk." . $check . ".price") - $eco->myMoney($player))]));
                    }
                } else {
                    $playernewperk[] = $player->getName();
                }
            } else {
                if ($players->get($check . "-buy") == true) {
                    $playernewperk[] = $player->getName();
                } else {
                    if ($config->getNested("settings.buy-confirm.enable") == true) {
                        $perk->getPerkBuyConfirm($player, $check, "notime");
                    } else {
                        if ($eco->myMoney($player) >= $config->getNested("perk.$check.price")) {
                            $players->set($check, false);
                            $players->set("$check-buy", true);
                            $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("buy-economyapi", ["%perk%" => $this->getLanguage("$check-msg"), "%moneyp%" => $config->getNested("perk.$check.price")]));
                            $eco->reduceMoney($player, $config->getNested("perk.$check.price"));
                        } else {
                            $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("no-money-economyapi", ["%need-money%" => ($config->getNested("perk.$check.price") - $eco->myMoney($player))]));
                        }
                    }
                }
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                        $playernewperk[] = $player->getName();
                } else {
                    if ($player->hasPermission("Perks." . $check)) {
                        $playernewperk[] = $player->getName();
                    } else {
                        $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("no-perms"));
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
                    $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("disable-perk", ["%perk%" => $this->getLanguage($check . "-msg")]));
                } else {
                    $perk->getPerkSwitch($player, $check, $effect);
                }
            } else {
                $players->set($check, true);
                if (!in_array($check, $block) && $effect !== null) {
                    $player->getEffects()->add(new EffectInstance($effect, 107374182, 0, false));
                }
                if ($check == "fly") {
                    $player->setAllowFlight(true);
                }
                $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("enable-perk", ["%perk%" => $this->getLanguage($check . "-msg")]));
            }
        }
        $players->save();
        return true;
    }

    /**
     * @param Player $player
     * @param string $check
     * @return string
     */
    public function getStatus(Player $player, string $check): string
    {
        $config = Main::getInstance()->getConfig();
        $players = Main::getInstance()->getPlayers($player->getName());
        $effect = $this->getPerkEffect($check);
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp", "fly", "keep-xp", "double-jump", "auto-smelting"];
        $date = new \DateTime("now");
        $datas = explode(":", $date->format("Y:m:d:H:i:s"));
        $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0) . ":" . ((int)$datas[5] - 0);
        $speedcheck = $this->getLanguage("disable-button");
        if ($players->get($check) == true && $effect !== null) {
            if (!$player->getEffects()->has($effect) && !in_array($check, $block)) {
                $players->set($check, false);
            }
        }
        if ($config->getNested("settings.perk-time.enable") == true && $config->getNested("settings.economy-api") == true && $players->exists("$check-buy-count") && $data >= $players->get("$check-buy-count")) {
            $players->set($check, false);
            $players->set($check . "-buy", false);
            $players->remove($check . "-buy-count");
            $player->sendMessage($this->getLanguage("prefix") . $this->getLanguage("close-time", ["%perk%" => $this->getLanguage($check . "-msg")]));
            if ($effect !== null) {
                $player->getEffects()->clear();
            }
        }
        if (($config->getNested("settings.economy-api") == true && $players->get($check . "-buy") == true) || ($config->getNested("perk.$check.perms") !== false && $player->hasPermission($config->getNested("perk.$check.perms"))) || ($players->get($check . "-buy") == false && $players->get($check) == true)) {
            if ($players->get($check) == false && $effect !== null) {
                if ($player->getEffects()->has($effect) && !in_array($check, $block)) {
                    $players->set($check, true);
                }
            }
        }
        $players->save();
        if ($config->getNested("settings.economy-api") == true) {
            if ($players->get("$check-buy") == true) {
                if ($players->get($check) == true) {
                    $speedcheck = $this->getLanguage("enable-button");
                } else {
                    $speedcheck = $this->getLanguage("disable-button");
                }
            } else {
                if ($players->get($check) == true && $effect !== null) {
                    if ($player->getEffects()->has($effect) && !in_array($check, $block)) {
                        $speedcheck = $this->getLanguage("enable-button");
                    }
                } else {
                    $speedcheck = $this->getLanguage("buy-button", ["%moneyp%" => $config->getNested("perk." . $check . ".price")]);
                }
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                    if ($players->get($check) == true) {
                        $speedcheck = $this->getLanguage("enable-button");
                    } else {
                        $speedcheck = $this->getLanguage("disable-button");
                    }
                } else {
                    if ($player->hasPermission("Perks.$check")) {
                        if ($players->get($check) == true) {
                            $speedcheck = $this->getLanguage("enable-button");
                        } else {
                            $speedcheck = $this->getLanguage("disable-button");
                        } 
                    } else {
                        $speedcheck = $this->getLanguage("no-perms-button");
                    }
                }
            } else {
                if ($players->get($check) == true) {
                    $speedcheck = $this->getLanguage("enable-button");
                } else {
                    $speedcheck = $this->getLanguage("disable-button");
                }
            }
        }
        return $speedcheck;
    }

    /**
     * @param string $check
     */
    public function getPerkEffect(string $check)
    {
        $effect = null;
        if ($check == "speed") { 
            $effect = VanillaEffects::SPEED();
        } elseif ($check == "jump") { 
            $effect = VanillaEffects::JUMP_BOOST();
        } elseif ($check == "haste") { 
            $effect = VanillaEffects::HASTE();
        } elseif ($check == "night-vision") { 
            $effect = VanillaEffects::NIGHT_VISION();
        } elseif ($check == "fast-regeneration") { 
            $effect = VanillaEffects::REGENERATION();
        } elseif ($check == "strength") { 
            $effect = VanillaEffects::STRENGTH();
        } elseif ($check == "no-firedamage") { 
            $effect = VanillaEffects::FIRE_RESISTANCE();
        } elseif ($check == "water-breathing") {
            $effect = VanillaEffects::WATER_BREATHING();
        } elseif ($check == "invisibility") {
            $effect = VanillaEffects::INVISIBILITY();
        }
        return $effect;
    }

    /**
     * @param string $message
     * @param array $params
     * @return string
     */
    public function getLanguage(string $message, array $params = []): string
    {
        $config = Main::getInstance()->getConfig();
        $lang = Main::getInstance()->getDataFolder() . "lang/" . $config->get("language") . ".yml";
	    $msg = "";
        if (file_exists($lang)) {
            $messages = new Config($lang, Config::YAML);
            $msg = $messages->getNested($message);
            foreach ($params as $index => $param) {
                $msg = str_replace($index, $param, $msg);
            }
        } else {
            Main::getInstance()->loadFiles();
            if (file_exists(Main::getInstance()->getDataFolder() . "config.yml")) {
                $config->reload();
            }
            if (file_exists($lang)) {
                $language = new Config($lang, Config::YAML);
                $language->reload();
            }
            $this->getLanguage($message, $params);
        }
        return $msg;
    }
}
