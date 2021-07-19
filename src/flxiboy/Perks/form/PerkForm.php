<?php

namespace flxiboy\Perks\form;

use pocketmine\{
    Player,
    Server
};
use pocketmine\entity\{
    EffectInstance,
    Effect
};
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
	 */
    public function getPerks(Player $player) 
    {
        $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $api = new API($this->plugin, $player);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $form = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            if ($data == "friend") {
                $this->getPerkFriend($player);
            } else {
                $checkPerk = new API($this->plugin, $player);
                $checkPerk->getCheckPerk($player, $data);
            }
            return true;
        });
        $form->setTitle($api->getLanguage($player, "title-ui"));
        if ($config->getNested("settings.economy-api") == true) {
            $msg = $api->getLanguage($player, "text-money-ui");
            $msg = str_replace("%money%", $eco->myMoney($player), $msg);
            $form->setContent($msg);
        } else {
            $form->setContent($api->getLanguage($player, "text-ui"));
        }
        if ($config->getNested("settings.friends.enable") == true and $config->getNested("settings.economy-api") == true) {
            if ($config->getNested("settings.friends.menu-img") !== false and strpos($config->getNested("settings.friends.menu-img"), "textures/") !== false) { $picturef = 0; } else { $picturef = 1; }
            $form->addButton($api->getLanguage($player, "button-friends"), $picturef, $config->getNested("settings.friends.menu-img"), "friend");
        }
        foreach ($config->getNested("perk.order") as $enable) {
            $msg = $api->getLanguage($player, $enable);
            $msg = str_replace("%status%", $api->getStatus($player, $enable), $msg);
            if ($config->getNested("perk.perms.enable") == true) {
                if ($players->get($enable . "-buy") == true) {
                    if ($config->getNested("perk.$enable.img") !== false and strpos($config->getNested("perk.$enable.img"), "textures/") !== false) {
                        $form->addButton($msg, 0, $config->getNested("perk.$enable.img"), $enable);
                    } else { 
                        $form->addButton($msg, 1, $config->getNested("perk.$enable.img"), $enable);
                    }
                }
            } else {
                if ($config->getNested("perk.$enable.img") !== false and strpos($config->getNested("perk.$enable.img"), "textures/") !== false) {
                    $form->addButton($msg, 0, $config->getNested("perk.$enable.img"), $enable);
                } else { 
                    $form->addButton($msg, 1, $config->getNested("perk.$enable.img"), $enable);
                }
            }
        }
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
        $api = new API($this->plugin, $player);
        $form = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI")->createCustomForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $api = new API($this->plugin, $player);
            $check = null;
            $effect = null;
            if (isset($this->plugin->playernewperkname[$player->getName()])) {
                $check = $this->plugin->playernewperkname[$player->getName()];
                $effect = $api->getPerkEffect($player, $this->plugin->playernewperkname[$player->getName()], "main");
            }
            $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
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
            $this->plugin->playernewperkname[$player->getName()] = null;
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
        $api = new API($this->plugin, $player);
        $form = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI")->createCustomForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $api = new API($this->plugin, $player);
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
            
            $targetd = new Config($this->plugin->getDataFolder() . "players/" . $data[0] . ".yml", Config::YAML);
            if ($data[0] == $player->getName()) {
                $player->sendMessage($api->getLanguage($player, "prefix") . $api->getLanguage($player, "not-yourself-friends"));
                return;
            }
            if (!file_exists($this->plugin->getDataFolder() . "players/" . $data[0] . ".yml") or $data[0] == null) {
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
        $api = new API($this->plugin, $player);
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $form = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $api = new API($this->plugin, $player);
            $data = explode(":", $data);
            if ($data[3] == true) {
                $target = $this->plugin->getServer()->getPlayer($data[0]);
                $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
                $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $targetd = new Config($this->plugin->getDataFolder() . "players/" . $data[0] . ".yml", Config::YAML);
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