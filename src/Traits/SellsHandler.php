<?php

namespace App\Traits;

trait SellsHandler
{
    public function handleSellsHistory(string $sellsHistory): array
    {
        $str = explode('line1=[[',$sellsHistory,2);

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

            $sellsData[] = [
                'date' => (string)$date,
                'price' => ((double)number_format($price, 2)),
                'sells' => (int)$sells
            ];
        }
        return $sellsData;
    }


}