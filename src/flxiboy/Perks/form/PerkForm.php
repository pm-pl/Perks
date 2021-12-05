<?php

namespace flxiboy\Perks\form;

use jojoe77777\FormAPI\{
    CustomForm,
    SimpleForm
};
use pocketmine\entity\effect\EffectInstance;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use flxiboy\Perks\api\API;
use flxiboy\Perks\Main;

/**
 * Class PerkForm
 * @package flxiboy\Perks\form
 */
class PerkForm 
{

    /**
     * @param Player $player
     * @return SimpleForm
     */
    public function getPerks(Player $player): SimpleForm
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $form = new SimpleForm(function (Player $player, $data = null) use ($config) { 
            if ($data === null) return; 

            if ($config->getNested("settings.friends.enable") == true && $config->getNested("settings.economy-api") == true) {
                if ($data == "friend") {
                    $this->getPerkFriend($player);
                } else {
                    $cate = (int)$data - 1;
                    $this->getPerkFriend2($player, $cate, $data);
                }
            } else {
                $this->getPerkFriend2($player, (int)$data, $data);
            }
            return true;
        });
        $form->setTitle($api->getLanguage("title-ui"));
        $form->setContent($api->getLanguage("text-category"));
        if ($config->getNested("settings.friends.enable") == true && $config->getNested("settings.economy-api") == true) {
            if ($config->getNested("settings.friends.menu-img") !== false && strpos($config->getNested("settings.friends.menu-img"), "textures/") !== false) { $picturef = 0; } else { $picturef = 1; }
            $form->addButton($api->getLanguage("button-friends"), $picturef, $config->getNested("settings.friends.menu-img"), "friend");
        }
        foreach ($config->get("category") as $cate => $category) {
            $list = explode(":", $category["name"]);
            if (strpos($list[1], "textures/") !== false) {
                $form->addButton($list[0], 0, $list[1]);
            } elseif (strpos($list[1], "http") !== false) {
                $form->addButton($list[0], 1, $list[1] . ":" . $list[2]);
            } else {
                $form->addButton($list[0], -1, "");
            }
        }
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $type
     * @param string $cate
     * @return SimpleForm
     */
    public function getPerkFriend2(Player $player, string $type, string $cate): SimpleForm
    {
        $api = new API();
        $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $config = Main::getInstance()->getConfig();
        $players = Main::getInstance()->getPlayers($player->getName());
        $form = new SimpleForm(function (Player $player, $data = null) use ($api) { 
            if ($data === null) return; 

            $api->getCheckPerk($player, $data);
            return true;
        });
        $titles = explode(":", $config->get("category")[$type]["name"]);
        $form->setTitle($api->getLanguage("title-category", ["%category%" => $titles[0]]));
        if ($config->getNested("settings.economy-api") == true) {
            $form->setContent($api->getLanguage("text-money-ui", ["%money%" => $eco->myMoney($player)]));
        } else {
            $form->setContent($api->getLanguage("text-ui"));
        }
        foreach ($config->get("category")[$type]["perks"] as $cate => $perks) {
            $list = explode(":", $perks);
            if ($config->getNested("perk.perms.enable") == true) {
                if ($players->get($list[0] . "-buy") == true) {
                    if ($config->getNested("perk." . $list[0] . ".img") !== false && strpos($config->getNested("perk." . $list[0] . ".img"), "textures/") !== false) {
                        $form->addButton($api->getLanguage($list[0], ["%status%" => $api->getStatus($player, $list[0])]), 0, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                    } else { 
                        $form->addButton($api->getLanguage($list[0], ["%status%" => $api->getStatus($player, $list[0])]), 1, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                    }
                }
            } else {
                if ($config->getNested("perk." . $list[0] . ".img") !== false && strpos($config->getNested("perk." . $list[0] . ".img"), "textures/") !== false) {
                    $form->addButton($api->getLanguage($list[0], ["%status%" => $api->getStatus($player, $list[0])]), 0, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                } else { 
                    $form->addButton($api->getLanguage($list[0], ["%status%" => $api->getStatus($player, $list[0])]), 1, $config->getNested("perk." . $list[0] . ".img"), $list[0]);
                }
            }
        }
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $perk
     * @param string $type
     * @return SimpleForm
     */
    public function getPerkBuyConfirm(Player $player, string $perk, string $type): SimpleForm
    {
        $api = new API();
        $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $players = Main::getInstance()->getPlayers($player->getName());
        $config = Main::getInstance()->getConfig();
        $form = new SimpleForm(function (Player $player, $data = null) use ($perk, $eco, $players, $config, $api, $type) { 
            if ($data === null) return; 

            switch ($data) {
                case 0:
                    if ($eco->isEnabled() && $type == "time") {
                        $date = new \DateTime('now');
                        $datas = explode(":", $date->format("Y:m:d:H:i"));
                        $data = ((int)$datas[0] - 0) . ":" . ((int)$datas[1] - 0) . ":" . ((int)$datas[2] - 0) . ":" . ((int)$datas[3] - 0) . ":" . ((int)$datas[4] - 0);
                        if ($eco->myMoney($player) >= $config->getNested("perk.$perk.price")) {
                            if (in_array($date->format("m"), [1, 3, 5, 7, 9, 11])) { $months = 32; } elseif (in_array($date->format("m"), [4, 6, 8, 10, 12])) { $months = 31; } else { $months = 29; }
                            $format = explode(":", $config->getNested("perk.$perk.time"));
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
                            $players->set("$perk", false);
                            $players->set("$perk-buy", true);
                            $players->set("$perk-buy-count", $year . ":" . $month . ":" . $day . ":" . $hour . ":" . $minute . ":0");
                            $players->save();
                            $msg2 = $config->getNested("settings.perk-time.format");
                            $msg2 = str_replace("%year%", $year, $msg2);
                            $msg2 = str_replace("%month%", $month, $msg2);
                            $msg2 = str_replace("%day%", $day, $msg2);
                            $msg2 = str_replace("%hour%", (int)$hour - 1, $msg2);
                            $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("buy-time", ["%perk%" => $api->getLanguage("$perk-msg"), "%moneyp%" => $config->getNested("perk.$perk.price"), "%time%" => $msg2]));
                            $eco->reduceMoney($player, $config->getNested("perk.$perk.price"));
                        } else {
                            $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("no-money-economyapi", ["%need-money%" => ($config->getNested("perk.$perk.price") - $eco->myMoney($player))]));
                        }
                    } else {
                        if ($eco->myMoney($player) >= $config->getNested("perk.$perk.price")) {
                            $players->set("$perk", false);
                            $players->set("$perk-buy", true);
                            $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("buy-economyapi", ["%perk%" => $api->getLanguage("$perk-msg"), "%moneyp%" => $config->getNested("perk.$perk.price")]));
                            $eco->reduceMoney($player, $config->getNested("perk.$perk.price"));
                        } else {
                            $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("no-money-economyapi", ["%need-money%" => ($config->getNested("perk.$perk.price") - $eco->myMoney($player))]));
                        } 
                    }
                    $players->save();
                    break;
            }
            return true;
        });
        $form->setTitle($api->getLanguage("title-confirm"));
        $form->setContent($api->getLanguage("text-confirm", ["%perk%" => $api->getLanguage("$perk-msg"), "%moneyp%" => $config->getNested("perk.$perk.price")]));
        if ($config->getNested("settings.buy-confirm.yes-img") !== false && strpos($config->getNested("settings.buy-confirm.yes-img"), "textures/") !== false) { $pictureyes = 0; } else { $pictureyes = 1; }
        if ($config->getNested("settings.buy-confirm.no-img") !== false && strpos($config->getNested("settings.buy-confirm.no-img"), "textures/") !== false) { $pictureno = 0; } else { $pictureno = 1; }
        $form->addButton($api->getLanguage("yes-button-confirm"), $pictureyes, $config->getNested("settings.buy-confirm.yes-img"));
        $form->addButton($api->getLanguage("no-button-confirm"), $pictureno, $config->getNested("settings.buy-confirm.no-img"));
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $check
     * @param $effect
     * @return CustomForm
     */
    public function getPerkSwitch(Player $player, string $check, $effect): CustomForm
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $form = new CustomForm(function (Player $player, $data = null) use ($api, $check) { 
            if ($data === null) return; 

            $effect = $api->getPerkEffect($check);
            $players = new Config(Main::getInstance()->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
            if ($data[0] !== $player->getEffects()->get($effect)->getEffectLevel()) {
                if ($data[0] == 0) {
                    $players->set($check, false);
                    if ($effect !== null) {
                        $player->getEffects()->clear();
                    }
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("disable-perk", ["%perk%" => $api->getLanguage("$check-msg")]));
                } else {
                    if ($effect !== null) {
                        $player->getEffects()->remove($effect);
                        $player->getEffects()->add(new EffectInstance($effect, 107374182, $data[0] - 1, false));
                    }
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("new-strength", ["%perk%" => $api->getLanguage("$check-msg"), "%strength%" => $data[0]]));
                }
                $players->save();
            }
            return true;
        });
        $form->setTitle($api->getLanguage("title-strength"));
        if ($player->getEffects()->has($effect)) {
            $strengths = [];
            for ($strength = 0; $strength <= $config->getNested("settings.perks-strength.strength"); $strength++) {
                $strengths[] = "$strength";
            }
            $form->addStepSlider($api->getLanguage("text-strength"), $strengths, $player->getEffects()->get($effect)->getEffectLevel());
        } else {
            $form->addStepSlider($api->getLanguage("text-strength"), ["0"], 0);
        }
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @return CustomForm
     */
    public function getPerkFriend(Player $player): CustomForm
    {
        $api = new API();
        $form = new CustomForm(function (Player $player, $data = null) use ($api){ 
            if ($data === null) return; 
            
            if ($data[0] == $player->getName()) {
                $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("not-yourself-friends"));
                return true;
            }
            if (!file_exists(Main::getInstance()->getDataFolder() . "players/" . $data[0] . ".yml") || $data[0] == null) {
                $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("target-notfound-friends", ["%target%" => $data[0]]));
                return true;
            }
            $this->getPerkFriendTarget($player, $data[0]);
            return true;
        });
        $form->setTitle($api->getLanguage("title-friends"));
        $form->addInput($api->getLanguage("text-friends"), $api->getLanguage("user-friends"));
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $target
     * @return CustomForm
     */
    public function getPerkFriendTarget(Player $player, string $target): CustomForm
    {
        $api = new API();
        $targetd = Main::getInstance()->getPlayers($target);
        $list = [];
        $list2 = [];
        foreach (Main::getInstance()->perklist as $perks) {
            if ($targetd->get("$perks-buy") == false) {
                $list[] = $perks;
                $list2[] = $api->getLanguage("$perks-msg");
            }
        }
        $form = new CustomForm(function (Player $player, $data = null) use ($target, $targetd, $api, $list){ 
            if ($data === null) return; 

            $perk = $list[$data[1]];
            if ($targetd->get($perk) == true || $targetd->get($perk . "-buy") == true) {
                $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("perk-buyed-friends", ["%target%" => $target]));
                return true;
            }
            $this->getPerkFriendConfirm($player, $target, $perk, $data[2]);
            return true;
        });
        $form->setTitle($api->getLanguage("title-friends"));
        $form->addLabel($api->getLanguage("text2-friends", ["%target%" => $target]));
        $form->addDropdown($api->getLanguage("perks-friends"), $list2);
        $form->addInput($api->getLanguage("message-friends"));
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param string $target
     * @param string $perk
     * @param string $message
     * @return SimpleForm
     */
    public function getPerkFriendConfirm(Player $player, string $target, string $perk, string $message): SimpleForm
    {
        $api = new API();
        $config = Main::getInstance()->getConfig();
        $form = new SimpleForm(function (Player $player, $data = null) use ($config, $api, $target, $perk, $message) { 
            if ($data === null) return; 

            if ($data == "yes") {
                $eco = Main::getInstance()->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $targetd = Main::getInstance()->getPlayers($target);
                if ($eco->myMoney($player) >= $config->getNested("perk." . $perk . ".price")) {
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("success-friends", ["%target%" => $target, "%perk%" => $api->getLanguage($perk . "-msg")]));
                    $eco->reduceMoney($player, $config->getNested("perk." . $perk . ".price"));
                    $target = Main::getInstance()->getServer()->getPlayerByPrefix($target);
                    if ($target instanceof Player) {
                        if ($config->getNested("settings.friends.open-ui") == true) {
                            $this->getPerkFriendThanks($player, $target, $perk, $message);
                        } else {
                            $target->sendMessage($api->getLanguage("prefix") . $api->getLanguage("target-success-friends", ["%player%" => $player->getName(), "%perk%" => $api->getLanguage($perk . "-msg"), "%message%" => $message]));
                        }
                    }
                    $targetd->set($perk . "-buy", true);
                } else {
                    $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("no-money-economyapi", ["%need-money%" => ($config->getNested("perk." . $perk . ".price") - $eco->myMoney($player))]));
                }
                $targetd->save();
            }
            return true;
        });
        $form->setTitle($api->getLanguage("title-friends"));
        $form->setContent($api->getLanguage("confirm-text-friends", ["%target" => $target, "%perk%" => $api->getLanguage("$perk-msg"), "%moneyp%" => $config->getNested("perk.$perk.price")]));
        if ($config->getNested("settings.friends.yes-img") !== false && strpos($config->getNested("settings.friends.yes-img"), "textures/") !== false) { $pictureyes = 0; } else { $pictureyes = 1; }
        if ($config->getNested("settings.friends.no-img") !== false && strpos($config->getNested("settings.friends.no-img"), "textures/") !== false) { $pictureno = 0; } else { $pictureno = 1; }
        $form->addButton($api->getLanguage("confirm-yes-friends"), $pictureyes, $config->getNested("settings.friends.yes-img"), "yes");
        $form->addButton($api->getLanguage("confirm-no-friends"), $pictureno, $config->getNested("settings.friends.no-img"), "no");
        $player->sendForm($form);
        return $form;
    }

    /**
     * @param Player $player
     * @param Player $target
     * @param string $perk
     * @param string $message
     * @return CustomForm
     */
    public function getPerkFriendThanks(Player $player, Player $target, string $perk, string $message): CustomForm
    {
        $api = new API();
        $form = new CustomForm(function (Player $target, $data = null) use ($player, $api) {
            if ($data === null) return; 

            if ($data[1] !== null && $player instanceof Player) {
                $player->sendMessage($api->getLanguage("prefix") . $api->getLanguage("msg-thanks-friends", ["%message%" => ($data[1] == null ? "§c/" : $data[1]), "%target%" => $target->getName()]));
            }
            return true;
        });
        $form->setTitle($api->getLanguage("title-friends"));
        $form->addLabel($api->getLanguage("text-thanks-friends", ["%player%" => $player->getName(), "%perk%" => $api->getLanguage("$perk-msg"), "%message%" => ($message == null ? "§c/" : $message)]));
        $form->addInput($api->getLanguage("message-thanks-friends"));
        $target->sendForm($form);
        return $form;
    }
}
