<?php

namespace App;

use App\Services\MarketService;

class Parser extends MarketService {

    const STEAM_MARKET_URL = 'https://steamcommunity.com/market/search?';
    const RUST_GAME_ID = 'appid=252490';

    public function execute(): string
    {
        $result = $this->handleMarketApp(self::STEAM_MARKET_URL,self::RUST_GAME_ID);

        return $result;
    }


}