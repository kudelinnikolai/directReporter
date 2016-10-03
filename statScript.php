<?//Удаляем отчеты с прошлого месяца

?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Формирование отчетов</title>

    <!-- Font Awesome -->
    <link href="public/css/font-awesome.css" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="public/css/bootstrap.css" rel="stylesheet">

    <!--Include the latest AJAX library-->
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>

    <!--Include MY JavaScript scripts-->
    <script src="ajaxJs/formValidater.js" defer></script>
</head>

<body>

<div class="container" style="margin-top: 20px">

<?php
//print_r($_POST);
$AllIsBad = false;
$Strings = array();

//Обязательные параметры
$CampsID = $_POST['CampID'];
$DateFrom = $_POST['DateFrom'];
$DateTo = $_POST['DateTo'];
$CounterID = $_POST['CounterID'];
$Directory = $_POST['Directory'];
$Сurrency = $_POST['Сurrency'];
$IncludeVAT = $_POST['IncludeVAT'];
$IncludeDiscount = $_POST['IncludeDiscount'];
$Token = $_POST['Token'];
$Login = $_POST['Login'];

//Необязательные параметры
if($_POST['Role'] == "Agency"){$ClientLogin = $_POST['ClientLogin'];}
$Role = $_POST['Role'];

?>
    <?
    $metrika_url = "https://api-metrika.yandex.ru/counter/".$CounterID."/goals.json?pretty=1&oauth_token=".$Token;

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL,$metrika_url);
    //curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $metrikas = curl_exec ($ch);
    $metrikas = json_decode($metrikas);

    curl_close($ch);

    //print_r($metrikas);

    if(isset($metrikas->goals[0]->id)){
        $targetID = $metrikas->goals[0]->id;//ТОЛЬКО СЛУЧАЙ, КОГДА У СЧЕТЧИКА ОДНА ЦЕЛЬ
        require "statWithTarget.php";
    } else {
        require "statWithoutTarget.php";
    }
    
require_once 'Classes/PHPExcel.php'; //ПОДКЛЮЧЕНИЕ ГЛАВНОГО ФАЙЛА БИБЛИОТЕКИ

//=====================SOAP==============    СИЛЬНО МНОГО ВЕСИТ, ЛУЧШЕ ИСПОЛЬЗОВАТЬ JSON
//require("soapAPIConnecter.php");

//=====================JSON==============
$direct_url = "https://api.direct.yandex.ru/live/v4/json/";

//столбцы агрегированной статистики
$shows = array();
$clicks = array();
$CTR = array();
$consumption = array();
$avgConsumptionPerDay = array();
$avgClicksCoast = array();
$conversion = array();
$conversionPercent = array();
$targetCoast = array();

$iterateCamps = 0;
foreach ($CampsID as $item) {

    $ch = curl_init();
    $post_data = array("param" =>array(
        "CampaignIDS" => array($item),
        "StartDate" => $DateFrom,
        "EndDate" => $DateTo,
        "Currency" => $Сurrency,
        "IncludeVAT" => $IncludeVAT,
        "IncludeDiscount" => $IncludeDiscount
    ),
        "method" => "GetSummaryStat",
        "token" => $Token,
        "locale" => "ru"
    );

    $post_data = json_encode($post_data);

    curl_setopt ($ch, CURLOPT_URL,$direct_url);
//curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $stat = curl_exec ($ch);
    curl_close($ch);

    $stat_o = (array) json_decode($stat)->data;

    $showsSum = 0;
    $clicksSum = 0;
    $consumptionSum = 0;
    for ($i = 0; $i < count($stat_o); $i++)
    {
        $myrow = (array) $stat_o[$i]; //преобразование из объекта в массив

        $showsSum += $myrow["ShowsSearch"] + $myrow["ShowsContext"];
        $clicksSum += $myrow["ClicksSearch"] + $myrow["ClicksContext"];
        $consumptionSum += $myrow["SumSearch"] + $myrow["SumContext"];
    }

    if ($showsSum == 0) { //переходим к след. кампании, если показов нет
        unset( $campWithName[$item] );
        $campWithoutShow[] = $item;
        continue;
    }

    if (array_key_exists ( $item , $campWithStat )){
        $val = $campWithStat[$item];
    } else {
        $val = 0;
        $CampsNoStat[] = $iterateCamps; //чтобы потом раскрасить ячейки в желтый
    }

    $datetime1 = date_create($DateFrom);
    $datetime2 = date_create($DateTo);
    $interval = date_diff($datetime1, $datetime2);

    $CTR[] = ($showsSum == 0)?0:$clicksSum/$showsSum;
    $avgConsumptionPerDay[] = ($interval->days == 0)?0:$consumptionSum/$interval->days;
    $avgClicksCoast[] = ($clicksSum == 0)?0:$consumptionSum/$clicksSum;
    $conversion[] = $val;
    $conversionPercent[] = ($clicksSum == 0)?0:$val/$clicksSum;
    $targetCoast[] = ($val == 0)?0:$consumptionSum/$val;
    $shows[] = $showsSum;
    $clicks[] = $clicksSum;
    $consumption[] = $consumptionSum;

    $iterateCamps += 1;
}

    /*echo "<br>";
    var_dump($campWithName);
    echo "<br>";echo "<hr>";
    var_dump($campWithoutShow);
    echo "<br>";echo "<hr>";
    echo "$iterateCamps";*/

$totalShows = array_sum($shows);
$totalClicks = array_sum($clicks);
$totalCTR = ($totalShows == 0)?0:$totalClicks/$totalShows;
$totalConsumption = array_sum($consumption);
$totalAvgConsumptionPerDay = ($interval->days == 0)?0:$totalConsumption/$interval->days;
$totalAvgClicksCoast = ($totalClicks == 0)?0:$totalConsumption/$totalClicks;
$totalConversion = array_sum($conversion);
$totalConversionPercent = ($totalClicks == 0)?0:$totalConversion/$totalClicks;
$totalTargetCoast = ($totalConversion == 0)?0:$totalConsumption/$totalConversion;

/*if(isset($CampsWithoutConversion)){ //сбрасываем агрегированную статистику
    $totalConversion = 0;
    $totalConversionPercent = 0;
    $totalTargetCoast = 0;
}*/

$pExcel = new PHPExcel();

$pExcel->setActiveSheetIndex(0);
$aSheet = $pExcel->getActiveSheet();

// Ориентация страницы и  размер листа
$aSheet->getPageSetup()
    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
$aSheet->getPageSetup()
    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

// Название листа
$aSheet->setTitle(str_replace("-",".",$DateFrom)." - ".str_replace("-",".",$DateTo));//???????????????????????????????????????????????????????????????????????
// Шапка и футер (при печати)
$aSheet->getHeaderFooter()
    ->setOddHeader('&CЯндекс.Директ: '.(isset($ClientLogin))?$ClientLogin:$Login);
$aSheet->getHeaderFooter()
    ->setOddFooter('&L&B'.$aSheet->getTitle().'&RСтраница &P из &N');
// Настройки шрифта
$pExcel->getDefaultStyle()->getFont()->setName('Arial');
$pExcel->getDefaultStyle()->getFont()->setSize(10);

//http://tokmakov.msk.ru/blog/psts/7/
$aSheet->mergeCells('A1:K1');
$aSheet->setCellValue('A1','Яндекс.Директ');
$aSheet->mergeCells('A2:K2');
$aSheet->setCellValue('A2','Клиент: '.((isset($ClientLogin))?$ClientLogin:$Login).", период: ".(str_replace("-",".",$DateFrom))." - ".(str_replace("-",".",$DateTo)));
$aSheet->mergeCells('A3:B3');
$aSheet->setCellValue('A3','Всего');
$aSheet->mergeCells('A4:B4');
$aSheet->setCellValue('A4',"C ".(str_replace("-",".",$DateFrom))." по ".(str_replace("-",".",$DateTo)));

$aSheet->setCellValue('C3','Ср. расход за день('.$Сurrency.')');
$aSheet->setCellValue('D3','Показы');
$aSheet->setCellValue('E3','Клики');
$aSheet->setCellValue('F3','CTR (%)');
$aSheet->setCellValue('G3','Расход('.$Сurrency.')');
$aSheet->setCellValue('H3','Ср. цена клика('.$Сurrency.')');
$aSheet->setCellValue('I3','Конверсии');
$aSheet->setCellValue('J3','Конверсия (%)');
$aSheet->setCellValue('K3','Цена цели('.$Сurrency.')');

$aSheet->setCellValue('C4', $totalAvgConsumptionPerDay);
    $aSheet->getStyle('C4')->getNumberFormat()
    ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
$aSheet->setCellValue('D4', $totalShows);
    $aSheet->getStyle('D4')->getNumberFormat()
    ->setFormatCode('0');
$aSheet->setCellValue('E4', $totalClicks);
    $aSheet->getStyle('E4')->getNumberFormat()
    ->setFormatCode('0');
$aSheet->setCellValue('F4', $totalCTR);
    $aSheet->getStyle('F4')->getNumberFormat()
    ->setFormatCode('0.00%');
$aSheet->setCellValue('G4', $totalConsumption);
    $aSheet->getStyle('G4')->getNumberFormat()
    ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
$aSheet->setCellValue('H4', $totalAvgClicksCoast);
    $aSheet->getStyle('H4')->getNumberFormat()
    ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
$aSheet->setCellValue('I4', $totalConversion);
    $aSheet->getStyle('I4')->getNumberFormat()
    ->setFormatCode('0');
$aSheet->setCellValue('J4', $totalConversionPercent);
    $aSheet->getStyle('J4')->getNumberFormat()
    ->setFormatCode('0.00%');
$aSheet->setCellValue('K4', $totalTargetCoast);
    $aSheet->getStyle('K4')->getNumberFormat()
    ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);

$aSheet->setCellValue('A6', "Дата");
$aSheet->setCellValue('B6', "Кампания");
$aSheet->setCellValue('C6', "№ Кампании");
$aSheet->setCellValue('D6', "Показы");
$aSheet->setCellValue('E6', "Клики");
$aSheet->setCellValue('F6', "CTR (%)");
$aSheet->setCellValue('G6','Расход('.$Сurrency.')');
$aSheet->setCellValue('H6','Ср. цена клика('.$Сurrency.')');
$aSheet->setCellValue('I6','Конверсии');
$aSheet->setCellValue('J6','Конверсия (%)');
$aSheet->setCellValue('K6','Цена цели('.$Сurrency.')');

function get_excel_valid_date($dateFrom, $dateTo){
    $datetime1 = date_create($dateFrom);
    $datetime2 = date_create($dateTo);
    $interval = date_diff($datetime1, $datetime2);

    if ($interval->days >= 20){
        if ((int)substr($dateFrom, 8, 2) >= 27){
            $month = (int)substr($dateFrom, 5, 2) + 1;
        } else {
            $month = (int)substr($dateFrom, 5, 2);
        }
    } else {
        $month = (int)substr($dateFrom, 5, 2);
    }
    $months_names_arr = array(
        "1" => "Январь",
        "2" => "Февраль",
        "3" => "Март",
        "4" => "Апрель",
        "5" => "Май",
        "6" => "Июнь",
        "7" => "Июль",
        "8" => "Август",
        "9" => "Сентябрь",
        "10" => "Октябрь",
        "11" => "Ноябрь",
        "12" => "Декабрь",
    );
    $strResult = $months_names_arr[(string)$month]." ".substr($dateFrom, 0, 4);

    return $strResult;
}

$dateAtExcel = get_excel_valid_date($DateFrom, $DateTo);
for ($i = 0; $i < count($campWithName); $i++){
    $aSheet->setCellValue('A'.(7+$i), $dateAtExcel);
    $aSheet->setCellValue('B'.(7+$i), array_values($campWithName)[$i]);
    $aSheet->setCellValue('C'.(7+$i), array_keys($campWithName)[$i]);
    $aSheet->setCellValue('D'.(7+$i), $shows[$i]);
        $aSheet->getStyle('D'.(7+$i))->getNumberFormat()
        ->setFormatCode('0');
    $aSheet->setCellValue('E'.(7+$i), $clicks[$i]);
        $aSheet->getStyle('E'.(7+$i))->getNumberFormat()
        ->setFormatCode('0');
    $aSheet->setCellValue('F'.(7+$i), $CTR[$i]);
        $aSheet->getStyle('F'.(7+$i))->getNumberFormat()
        ->setFormatCode("0.00%");
    $aSheet->setCellValue('G'.(7+$i), $consumption[$i]);
        $aSheet->getStyle('G'.(7+$i))->getNumberFormat()
        ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
    $aSheet->setCellValue('H'.(7+$i), $avgClicksCoast[$i]);
        $aSheet->getStyle('H'.(7+$i))->getNumberFormat()
        ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
    $aSheet->setCellValue('I'.(7+$i), $conversion[$i]);
        $aSheet->getStyle('I'.(7+$i))->getNumberFormat()
        ->setFormatCode('0');
    $aSheet->setCellValue('J'.(7+$i), $conversionPercent[$i]);
        $aSheet->getStyle('J'.(7+$i))->getNumberFormat()
        ->setFormatCode("0.00%");
    $aSheet->setCellValue('K'.(7+$i), $targetCoast[$i]);
        $aSheet->getStyle('K'.(7+$i))->getNumberFormat()
        ->setFormatCode(PHPExcel_Style_NumberFormat::MY_RUB_FORMAT);
}

//Задаем цвета
$softGreen = array(
	'fill' => array(
		'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color'   => array(
            'rgb' => 'eeffe7'
        )
	),
);
$midGreen = array(
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color'   => array(
            'rgb' => 'ccff99'
        )
    ),
);
$hardGreen = array(
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color'   => array(
            'rgb' => '92d050'
        )
    ),
);
$softYellow = array(
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color'   => array(
            'rgb' => 'fcfbb1'
        )
    ),
);

$aSheet->getStyle('A1:K5')->applyFromArray($softGreen);
$aSheet->getStyle('A3:K3')->applyFromArray($hardGreen);
$aSheet->getStyle('A6:K6')->applyFromArray($hardGreen);
$aSheet->getStyle('A4:K4')->applyFromArray($midGreen);
$aSheet->getStyle('A7:K'.(6+$i))->applyFromArray($midGreen);
    
if ($AllIsBad){
    $aSheet->getStyle('I7:K'.(6+$i))->applyFromArray($softYellow);
    $aSheet->getStyle('I4:K4')->applyFromArray($softYellow);
}

if(isset($CampsNoStat)){
    foreach ($CampsNoStat as $item){
        $aSheet->getStyle('I'.(7+$item).':K'.(7+$item))->applyFromArray($softYellow);
    }

    $aSheet->getStyle('I4:K4')->applyFromArray($softYellow);
}

//Задаем параметры текста
$h1Font = array(
    'font' => array(
        'bold' => true,
        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,
        'size' => 14,
    ),
);
$h2Font = array(
    'font' => array(
        'bold' => true,
        'italic' => true,
        'size' => 12,
    ),
);
$boldFont = array(
    'font' => array(
        'bold' => true,
    ),
);
$aSheet->getStyle('A1')->applyFromArray($h1Font);
$aSheet->getStyle('A2')->applyFromArray($h2Font);
$aSheet->getStyle('A3:K3')->applyFromArray($boldFont);
$aSheet->getStyle('A4')->applyFromArray($boldFont);
$aSheet->getStyle('A6:K6')->applyFromArray($boldFont);

//Задаем выравнивание
$aSheet->getStyle('A1:A2')
    ->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$aSheet->getStyle('A1:A2')
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

//Задаем рамку
$borderAll = array(
    // рамки
    'borders'=>array(
        // внешняя рамка
        'outline' => array(
            'style'=>PHPExcel_Style_Border::BORDER_THIN,
            'color' => array(
                'rgb'=>'000000'
            )
        ),
        // внутренняя
        'allborders'=>array(
            'style'=>PHPExcel_Style_Border::BORDER_THIN,
            'color' => array(
                'rgb'=>'000000'
            )
        )
    )
);
$borderRight = array(
    // рамки
    'borders'=>array(
        'right'=>array(
            'style'=>PHPExcel_Style_Border::BORDER_THIN,
            'color' => array(
                'rgb'=>'000000'
            )
        )
    )
);
$aSheet->getStyle('A3:K4')->applyFromArray($borderAll);
$aSheet->getStyle('A6:K'.(6 + $i))->applyFromArray($borderAll);

$aSheet->getStyle('K1:K'.(6 + $i))->applyFromArray($borderRight);

// Задаем ширину столбцов
$aSheet->getColumnDimension('A')->setWidth(18);
$aSheet->getColumnDimension('B')->setAutoSize(true);
$aSheet->getColumnDimension('C')->setAutoSize(true);
$aSheet->getColumnDimension('D')->setWidth(12);
$aSheet->getColumnDimension('E')->setWidth(12);
$aSheet->getColumnDimension('F')->setWidth(12);
$aSheet->getColumnDimension('G')->setWidth(18);
$aSheet->getColumnDimension('H')->setAutoSize(true);
$aSheet->getColumnDimension('I')->setWidth(12);
$aSheet->getColumnDimension('J')->setAutoSize(true);
$aSheet->getColumnDimension('K')->setAutoSize(true);

//Высота строк
$aSheet->getRowDimension('1')->setRowHeight(25);

//Сохраняем
$objWriter = new PHPExcel_Writer_Excel2007($pExcel);
$objWriter->save("excels/".(isset($ClientLogin)?$ClientLogin:$Login)."_".$DateFrom."_".$DateTo.".xls");

/*if (isset($CampsWithoutConversion)){
    echo "<hr><p><b>Данные по конверсиям у кампаний со следующими идентефикаторами надо проверить отдельно:</b></p><br>";
    foreach ($CampsWithoutConversion as $item){
        echo "<p>$item<p>";
    }
}*/
    if (isset($campWithoutShow)){
        echo "<hr><p><b>Данные по кампаниям со следующими идентефикаторами не учитывались, т.к по ним не было ни одного показа за отчетный период:</b></p><br>";
        foreach ($campWithoutShow as $item){
            echo "<p>$item</p>";
        }
    }
?>
    <div class="jumbotron">
        <a href="<? echo "excels/".(isset($ClientLogin)?$ClientLogin:$Login)."_".$DateFrom."_".$DateTo.".xls";?>" ><button type="button" class="btn btn-success btn-lg">Cкачать отчет</button></a>
        <a href="index.php" ><button type="button" class="btn btn-primary btn-lg">Назад</button></a>
    </div>
</div>


</body>