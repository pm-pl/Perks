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
        $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            $checkPerk = new API($this->plugin, $player);
            $checkPerk->getCheckPerk($player, $data);
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
        foreach (["speed", "jump", "haste", "night-vision", "no-hunger", "no-falldamage", "fast-regeneration", "keep-inventory", "dopple-xp", "strength", "no-firedamage", "fly"] as $name) {
            if ($config->getNested("perk.$name.enable") == true) {
                if ($config->getNested("perk.$name.img") !== false) {
                    if (strpos($config->getNested("perk.$name.img"), "textures/") !== false) {
                        $picture = 0;
                    } else {
                        $picture = 1;
                    }
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
        $players = new Config($this->plugin->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createCustomForm(function (Player $player, $data = null) { 
            if ($data === null) {
                return; 
            }
            if (isset($this->plugin->playernewperkname[$player->getName()])) {
                $check = $this->plugin->playernewperkname[$player->getName()];
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
                }
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
        if ($player->hasEffect($effect)) {
            $form->addStepSlider($config->getNested("message.strength.text"), ["0", "1", "2", "3", "4", "5"], $player->getEffect($effect)->getEffectLevel());
        } else {
            $players->set($check, false);
            $players->save();
            $form->addLabel($config->getNested("message.strength.error"));
        }
        $form->sendToPlayer($player);
        return $form;
    }
}