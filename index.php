<?php

use Parsers\Interyerus;

require_once __DIR__ . '/vendor/autoload.php';

set_time_limit(0);

// Получение текста файла csv
$interyerus = new Interyerus('https://interyerus.ru/oboi/', Interyerus::OUTPUT_FILE);
$output = $interyerus->run();
file_put_contents(__DIR__ . '/' . date('Ymdhis') . '.csv', $output);

// Получение массива
//$interyerus = new Interyerus('https://interyerus.ru/oboi/', Interyerus::OUTPUT_ARRAY);
//$output = $interyerus->run();
//echo "<pre>";
//print_r($output);
//echo "</pre>";
