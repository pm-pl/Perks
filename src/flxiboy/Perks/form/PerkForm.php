<?php

namespace flxiboy\Perks\form;

use pocketmine\entity\{
    EffectInstance,
    Effect
};
use pocketmine\Player;
use pocketmine\utils\Config;
use flxiboy\Perks\Main;
use flxiboy\Perks\api\API;

/**
 * Class PerkForm
 * @package flxiboy\Perks\form
 */
class PerkForm 
{

    /**
	 * @param Player $player
	 */
    public function getPerks(Player $player) 
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) use ($config) { 
            if ($data === null) {
                return; 
            }
            if ($config->getNested("settings.friends.enable") == true and $config->getNested("settings.economy-api") == true) {
                if ($data == 0) {
                    $this->getPerkFriend($player);
                } else {
                    $this->getPerkFriend2($player, (integer)($data - "1"), $data);
                }
            } else {
                $this->getPerkFriend2($player, (integer)$data, $data);
            }
            return true;
        });
        $form->setTitle($api->getLanguage($player, "title-ui"));
        $form->setContent($api->getLanguage($player, "text-category"));
        if ($config->getNested("settings.friends.enable") == true and $config->getNested("settings.economy-api") == true) {
            if ($config->getNested("settings.friends.menu-img") !== false and strpos($config->getNested("settings.friends.menu-img"), "textures/") !== false) { $picturef = 0; } else { $picturef = 1; }
            $form->addButton($api->getLanguage($player, "button-friends"), $picturef, $config->getNested("settings.friends.menu-img"), "friend");
        }
        foreach ($config->get("category") as $cate => $category) {
            $list = explode(":", $category["name"]);
            if (strpos($list[1], "textures/") !== false) {
                $form->addButton($list[0], 0, $list[1], $list[0]);
            } elseif (strpos($list[1], "http") !== false) {
                $form->addButton($list[0], 1, $list[1] . ":" . $list[2]);
            } else {
                $form->addButton($list[0], -1, "");
            }
        }
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
     * @param string $type
     * @param string $cate
	 */
    public function getPerkFriend2(Player $player, string $type, string $cate) 
    {
        $api = new API();
        $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $config = Main::getInstance()->getConfig();
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $checkPerk = new API();
            $checkPerk->getCheckPerk($player, $data);
            return true;
        });
        $title = $api->getLanguage($player, "title-category");
        $title = str_replace("%category%", $cate, $title);
        $form->setTitle($title);
        if ($config->getNested("settings.economy-api") == true) {
            $msg = $api->getLanguage($player, "text-money-ui");
            $msg = str_replace("%money%", $eco->myMoney($player), $msg);
            $form->setContent($msg);
        } else {
            $form->setContent($api->getLanguage($player, "text-ui"));
        }
        foreach ($config->get("category")[$type]["perks"] as $cate => $perks) {
            $list = explode(":", $perks);
            $msg = $api->getLanguage($player, $list[0]);
            $msg = str_replace("%status%", $api->getStatus($player, $list[0]), $msg);
            if ($config->getNested("perk.perms.enable") == true) {
                if ($players->get($list[0] . "-buy") == true) {
                    if ($config->getNested("perk." . $list[0] . ".img") !== false and strpos($config->getNested("perk." . $list[0] . ".img"), "textures/") !== false) {
                        $form->addButton($msg, 0, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                    } else { 
                        $form->addButton($msg, 1, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                    }
                }
            } else {
                if ($config->getNested("perk." . $list[0] . ".img") !== false and strpos($config->getNested("perk." . $list[0] . ".img"), "textures/") !== false) {
                    $form->addButton($msg, 0, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                } else { 
                    $form->addButton($msg, 1, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                }
            }
        }
        $form->sendToPlayer($player);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $perk
     * @param string $type
     */
    public function getPerkBuyConfirm(Player $player, string $perk, string $type) 
    {
        $api = new API();
        $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $config = Main::getInstance()->getConfig();
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) use ($perk, $eco, $players, $config, $api, $type) { 
            if ($data === null) {
                return; 
            }
            if ($data == "yes") {
                if ($type == "time") {
                    $date = new \DateTime('now');
                    $datas = explode(":", $date->format("Y:m:d:H:i:s"));
                    $data = ($datas[0] - "0") . ":" . ($datas[1] - "0") . ":" . ($datas[2] - "0") . ":" . ($datas[3] - "0") . ":" . ($datas[4] - "0") . ":" . ($datas[5] - "0");
                    if ($eco->myMoney($player) >= $config->getNested("perk.$perk.price")) {
                        if (in_array($date->format("m"), [1, 3, 5, 7, 9, 11])) { $months = 32; } elseif (in_array($date->format("m"), [4, 6, 8, 10, 12])) { $months = 31; } else { $months = 29; }
                        $format = explode(":", $config->getNested("perk.$perk.time"));
                        $formats = explode(":", $data);
                        $year = ($formats[0] + $format[0]);
                        $month = ($formats[1] + $format[1]);
                        $day = ($formats[2] + $format[2]);
                        $hour = ($formats[3] + $format[3]);
                        $minute = ($formats[4] + $format[4]);
                        $second = ($formats[5] + $format[5]);
                        if ($second >= 60) { $second = ($second - "61"); $minute++; }
                        if ($minute >= 60) { $minute = ($minute - "61"); $hour++; }
                        if ($hour >= 24) { $hour = ($hour - "25"); $minute++; }
                        if ($day >= $months) { $day = ($day - $months); $month++; }
                        if ($month >= 12) { $month = ($month - "13"); $year++; }
                        $players->set("$perk", false);
                        $players->set("$perk-buy", true);
                        $players->set("$perk-buy-count", $year . ":" . $month . ":" . $day . ":" . $hour . ":" . $minute . ":0");
                        $players->save();
                        $msg = $api->getLanguage($player, "buy-time");
                        $msg = str_replace("%perk%", $api->getLanguage($player, "$perk-msg"), $msg);
                        $msg = str_replace("%moneyp%", $config->getNested("perk.$perk.price"), $msg);
                        $msg2 = $config->getNested("settings.perk-time.format");
                        $msg2 = str_replace("%year%", $year, $msg2);
                        $msg2 = str_replace("%month%", $month, $msg2);
                        $msg2 = str_replace("%day%", $day, $msg2);
                        $msg2 = str_replace("%hour%", $hour, $msg2);
                        $msg2 = str_replace("%minute%", $minute, $msg2);
                        $msg = str_replace("%time%", $msg2, $msg);
                        $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                        $eco->reduceMoney($player, $config->getNested("perk.$perk.price"));
                    } else {
                        $msg = $api->getLanguage($player, "no-money-economyapi");
                        $msg = str_replace("%need-money%", $config->getNested("perk.$perk.price") - $eco->myMoney($player), $msg);
                        $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                    }
                } else {
                    if ($eco->myMoney($player) >= $config->getNested("perk.$perk.price")) {
                        $players->set("$perk", false);
                        $players->set("$perk-buy", true);
                        $msg = $api->getLanguage($player, "buy-economyapi");
                        $msg = str_replace("%perk%", $api->getLanguage($player, "$perk-msg"), $msg);
                        $msg = str_replace("%moneyp%", $config->getNested("perk.$perk.price"), $msg);
                        $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                        $eco->reduceMoney($player, $config->getNested("perk.$perk.price"));
                    } else {
                        $msg = $api->getLanguage($player, "no-money-economyapi");
                        $msg = str_replace("%need-money%", $config->getNested("perk.$perk.price") - $eco->myMoney($player), $msg);
                        $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                    } 
                }
                $players->save();
            }
        });
        $form->setTitle($api->getLanguage($player, "title-confirm"));
        $content = $api->getLanguage($player, "text-confirm");
        $content = str_replace("%perk%", $api->getLanguage($player, "$perk-msg"), $content);
        $content = str_replace("%moneyp%", $config->getNested("perk.$perk.price"), $content);
        $form->setContent($content);
        if ($config->getNested("settings.buy-confirm.yes-img") !== false and strpos($config->getNested("settings.buy-confirm.yes-img"), "textures/") !== false) { $pictureyes = 0; } else { $pictureyes = 1; }
        if ($config->getNested("settings.buy-confirm.no-img") !== false and strpos($config->getNested("settings.buy-confirm.no-img"), "textures/") !== false) { $pictureno = 0; } else { $pictureno = 1; }
        $form->addButton($api->getLanguage($player, "yes-button-confirm"), $pictureyes, $config->getNested("settings.buy-confirm.yes-img"), "yes");
        $form->addButton($api->getLanguage($player, "no-button-confirm"), $pictureno, $config->getNested("settings.buy-confirm.no-img"), "no");
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
     * @param string $check
     * @param string $effect
	 */
    public function getPerkSwitch(Player $player, string $check, string $effect)
    {
        $api = new API();
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createCustomForm(function (Player $player, $data = null) use ($check) { 
            if ($data === null) {
                return; 
            }
            $api = new API();
            $check = $check;
            $effect = $api->getPerkEffect($player, $check);
            $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
            if ($data[0] !== $player->getEffect($effect)->getEffectLevel()) {
                if ($data[0] == 0) {
                    $players->set($check, false);
                    if ($effect !== null) {
                        $player->removeEffect($effect);
                    }
                    $msg = $api->getLanguage($player, "disable-perk");
                    $msg = str_replace("%perk%", $api->getLanguage($player, "$check-msg"), $msg);
                    $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                } else {
                    if ($effect !== null) {
                        $player->removeEffect($effect);
                        $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, $data[0] - 1, false));
                    }
                    $msg = $api->getLanguage($player, "new-strength");
                    $msg = str_replace("%perk%", $api->getLanguage($player, "$check-msg"), $msg);
                    $msg = str_replace("%strength%", $data[0], $msg);
                    $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                }
                $players->save();
            }
            return true;
        });
        $form->setTitle($api->getLanguage($player, "title-strength"));
        if ($player->hasEffect($effect)) {
            $form->addStepSlider($api->getLanguage($player, "text-strength"), ["0", "1", "2", "3", "4", "5"], $player->getEffect($effect)->getEffectLevel());
        } else {
            $form->addStepSlider($api->getLanguage($player, "text-strength"), ["0"], 0);
        }
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
	 */
    public function getPerkFriend(Player $player) 
    {
        $api = new API();
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createCustomForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $api = new API();
            $perk = null;
            if ($data[1] == 0) { $perk = "speed";
            } elseif ($data[1] == 1) { $perk = "jump";
            } elseif ($data[1] == 2) { $perk = "haste";
            } elseif ($data[1] == 3) { $perk = "night-vision";
            } elseif ($data[1] == 4) { $perk = "no-hunger";
            } elseif ($data[1] == 5) { $perk = "no-falldamage";
            } elseif ($data[1] == 6) { $perk = "fast-regeneration";
            } elseif ($data[1] == 7) { $perk = "keep-inventory";
            } elseif ($data[1] == 8) { $perk = "dopple-xp";
            } elseif ($data[1] == 9) { $perk = "strength";
            } elseif ($data[1] == 10) { $perk = "no-firedamage";
            } elseif ($data[1] == 11) { $perk = "fly";
            } elseif ($data[1] == 12) { $perk = "water-breathing";
            } elseif ($data[1] == 13) { $perk = "invisibility"; }
            
            $targetd = new Config(Main::getInstance()->getDataFolder() . "players/" . $data[0] . ".yml", Config::YAML);
            if ($data[0] == $player->getName()) {
                $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "not-yourself-friends"));
                return;
            }
            if (!file_exists(Main::getInstance()->getDataFolder() . "players/" . $data[0] . ".yml") or $data[0] == null) {
                $msg = $api->getLanguage($player, "target-notfound-friends");
                $msg = str_replace("%target%", $data[0], $msg);
                $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                return;
            }
            if ($targetd->get($perk) == true or $targetd->get($perk . "-buy") == true) {
                $msg = $api->getLanguage($player, "perk-buyed-friends");
                $msg = str_replace("%target%", $data[0], $msg);
                $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                return;
            }
            $this->getPerkFriendConfim($player, $data[0], $perk, $data[2]);
        });
        $form->setTitle($api->getLanguage($player, "title-friends"));
        $form->addInput($api->getLanguage($player, "text-friends"), $api->getLanguage($player, "user-friends"));
        $form->addDropdown($api->getLanguage($player, "perks-friends"), [$api->getLanguage($player, "speed-msg"), $api->getLanguage($player, "jump-msg"), $api->getLanguage($player, "haste-msg"), $api->getLanguage($player, "night-vision-msg"), 
            $api->getLanguage($player, "no-hunger-msg"), $api->getLanguage($player, "no-falldamage-msg"), $api->getLanguage($player, "fast-regeneration-msg"), $api->getLanguage($player, "keep-inventory-msg"), $api->getLanguage($player, "dopple-xp-msg"), 
            $api->getLanguage($player, "strength-msg"), $api->getLanguage($player, "no-firedamage-msg"), $api->getLanguage($player, "fly-msg"), $api->getLanguage($player, "water-breathing-msg"), $api->getLanguage($player, "invisibility-msg"),
            $api->getLanguage($player, "keep-xp-msg"), $api->getLanguage($player, "double-jump-msg"), $api->getLanguage($player, "auto-smelting-msg")]);
        $form->addInput($api->getLanguage($player, "message-friends"));
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
     * @param string $target
     * @param string $perk
     * @param string $message
	 */
    public function getPerkFriendConfim(Player $player, string $target, string $perk, string $message) 
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $api = new API();
            $data = explode(":", $data);
            if ($data[3] == true) {
                $target = Main::getInstance()->getServer()->getPlayer($data[0]);
                $config = Main::getInstance()->getConfig();
                $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $targetd = new Config(Main::getInstance()->getDataFolder() . "players/" . $data[0] . ".yml", Config::YAML);
                if ($eco->myMoney($player) >= $config->getNested("perk." . $data[1] . ".price")) {
                    $msgp = $api->getLanguage($player, "success-friends");
                    $msgp = str_replace("%target%", $data[0], $msgp);
                    $msgp = str_replace("%perk%", $api->getLanguage($player, $data[1] . "-msg"), $msgp);
                    $player->sendMessage($api->getLanguage($player, "prefix") . $msgp);
                    $eco->reduceMoney($player, $config->getNested("perk." . $data[1] . ".price"));
                    if ($target instanceof Player) {
                        $msgt = $api->getLanguage($player, "target-success-friends");
                        $msgt = str_replace("%player%", $player->getName(), $msgt);
                        $msgt = str_replace("%perk%", $api->getLanguage($player, $data[1] . "-msg"), $msgt);
                        $msgt = str_replace("%message%", $data[2], $msgt);
                        $target->sendMessage($api->getLanguage($player, "prefix") . $msgt);   
                    }
                    $targetd->set($data[1] . "-buy", true);
                } else {
                    $msg = $api->getLanguage($player, "no-money-economyapi");
                    $msg = str_replace("%need-money%", $config->getNested("perk." . $data[1] . ".price") - $eco->myMoney($player), $msg);
                    $player->sendMessage($api->getLanguage($player, "prefix") . $msg);
                }
                $targetd->save();
            }
        });
        $form->setTitle($api->getLanguage($player, "title-friends"));
        $content = $api->getLanguage($player, "confirm-text-friends");
        $content = str_replace("%target%", $target, $content);
        $content = str_replace("%perk%", $api->getLanguage($player, "$perk-msg"), $content);
        $content = str_replace("%moneyp%", $config->getNested("perk.$perk.price"), $content);
        $form->setContent($content);
        if ($config->getNested("settings.friends.yes-img") !== false and strpos($config->getNested("settings.friends.yes-img"), "textures/") !== false) { $pictureyes = 0; } else { $pictureyes = 1; }
        if ($config->getNested("settings.friends.no-img") !== false and strpos($config->getNested("settings.friends.no-img"), "textures/") !== false) { $pictureno = 0; } else { $pictureno = 1; }
        $form->addButton($api->getLanguage($player, "confirm-yes-friends"), $pictureyes, $config->getNested("settings.friends.yes-img"), implode(":", [$target, $perk, $message, true]));
        $form->addButton($api->getLanguage($player, "confirm-no-friends"), $pictureno, $config->getNested("settings.friends.no-img"), implode(":", [$target, $perk, $message, false]));
        $form->sendToPlayer($player);
        return $form;
    }
}