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
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            if ($data !== "friend") {
                $checkPerk = new API($this->plugin, $player);
                $checkPerk->getCheckPerk($player, $data);
            } else {
                $this->getPerkFriend($player);
            }
            return true;
        });
        $checkStatus = new API($this->plugin, $player);
        $form->setTitle($config->getNested("message.ui.title"));
        if ($config->getNested("message.ui.text-money") !== false) {
            if ($config->getNested("command.economy-api") == true) {
                $msg = $config->getNested("message.ui.text-money");
                $msg = str_replace("%money%", $eco->myMoney($player), $msg);
                $form->setContent($msg);
            } else {
                $form->setContent($config->getNested("message.ui.text"));
            }
        }
        if ($config->getNested("message.friends.enable") == true and $config->getNested("command.economy-api") == true) {
            if ($config->getNested("message.friends.button-img") !== false and strpos($config->getNested("message.friends.button-img"), "textures/") !== false) {
                $picturef = 0;
            } else {
                $picturef = 1;
            }
            $form->addButton($config->getNested("message.friends.button"), $picturef, $config->getNested("message.friends.button-img"), "friend");
        }
        foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly", "water-breathing", "invisibility"] as $name) {
            if ($config->getNested("perk.$name.enable") == true) {
                if ($config->getNested("perk.$name.img") !== false and strpos($config->getNested("perk.$name.img"), "textures/") !== false) {
                    $picture = 0;
                } else {
                    $picture = 1;
                }
                $perk = $config->getNested("perk.$name.button");
                $perk = str_replace("%status%", $checkStatus->getStatus($player, $name), $perk);
                $form->addButton($perk, $picture, $config->getNested("perk.$name.img"), $name);
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
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createCustomForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            if (isset($this->plugin->playernewperkname[$player->getName()])) {
                $api = new API($this->plugin, $player);
                $check = $this->plugin->playernewperkname[$player->getName()];
                $effect = $api->getPerkEffect($player, $this->plugin->playernewperkname[$player->getName()], "main");
            }
            $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
            $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
            if ($data[0] == 0) {
                $players->set($check, false);
                if ($effect !== null) {
                    $player->removeEffect($effect);
                }
                $msg = $config->getNested("message.mode.disable");
                $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                $player->sendMessage($config->getNested("message.prefix") . $msg);
            } else {
                $msg = $config->getNested("message.strength.new-strength");
                $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                $msg = str_replace("%strength%", $data[0], $msg);
                $player->sendMessage($config->getNested("message.prefix") . $msg);
                if ($effect !== null) {
                    $player->removeEffect($effect);
                    $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, $data[0] - 1, false));
                }
            }
            $players->save();
            $this->plugin->playernewperkname[$player->getName()] = null;
            return true;
        });
        $form->setTitle($config->getNested("message.strength.title"));
        $form->addStepSlider($config->getNested("message.strength.text"), ["0", "1", "2", "3", "4", "5"], $player->getEffect($effect)->getEffectLevel());
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
	 */
    public function getPerkFriend(Player $player) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createCustomForm(function (Player $player, $data = null) { 
            $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
            if ($data === null) {
                return; 
            }
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
            
            if (!file_exists($this->plugin->getDataFolder() . "players/" . $data[0] . ".yml") and $data[0] == null) {
                $msg = $config->getNested("message.friends.target-notfound");
                $msg = str_replace("%target%", $data[0], $msg);
                $player->sendMessage($config->getNested("message.prefix") . $msg);
                return;
            }
            $this->getPerkFriendConfim($player, $data[0], $perk);
        });
        $form->setTitle($config->getNested("message.friends.title"));
        $form->addInput($config->getNested("message.friends.text"), $config->getNested("message.friends.user"));
        $form->addDropdown($config->getNested("message.friends.perks"), [$config->getNested("perk.speed.msg"), $config->getNested("perk.jump.msg"), $config->getNested("perk.haste.msg"), $config->getNested("perk.night-vision.msg"), 
        $config->getNested("perk.no-hunger.msg"), $config->getNested("perk.no-falldamage.msg"), $config->getNested("perk.fast-regeneration.msg"), $config->getNested("perk.keep-inventory.msg"), $config->getNested("perk.dopple-xp.msg"), 
        $config->getNested("perk.strength.msg"), $config->getNested("perk.no-firedamage.msg"), $config->getNested("perk.fly.msg"), $config->getNested("perk.water-breathing.msg"), $config->getNested("perk.invisibility.msg")]);
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
     * @param string $target
     * @param string $perk
	 */
    public function getPerkFriendConfim(Player $player, string $target, string $perk) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $data = explode(":", $data);
            if ($data[2] == true) {
                $target = $this->plugin->getServer()->getPlayer($data[0]);
                $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
                $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $targetd = new Config($this->plugin->getDataFolder() . "players/" . $data[0] . ".yml", Config::YAML);
                if ($eco->myMoney($player) >= $config->getNested("perk." . $data[1] . ".price")) {
                    $msg = $config->getNested("message.friends.success");
                    $msg = str_replace("%target%", $data[0], $msg);
                    $msg = str_replace("%perk%", $data[1], $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                    $eco->reduceMoney($player, $config->getNested("perk." . $data[1] . ".price"));
                    if ($target instanceof Player) {
                        $msg = $config->getNested("message.friends.target-success");
                        $msg = str_replace("%player%", $player->getName(), $msg);
                        $msg = str_replace("%perk%", $data[1], $msg);
                        $target->sendMessage($config->getNested("message.prefix") . $msg);   
                    }
                    $targetd->set($data[1] . "-buy", true);
                    $targetd->save();
                } else {
                    $msg = $config->getNested("message.no-money");
                    $msg = str_replace("%need-money%", $config->getNested("perk." . $data[1] . ".price") - $eco->myMoney($player), $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                }
            }
        });
        $form->setTitle($config->getNested("message.friends.title"));
        $content = $config->getNested("message.friends.confirm-text");
        $content = str_replace("%target%", $target, $content);
        $content = str_replace("%perk%", $config->getNested("perk.$perk.msg"), $content);
        $content = str_replace("%money%", $config->getNested("perk.$perk.price"), $content);
        $form->setContent($content);
        if ($config->getNested("message.friends.confirm-yes-img") !== false and strpos($config->getNested("message.friends.confirm-yes-img"), "textures/") !== false) { $pictureyes = 0; } else { $pictureyes = 1; }
        if ($config->getNested("message.friends.confirm-no-img") !== false and strpos($config->getNested("message.friends.confirm-no-img"), "textures/") !== false) { $pictureno = 0; } else { $pictureno = 1; }
        $form->addButton($config->getNested("message.friends.confirm-yes"), $pictureyes, $config->getNested("message.friends.confirm-yes-img"), implode(":", [$target, $perk, true]));
        $form->addButton($config->getNested("message.friends.confirm-no"), $pictureno, $config->getNested("message.friends.confirm-no-img"), implode(":", [$target, $perk, false]));
        $form->sendToPlayer($player);
        return $form;
    }
}