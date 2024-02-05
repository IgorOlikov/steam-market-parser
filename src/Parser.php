<?php



namespace App;

use Symfony\Component\BrowserKit\HttpBrowser;
use App\Traits\SellsHandler;

class Parser {

    use SellsHandler;

    const  RUST_GAME_URL = 'https://steamcommunity.com/market/search?appid=252490';

    public HttpBrowser $client;


    public function __construct()
    {
       $this->client = new HttpBrowser();
    }


    public function clicker()
    {
        $crawler = $this->client->request('GET',self::RUST_GAME_URL);

        dd($crawler->links());
        //$this->client->click()

    }



    public function execute(): array
    {
        $crawler = $this->client->request('GET', 'https://steamcommunity.com/market/listings/252490/Tempered%20AK47');




        $sellsHistory = $crawler->text();
        $sellsHistory = $this->handleSellsHistory($sellsHistory);


        return $sellsHistory;
    }
}