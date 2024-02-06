<?php

namespace App;

use Symfony\Component\BrowserKit\HttpBrowser;
use App\Traits\SellsHandler;
use Symfony\Component\DomCrawler\Crawler;

class Parser {

    use SellsHandler;

    const  RUST_GAME_URL = 'https://steamcommunity.com/market/search?appid=252490';

    public HttpBrowser $client;


    public function __construct()
    {
       $this->client = new HttpBrowser();
    }


    public function execute()
    {
        $parsedData = $this->handleMarketApp(self::RUST_GAME_URL);

        dd($parsedData);
    }

    public function handleMarketApp(string $appUrl)
    {
        $homeAppPage = $this->client->request('GET',$appUrl);

        dd($this->handleItemPages($homeAppPage));
    }

    public function handleItemPages(Crawler $crawler)
    {
        $filterPage  = $crawler->filter('.market_listing_row_link')->each(function(Crawler $node){

            $link = $node->link();

            $itemPage = $this->client->click($link);

            //handle fields of item


            $itemUri = $itemPage->getUri();

            $itemName = $this->getItemName($itemPage);

            $itemNameId = $this->getItemNameId($itemPage);

            $itemOrders = $this->getItemOrders($itemNameId);

            [$itemOrdersInfo,$itemOrders] = $itemOrders;

            $itemSellsHistory = $this->handleSellsHistory($itemPage->text());

            echo "Iteration  ";

            $itemPageData[] = [
                'name' => $itemName,
                'highest_order_to_buy' => $itemOrdersInfo['highest_order_to_buy'],
                'lowest_order_to_sell' => $itemOrdersInfo['lowest_order_to_sell'],
                'total_orders_to_buy' => $itemOrdersInfo['total_orders_to_buy'],
                'total_orders_to_sell' => $itemOrdersInfo['total_orders_to_sell'],
                'url' => $itemUri,
                'item_name_id' => $itemNameId,
                'item_orders' => json_encode($itemOrders),
                'sells_history' => json_encode($itemSellsHistory),
            ];

            dd($itemPageData);
        });
        dd($filterPage);
        return $itemPageData;
    }

    public function getItemName(Crawler $crawler): string
    {
        return $crawler->filter('.market_listing_item_name')->text();
    }

    public function getItemNameId(Crawler $crawler): string
    {
        $string = explode('Market_LoadOrderSpread( ',$crawler->text());
         $string  = explode(' );',$string[1]);
         $itemNameId = $string[0];

         return $itemNameId;
    }

    public function getItemOrders(string $itemNameId): array
    {
        $url = 'https://steamcommunity.com/market/itemordershistogram?country=US&language=english&currency=1&item_nameid=' . $itemNameId . '&two_factor=0';

        $this->client->request('GET',$url);

        $itemOrdersArray = $this->client->getResponse()->toArray();

        $ordersToBuy = $this->deleteTrashFromOrders($itemOrdersArray['buy_order_graph']);

        $ordersToSell = $this->deleteTrashFromOrders($itemOrdersArray['sell_order_graph']);

        //0 =>
        $itemOrders = [
            ['highest_order_to_buy' => $ordersToBuy[0][0],
            'lowest_order_to_sell' => $ordersToSell[0][0],
            'total_orders_to_buy' => (int)$this->handleTotalOrdersString($itemOrdersArray['buy_order_summary']),
            'total_orders_to_sell' => (int)$this->handleTotalOrdersString($itemOrdersArray['sell_order_summary']),],

            ['orders_to_buy' => $ordersToBuy,
            'orders_to_sell' => $ordersToSell,]
        ];

        return $itemOrders;
    }

    public function handleTotalOrdersString(string $string): string
    {
       $total = explode(' ',strip_tags($string), 2);

        return $total[0];
    }

    public function deleteTrashFromOrders(array $orders): array
    {
       $arr = array_map(function ($key){
                unset($key[2]);
                  return $key;
           }
           ,$orders);

       return $arr;
    }
}