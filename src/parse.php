<?php

require_once '../vendor/autoload.php';


use Symfony\Component\BrowserKit\HttpBrowser;

$client = new HttpBrowser();




try {
    $crawler = $client->request('GET', 'https://steamcommunity.com/market/listings/252490/Tempered%20AK47');
} catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
}

$datastring = $crawler->text();

$str = explode('line1=[[',$datastring,2);

$str = $str[1];

$datatime = explode(']];', $str);

$datatime = $datatime[0];

$charsToReplace = [' +0', '"'];

$clearString = str_replace($charsToReplace,['00:00',""],$datatime);

$arr = explode('],[',$clearString);

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

    $resultArr[] = [
        'date' => (string)$date,
        'price' => ((double)number_format($price, 2)),
        'sells' => (int)$sells
    ];
}





//var_dump(number_format($price,2));
//$str = explode(';',$str,2);

//$str = $str[2];
//echo $str;
//exit();

file_put_contents('text.txt',json_encode($resultArr));
//dd($crawler->filterXPath('//script[@type]'));


echo "success";