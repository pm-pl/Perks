<?php

namespace flxiboy\Perks\provider;

use pocketmine\player\Player;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

/**
 * Class BedrockEconomyProvider
 * @package flxiboy\Perks\provider
 */
class BedrockEconomyProvider
{

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function addMoney(Player $player, int $amount)
    {
        BedrockEconomyAPI::getInstance()->addToPlayerBalance(strtolower($player->getName()), $amount);
    }

    /**
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function reduceMoney(Player $player, int $amount)
    {
        BedrockEconomyAPI::getInstance()->subtractFromPlayerBalance(strtolower($player->getName()), $amount);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getMoney(Player $player): int
    {
        # ToDo: This is bad. :c
        BedrockEconomyAPI::getInstance()->getPlayerBalance(
            strtolower($player->getName()),
            ClosureContext::create(
                function (?int $balance) {
                    return $balance;
                },
            )
        );
        return 0;
    }
}