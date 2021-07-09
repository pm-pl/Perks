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
            }
        });
        $form->setTitle($config->getNested("message.ui.title"));
        $form->setContent($config->getNested("message.ui.text"));
        $speed = $config->getNested("message.ui.speed");
        $speed = str_replace("%status%", $this->getStatus($player, "speed"), $speed);
        $form->addButton($speed, -1, "", "speed");
        $jump = $config->getNested("message.ui.jump");
        $jump = str_replace("%status%", $this->getStatus($player, "jump"), $jump);
        $form->addButton($jump, -1, "", "jump");
        $haste = $config->getNested("message.ui.haste");
        $haste = str_replace("%status%", $this->getStatus($player, "haste"), $haste);
        $form->addButton($haste, -1, "", "haste");
        $night = $config->getNested("message.ui.night-vision");
        $night = str_replace("%status%", $this->getStatus($player, "night-vision"), $night);
        $form->addButton($night, -1, "", "night");
        $hunger = $config->getNested("message.ui.no-hunger");
        $hunger = str_replace("%status%", $this->getStatus($player, "no-hunger"), $hunger);
        $form->addButton($hunger, -1, "", "hunger");
        $fall = $config->getNested("message.ui.no-falldamage");
        $fall = str_replace("%status%", $this->getStatus($player, "no-falldamage"), $fall);
        $form->addButton($fall, -1, "", "fall");
        $regeneration = $config->getNested("message.ui.fast-regeneration");
        $regeneration = str_replace("%status%", $this->getStatus($player, "fast-regeneration"), $regeneration);
        $form->addButton($regeneration, -1, "", "regeneration");
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
        if ($config->getNested("message.ui." . $check . "-perms") !== false) {
            if ($players->get($check) == true) {
                $speedcheck = $config->getNested("message.button.enable");
            } else {
                $speedcheck = $config->getNested("message.button.disable");
            }
        } else {
            $speedcheck = $config->getNested("message.button.no-perms");
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
        if ($config->getNested("message.ui." . $check . "-perms") !== false) {
            if ($players->get($check) == true) {
                $players->set($check, false);
                if ($check !== "no-hunger" and $check !== "no-falldamage") {
                    $player->removeEffect($effect);
                }
            } else {
                $players->set($check, true);
                if ($check !== "no-hunger" and $check !== "no-falldamage") {
                    $player->addEffect(new EffectInstance(Effect::getEffect($effect), 107374182, 1, false));
                }
            }
        } else {
            $player->sendMessage($config->getNested("message.no-perms"));
        }
        $players->save();
        return true;
    }
}