<?php

namespace App\Services;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\TransportException;

class MarketService
{
    const FILTER_QUANTITY_ASC = '_quantity_asc';
    const PAGE_PREFIX = '#p';

    public HttpBrowser $client;

    public function __construct()
    {
        $this->client = new HttpBrowser();
    }

    public function handleMarketApp(string $marketUrl, $gameId): string
    {
        $homeAppPage = $this->client->request('GET', $marketUrl . $gameId);

        $totalPages = $this->getTotalPages($homeAppPage);

        $i = 1;
        while ($totalPages >= $i) {

            sleep(mt_rand(5, 10));

            $marketPage = $this->client
                ->request('GET', $marketUrl . $gameId . self::PAGE_PREFIX . $i . self::FILTER_QUANTITY_ASC);

            $parsedData = $this->handleMarketPage($marketPage);
            file_put_contents('text.txt', json_encode($parsedData));
            $i++;
        }
        return 'Success!!';
    }

    public function handleMarketPage(Crawler $crawler): array
    {
        $pageItems = $crawler->filter('.market_listing_row_link')->each(function (Crawler $node) {

            sleep(mt_rand(5, 15));

            $link = $node->link();

            $itemPage = $this->client->click($link);

            //handle fields of item
            $itemUri = $itemPage->getUri();

            $itemName = $this->getItemName($itemPage);

            $itemNameId = $this->getItemNameId($itemPage);

            $itemOrders = $this->getItemOrders($itemNameId);

            [$itemOrdersInfo, $itemOrders] = $itemOrders;

            $itemSellsHistory = $this->handleSellsHistory($itemPage->text());

            $itemPageData = [
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

            return $itemPageData;
        });
        echo "Page Parsed" . PHP_EOL;
        return $pageItems;
    }

    public function getItemName(Crawler $crawler): string
    {
        $titleString = $crawler->filter('title')->text();
        $name = explode('for ', $titleString);
        $name = $name[1];

        if (empty($name)) {
            throw new \Exception('NAME is EMPTY!!!');
        }
        return $name;
    }

    public function getItemNameId(Crawler $crawler): string
    {
        $string = explode('Market_LoadOrderSpread( ', $crawler->text());

        $string = explode(' );', $string[1]);

        $itemNameId = $string[0];

        return $itemNameId;
    }

    public function getItemOrders(string $itemNameId): array
    {
        $url = 'https://steamcommunity.com/market/itemordershistogram?country=US&language=english&currency=1&item_nameid=' . $itemNameId . '&two_factor=0';

        sleep(mt_rand(5, 15));

        try {
            $this->client->request('GET', $url, ['timeout' => 1]);

        } catch (TransportException $exception) {
            echo $exception->getMessage();
        }

        $itemOrdersArray = $this->client->getResponse();

        $statusCode = $itemOrdersArray->getStatusCode();

        if ($statusCode != 200) {
            echo 'Status code ' . $statusCode . ' on url ' . $url;
            exit();
        }
        $itemOrdersArray = $itemOrdersArray->toArray();

        $ordersToBuy = $this->deleteTrashFromOrders($itemOrdersArray['buy_order_graph']);

        $ordersToSell = $this->deleteTrashFromOrders($itemOrdersArray['sell_order_graph']);

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
        $total = explode(' ', strip_tags($string), 2);

        return $total[0];
    }

    public function deleteTrashFromOrders(array $orders): array
    {
        $arr = array_map(function ($key) {
            unset($key[2]);
            return $key;
        }
            ,$orders);

        return $arr;
    }

    public function getTotalPages(Crawler $homeAppPage)
    {
        $totalItems = str_replace(',', '', $homeAppPage->filter('#searchResults_total')->text());

        $totalPages = ($totalItems / 10);

        $totalPages = (int)round($totalPages);

        return $totalPages;
    }

    public function handleSellsHistory(string $sellsHistory): array
    {
        $str = explode('line1=[[', $sellsHistory, 2);

        $str = $str[1];

        $datatime = explode(']];', $str);

        $datatime = $datatime[0];

        $charsToReplace = [' +0', '"'];

        $clearString = str_replace($charsToReplace, ['00:00', ""], $datatime);

        $arr = explode('],[', $clearString);

        //array of sells data strings
        foreach ($arr as $string) {

            [$date, $price, $sells] = explode(',', $string);

            [$month, $day, $year, $time] = explode(' ', $date);

            $month = match ($month) {
                'Jan' => '01',
                'Feb' => '02',
                'Mar' => '03',
                'Apr' => '04',
                'May' => '05',
                'Jun' => '06',
                'Jul' => '07',
                'Aug' => '08',
                'Sep' => '09',
                'Oct' => '10',
                'Nov' => '11',
                'Dec' => '12',
            };

            $date = implode('-', [$year, $month, $day]) . ' ' . $time;

            $sellsData[] = [
                'date' => (string)$date,
                'price' => ((double)number_format($price, 2)),
                'sells' => (int)$sells
            ];
        }
        return $sellsData;
    }
}
