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

/**
 * Class PerkForm
 * @package flxiboy\Perks\form
 */
class PerkForm 
{

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
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            switch ($data) {
                case "speed":
                    $this->getCheckPerk($player, "speed");
                    break;
                case "jump":
                    $this->getCheckPerk($player, "jump");
                    break;
                case "haste":
                    $this->getCheckPerk($player, "haste");
                    break;
                case "night":
                    $this->getCheckPerk($player, "night-vision");
                    break;
                case "hunger":
                    $this->getCheckPerk($player, "no-hunger");
                    break;
                case "fall":
                    $this->getCheckPerk($player, "no-falldamage");
                    break;
                case "regeneration":
                    $this->getCheckPerk($player, "fast-regeneration");
                    break;
                case "inventory":
                    $this->getCheckPerk($player, "keep-inventory");
                    break;
                case "xp":
                    $this->getCheckPerk($player, "dopple-xp");
                    break;
            }
        });
        $form->setTitle($config->getNested("message.ui.title"));
        $form->setContent($config->getNested("message.ui.text"));
        if ($config->getNested("perk.speed.enable") == true) {
            $speed = $config->getNested("perk.speed.button");
            $speed = str_replace("%status%", $this->getStatus($player, "speed"), $speed);
            $form->addButton($speed, -1, "", "speed");
        }
        if ($config->getNested("perk.jump.enable") == true) {
            $jump = $config->getNested("perk.jump.button");
            $jump = str_replace("%status%", $this->getStatus($player, "jump"), $jump);
            $form->addButton($jump, -1, "", "jump");
        }
        if ($config->getNested("perk.haste.enable") == true) {
            $haste = $config->getNested("perk.haste.button");
            $haste = str_replace("%status%", $this->getStatus($player, "haste"), $haste);
            $form->addButton($haste, -1, "", "haste");
        }
        if ($config->getNested("perk.night-vision.enable") == true) {
            $night = $config->getNested("perk.night-vision.button");
            $night = str_replace("%status%", $this->getStatus($player, "night-vision"), $night);
            $form->addButton($night, -1, "", "night");
        }
        if ($config->getNested("perk.no-hunger.enable") == true) {
            $hunger = $config->getNested("perk.no-hunger.button");
            $hunger = str_replace("%status%", $this->getStatus($player, "no-hunger"), $hunger);
            $form->addButton($hunger, -1, "", "hunger");
        }
        if ($config->getNested("perk.no-falldamage.enable") == true) {
            $fall = $config->getNested("perk.no-falldamage.button");
            $fall = str_replace("%status%", $this->getStatus($player, "no-falldamage"), $fall);
            $form->addButton($fall, -1, "", "fall");
        }
        if ($config->getNested("perk.fast-regeneration.enable") == true) {
            $regeneration = $config->getNested("perk.fast-regeneration.button");
            $regeneration = str_replace("%status%", $this->getStatus($player, "fast-regeneration"), $regeneration);
            $form->addButton($regeneration, -1, "", "regeneration");
        }
        if ($config->getNested("perk.keep-inventory.enable") == true) {
            $inventory = $config->getNested("perk.keep-inventory.button");
            $inventory = str_replace("%status%", $this->getStatus($player, "keep-inventory"), $inventory);
            $form->addButton($inventory, -1, "", "inventory");
        }
        if ($config->getNested("perk.dopple-xp.enable") == true) {
            $xp = $config->getNested("perk.dopple-xp.button");
            $xp = str_replace("%status%", $this->getStatus($player, "dopple-xp"), $xp);
            $form->addButton($xp, -1, "", "xp");
        }
        $form->sendToPlayer($player);
        return $form;
    }

    /**
	 * @param Player $player
     * @param string $check
	 */
    public function getStatus(Player $player, string $check) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
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
	 */
    public function getCheckPerk(Player $player, string $check) 
    {
        $config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
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
        }
        $block = ["no-hunger", "no-falldamage", "keep-inventory", "dopple-xp"];
        if ($config->getNested("command.economy-api") == true) {
            $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            $money = $eco->myMoney($player);
            if ($players->get("$check-buy") == true) {
                if ($players->get($check) == true) {
                    $players->set($check, false);
                    if (!in_array($check, $block)) {
                        $player->removeEffect($effect);
                    }
                    $msg = $config->getNested("message.mode.disable");
                    $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                } else {
                    $players->set($check, true);
                    if (!in_array($check, $block)) {
                        $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 1, false));
                    }
                    $msg = $config->getNested("message.mode.enable");
                    $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                    $player->sendMessage($config->getNested("message.prefix") . $msg);
                }
            } else {
                if ($money >= $config->getNested("perk.$check.price")) {
                    $players->set("$check", false);
                    $players->set("$check-buy", true);
                    $buy = $config->getNested("message.buy");
                    $buy = str_replace("%perk%", $config->getNested("perk.$check.msg"), $buy);
                    $buy = str_replace("%money%", $config->getNested("perk.$check.price"), $buy);
                    $player->sendMessage($config->getNested("message.prefix") . $buy);
                    $eco->reduceMoney($player, $config->getNested("perk.$check.price"));
                } else {
                    $player->sendMessage($config->getNested("message.prefix") . $config->getNested("message.no-money"));
                }
            }
        } else {
            if ($config->getNested("perk.$check.perms") !== false) {
                if ($players->get("$check-buy") == true) {
                    if ($players->get($check) == true) {
                        $players->set($check, false);
                        if (!in_array($check, $block)) {
                            $player->removeEffect($effect);
                        }
                        $msg = $config->getNested("message.mode.disable");
                        $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                        $player->sendMessage($config->getNested("message.prefix") . $msg);
                    } else {
                        $players->set($check, true);
                        if (!in_array($check, $block)) {
                            $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 1, false));
                        }
                        $msg = $config->getNested("message.mode.enable");
                        $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                        $player->sendMessage($config->getNested("message.prefix") . $msg);
                    }
                } else {
                    if ($player->hasPermission($config->getNested("perk.$check.perms"))) {
                        if ($players->get($check) == true) {
                            $players->set($check, false);
                            if (!in_array($check, $block)) {
                                $player->removeEffect($effect);
                            }
                            $msg = $config->getNested("message.mode.disable");
                            $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                            $player->sendMessage($config->getNested("message.prefix") . $msg);
                        } else {
                            $players->set($check, true);
                            if (!in_array($check, $block)) {
                                $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 1, false));
                            }
                            $msg = $config->getNested("message.mode.enable");
                            $msg = str_replace("%perk%", $config->getNested("perk.$check.msg"), $msg);
                            $player->sendMessage($config->getNested("message.prefix") . $msg);
                        }
                    } else {
                        $player->sendMessage($config->getNested("message.prefix") . $config->getNested("message.no-perms"));
                    }
                }
            }
        }
        $players->save();
        return true;
    }
}