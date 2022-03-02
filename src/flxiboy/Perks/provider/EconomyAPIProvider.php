<?php

namespace flxiboy\Perks\provider;

use pocketmine\player\Player;
use onebone\economyapi\EconomyAPI;

/**
 * Class EconomyAPIProvider
 * @package flxiboy\Perks\provider
 */
class EconomyAPIProvider
{

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function addMoney(Player $player, int $amount)
    {
        EconomyAPI::getInstance()->addMoney($player, $amount);
    }

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function reduceMoney(Player $player, int $amount)
    {
        EconomyAPI::getInstance()->reduceMoney($player, $amount);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getMoney(Player $player): int
    {
        return EconomyAPI::getInstance()->myMoney($player);
    }
}