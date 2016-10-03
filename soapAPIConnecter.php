<?php //=================================SOAP==================================
ini_set("soap.wsdl_cache_enabled", "0");//Отключение кеширования wsdl

$wsdlUrl = 'https://api.direct.yandex.ru/live/v4/wsdl/';
$token = '6edd40b56d63400c9cd9f88666fc3645';
$locale = 'ru';

$soapClient = new SoapClient($wsdlUrl,
    array(
        "trace" => true,
        "exceptions" => false,
        "encoding" => "UTF-8"
    )
);

//Формирование заголовков
$soapClient->__setSoapHeaders(
    array(
        new SoapHeader("API", "token", $token, false),
        new SoapHeader("API", "locale", $locale, false)
    )
);

//Входные данные запроса
$params = array(
    "CampaignIDS" => array(9166900),
    "StartDate" => "2015-01-15",
    "EndDate" => "2015-08-18",
    "Currency" => "RUB",
    "IncludeVAT" => "Yes"
);

    // Выполнение запроса к серверу API Директа
    $result = $soapClient->GetSummaryStat($params);

echo "<pre>";
var_dump($result);
echo "</pre>";

    // Вывод запроса и ответа
    //echo "Запрос:<pre>".htmlspecialchars($soapClient->__getLastRequest()) ."</pre>";
    //echo "Ответ:<pre>".htmlspecialchars($soapClient->__getLastResponse())."</pre>";

    // Вывод отладочной информации в случае возникновения ошибки
    /*if (is_soap_fault($result)) { echo("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring}, detail: {$result->detail})"); }


    //var_dump($soapClient->getVersion());
    //var_dump($soapClient->__getFunctions());
    echo"<table class=\"table table-striped table-bordered table-hover table-condensed\">";
    echo"<tr>
            <td>Идентификатор кампании.</td>
            <td>Дата, за которую приведена статистика</td>
            <td>Стоимость кликов на поиске</td>
            <td>Стоимость кликов в РСЯ</td>
            <td>Кол-во показов в РСЯ</td>
            <td>Кол-во показов на поиске</td>
            <td>Кол-во кликов на поиске</td>
            <td>Кол-во кликов в РСЯ</td>
            <td>Глубина просмотра сайта при переходе с поиска</td>
            <td>Глубина просмотра сайта при переходе с РСЯ</td>
            <td>Доля целевых визитов в общем числе визитов при переходе с поиска, %</td>
            <td>Доля целевых визитов в общем числе визитов при переходе с РСЯ ,%</td>
            <td>Цена достижения цели яндекс метрики при переходе с поиска</td>
            <td>цена достижения цели Я.метрики при переходе с РСЯ</td>
         </tr>";

    for ($i = 0; $i < count($result) - 1; $i++)
    {

        $myrow = (array) $result[$i];
        printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
            $myrow["CampaignID"], $myrow["StatDate"],
            $myrow["SumSearch"], $myrow["SumContext"],
            $myrow["ShowsSearch"], $myrow["ShowsContext"],
            $myrow["ClicksSearch"], $myrow["ClicksContext"],
            $myrow["SessionDepthSearch"], $myrow["SessionDepthContext"],
            $myrow["GoalConversionSearch"], $myrow["GoalConversionContext"],
            $myrow["GoalCostSearch"], $myrow["GoalCostContext"]);
    }
    echo"</table>";*/