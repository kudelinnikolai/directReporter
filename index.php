<?php

$dateNow = getdate();
$dateLastWeek = getdate();

$dateLastWeek = strtotime("-7 day");
$dateNow = strtotime("0 day");
$strDateLastWeek = date("Y-m-d", $dateLastWeek);
$strDateNow = date("Y-m-d", $dateNow);

if( isset( $_POST['code'] ) ) //если страница вернула запрос на себя с параметром code - запрашиваем токен
{
    $query = array(
        "grant_type" => "authorization_code",
        "code" => $_POST['code'],
        "client_id" => "adf2c692868d49f7abab7b9fe9ad733b",
        "client_secret" => "00f7181951904add9d26e7fccfee664c"
    );
    $query = http_build_query($query);

    // Формирование заголовков POST-запроса
    $header = "Content-type: application/x-www-form-urlencoded";

    // Выполнение POST-запроса и вывод результата
    $opts = array('http' =>
        array(
            'method' => 'POST',
            'header' => $header,
            'content' => $query
        )
    );
    $context = stream_context_create($opts);
    $result = file_get_contents('https://oauth.yandex.ru/token', false, $context);

    $result = json_decode($result);

    if ($result->access_token != null) {
        $Token = $result->access_token;
    }
}

if( isset( $_POST['Token'] ) ){
    $Token = $_POST['Token'];
}
if( isset( $_POST['Login'] ) ){
    $Login = $_POST['Login'];
}

if( isset( $_POST['Role'] ) ){
    $Role = $_POST['Role'];
} else {
    //========================АГЕНСТСТВО или нет==================
    $direct_url = "https://api.direct.yandex.ru/live/v4/json/";
    $ch = curl_init();

    $post_data = array(
        "method" => "GetClientInfo",
        "token" => $Token,
        "locale" => "ru"
    );

    $post_data = json_encode($post_data);

    curl_setopt ($ch, CURLOPT_URL,$direct_url);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $clientInfo = curl_exec ($ch);
    curl_close($ch);

    $clientInfo_o = json_decode($clientInfo);
    $Login = $clientInfo_o->data[0]->Login;
    $Role = $clientInfo_o->data[0]->Role; //Client/Agency
}

if( isset( $_POST['ClientLogin'] ) ){
    $ClientLogin = $_POST['ClientLogin'];
}

{ //берем первую кампанию клиента и смотрим её счетчик
    //Первая кампания клиента
    $direct_url = "https://api.direct.yandex.ru/live/v4/json/";
    $ch = curl_init();
    $paramClient = ( (isset($ClientLogin))?$ClientLogin:$Login );

    $post_data = array(
        "param" =>array(
            "Logins" => array($paramClient),
            "Filter" => array(
                "StatusArchive" => array("No"),
            )
        ),
        "method" => "GetCampaignsListFilter",
        "token" => $Token,
        "locale" => "ru"
    );

    $post_data = json_encode($post_data);

    curl_setopt ($ch, CURLOPT_URL,$direct_url);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $camps = curl_exec ($ch);
    curl_close($ch);

    if (isset(json_decode($camps)->data[0]->CampaignID)){
        $firstCampID = json_decode($camps)->data[0]->CampaignID;//->data;
    }

    //Последний счетчик в кампании
    if(isset($firstCampID)){
        $direct_url = "https://api.direct.yandex.ru/live/v4/json/";
        $ch = curl_init();
        $paramClient = ( (isset($ClientLogin))?$ClientLogin:$Login );

        $post_data = array(
            "param" =>array(
                "CampaignIDS" => array($firstCampID),
                "Currency" => "RUB",
            ),
            "method" => "GetCampaignsParams",
            "token" => $Token,
            "locale" => "ru"
        );

        $post_data = json_encode($post_data);

        curl_setopt ($ch, CURLOPT_URL,$direct_url);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $backData = curl_exec ($ch);
        curl_close($ch);

        $backData = json_decode($backData);
        $lastIndex = count($backData->data[0]->AdditionalMetrikaCounters) - 1;
        $selectedCounter = $backData->data[0]->AdditionalMetrikaCounters[$lastIndex];
    } else{
        $selectedCounter = "000";
    }
}

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

    <!-- Chosen -->
    <link rel="stylesheet" href="public/chosen/chosen.css">

    <!--Include the latest AJAX library-->
    <!--script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script-->
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>

    <!--Include MY JavaScript scripts-->
    <script src="ajaxJs/formValidater.js" defer></script>
</head>

<body>

<div class="modal fade" id="modal-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close" type="button" data-dismiss="modal">
                    <i class="fa fa-close"></i>
                </button>
                <h4 class="modal-title">Вход</h4>
            </div>
            <div class="modal-body">
                <form method="post">
                    <div class="form-group">
                        <p>Получите пароль по <a href="https://oauth.yandex.ru/authorize?response_type=code&client_id=adf2c692868d49f7abab7b9fe9ad733b" target="_blank">ССЫЛКЕ</a> и введите его в поле формы.
                            <br>
                            <sub>Время жизни предоставленного кода — 10 минут. По истечении этого времени код нужно запросить заново.</sub>
                        </p>

                        <label class="control-label" for="yaPass">Пароль:</label>
                        <input type="password" name="code" class="form-control" id="yaPass" required placeholder="введите пароль"><br>

                        <button class="btn btn-success btn-lg" name="submit" type="submit" id="close"> Войти </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" type="button" data-dismiss="modal">Закрыть окно</button>
            </div>
        </div>
    </div>
</div>

<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Сделать отчет</a>
        </div>
        <div class="navbar-collapse collapse navbar-right">

            <button class="btn btn-primary navbar-btn navbar-right" type="button" data-toggle="modal" data-target="#modal-1">Войти</button>

            <? if($Role ==="Agency"):?>
            <form class="navbar-form navbar-right" action="" role="form" method="post">

                <input type="hidden" name="Role" value="Agency">
                <input type="hidden" name="Token" value="<?echo $Token?>">
                <input type="hidden" name="Login" value="<?echo $Login?>">

                <div class="input-group">
                    <div class="input-group-btn">
                        <? if(isset($ClientLogin)):?>
                        <button type="submit" class="btn btn-success">Изменить клиента</button>
                        <? else: ?>
                        <button type="submit" class="btn btn-danger">Выбрать клиента</button>
                        <? endif?>
                    </div>
                    <select class="form-control chzn-select" name="ClientLogin" required>
                        <?
                        //вывод всех клиентов кампании
                        $direct_url = "https://api.direct.yandex.ru/live/v4/json/";
                        $ch = curl_init();

                        $post_data = array("param" =>array(
                            "Filter" => array(
                                "StatusArch" => "No"
                            )
                        ),
                            "method" => "GetClientsList",
                            "token" => $Token,
                            "locale" => "ru"
                        );

                        $post_data = json_encode($post_data);

                        curl_setopt ($ch, CURLOPT_URL,$direct_url);
                        curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
                        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
                        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        $clients = curl_exec ($ch);
                        curl_close($ch);

                        $clients_o = json_decode($clients)->data;

                        foreach ($clients_o as $cl){
                            $login = $cl->Login;
                            $clSort[$login] = $cl->FIO;
                        }

                        asort($clSort);
                        foreach ($clSort as $login=>$fio){
                            $isSelected = ($login == $ClientLogin)?"selected=\"selected\"":"";
                            printf("<option value=\"%s\" %s>%s</option>", $login, $isSelected, $fio);
                        }
                        ?>
                    </select>
                </div>
            </form>

            <? endif ?>

            <p class="navbar-text">Текущий аккаунт:
                <?
                if(isset($Login)){
                    echo ($Login);}
                else{
                    echo "неизвестен";
                }
                ?>
            </p>

        </div><!--/.navbar-collapse -->
    </div>
</nav>

    <div class="container jumbotron" style="margin-top: 80px">
        <form action="statScript.php" method="POST" class="form-group">
            <div class="row">
                <div class="col-xs-6 col-lg-6 col-md-6">
                    <label class="control-label" for="dateFrom">Начало периода</label>
                    <input type="date" class="form-control" id="dateFrom" name="DateFrom" placeholder="YYYY-MM-DD" required pattern="^[0-9]{4}-[0-9]{2}-[0-9]{2}$"  value="<?echo "$strDateLastWeek";?>">
                    <button type="button" class="btn btn-link" id="dateIntWeek">Неделя</button>
                    <button type="button" class="btn btn-link" id="dateIntMonth">Месяц</button>
                </div>
                <div class="col-xs-6 col-lg-6 col-md-6">
                    <label class="control-label" for="dateTo">Конец периода</label>
                    <input type="date" class="form-control" id="dateTo" name="DateTo" placeholder="YYYY-MM-DD" required pattern="^[0-9]{4}-[0-9]{2}-[0-9]{2}$" value="<?echo "$strDateNow";?>">
                    <button type="button" class="btn btn-link" id="dateNow">Сегодня</button>
                </div>
            </div>
            <hr>

            <label class="control-label" for="campID">Кампания (для множественного выбора зажмите ctrl)</label>

            <?
            //вывод всех кампаний
            $direct_url = "https://api.direct.yandex.ru/live/v4/json/";
            $ch = curl_init();
            $paramClient = ( (isset($ClientLogin))?$ClientLogin:$Login );

            $post_data = array(
                "param" =>array(
                    "Logins" => array($paramClient),
                    "Filter" => array(
                        "StatusArchive" => array("No"),
                    )
                ),
                "method" => "GetCampaignsListFilter",
                "token" => $Token,
                "locale" => "ru"
            );

            $post_data = json_encode($post_data);

            curl_setopt ($ch, CURLOPT_URL,$direct_url);
            curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $camps = curl_exec ($ch);
            curl_close($ch);

            $camps_o = json_decode($camps)->data;
            ?>
            <select  class="form-control" name="CampID[]s" id="counterID" required multiple size="<?echo(count($camps_o));?>">
                <?
                foreach ($camps_o as $camp){
                    printf("<option value=\"%s\">%s</option>", $camp->CampaignID, $camp->Name);
                }
                ?>
            </select>
            <hr>

            <label class="control-label" for="counterID">Счетчик</label>
            <select  class="form-control chzn-select" name="CounterID" id="counterID" required>
                <?
                //вывод всех счетчиков / конкретного клиента нельзя, т.к счетчики в ведении лидлаба, а не клиентов.

                $metrika_url = "https://api-metrika.yandex.ru/counters.json?pretty=1&oauth_token=".$Token;

                $ch = curl_init();
                curl_setopt ($ch, CURLOPT_URL,$metrika_url);
                curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                $metrika = curl_exec ($ch);
                curl_close($ch);

                $metrika_o = (array) json_decode($metrika);
                $counters = $metrika_o["counters"];
                $rows = $metrika_o["rows"];

                function mySort($f1,$f2) //=================СОРТИРОВКА ПРИШЕДШИХ ДАННЫХ
                {
                    if($f1->name < $f2->name) return -1;
                    elseif($f1->name > $f2->name) return 1;
                    else return 0;
                }
                uasort($counters,"mySort");//сортирует массив, используя пользовательскую функцию mySort

                foreach ($counters as $counter){
                    if(isset($counter->name)){
                        $isSelected = ($selectedCounter == $counter->id)?"selected=\"\"":"";
                        printf("<option value=\"%s\" %s>ID - %s, НАЗВАНИЕ - ( %s ), САЙТ - ( %s )</option>", $counter->id, $isSelected, $counter->id, $counter->name, $counter->site);

                    }
                }
                ?>
            </select>
            <hr>

            <!--Создание спойлера-->
            <a href="#spoiler-1" class="btn btn-default btn-sm collapsed spoiler" data-toggle="collapse"> Дополнительные параметры</a>
            <div class="collapse" id="spoiler-1">
                <div class="well">
                    <div class="container-fluid">
                        <div class="col-md-4 col-lg-4 col-sm-4">
                            <p>Валюта</p>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="RUB" name="Сurrency" checked>
                                Российский рубль (RUB)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="CHF" name="Сurrency">
                                Швейцарский франк (CHF)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="EUR" name="Сurrency">
                                Евро (EUR)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="KZT" name="Сurrency">
                                Казахский тенге (KZT)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="TRY" name="Сurrency">
                                Турецкая лира (TRY)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="UAH" name="Сurrency">
                                Украинская гривна (UAH)
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="USD" name="Сurrency">
                                Доллар США (USD)
                            </label><br>
                            <hr>
                        </div>
                        <div class="col-md-4 col-lg-4 col-sm-4">
                            <p>Учитывать НДС?</p>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="Yes" name="IncludeVAT" checked>
                                Да
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="No" name="IncludeVAT">
                                Нет
                            </label><br>
                            <hr>
                        </div>
                        <div class="col-md-4 col-lg-4 col-sm-4">
                            <p>Учитывать скидки?</p>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="Yes" name="IncludeDiscount" checked>
                                Да
                            </label><br>
                            <label class="control-label">
                                <input class="control-label" type="radio" value="No" name="IncludeDiscount">
                                Нет
                            </label><br>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>
            <hr>

            <input type="hidden" name="Token" value="<?echo $Token?>">
            <input type="hidden" name="Login" value="<?echo $Login?>">
            <? if (isset($ClientLogin)):?>
                <input type="hidden" name="ClientLogin" value="<?echo "$ClientLogin"?>">
            <? endif?>
            <input type="hidden" name="Role" value="<?echo $Role?>">

            <input class="btn btn-success" type="submit" value="Сформировать отчет">
        </form>
    </div>
<script>
    $("#dateIntWeek").click(function(){
        var yyyy = $("#dateTo").val().substring(0,4);
        var mm = $("#dateTo").val().substring(5,7) - 1;
        var dd = $("#dateTo").val().substring(8,10);

        var myDate = new Date(yyyy, mm, dd);
        myDate.setDate(myDate.getDate() - 7);

        var month = myDate.getMonth()+1;
        if (month<10) month='0'+month;
        var day = myDate.getDate();
        if (day<10) day='0'+day;
        var year = myDate.getFullYear();
        $("#dateFrom").val(year+'-'+month+'-'+day);
    });

    $("#dateIntMonth").click(function(){
        var yyyy = $("#dateTo").val().substring(0,4);
        var mm = $("#dateTo").val().substring(5,7) - 1;
        var dd = $("#dateTo").val().substring(8,10);

        var myDate = new Date(yyyy, mm, dd);
        myDate.setDate(myDate.getDate() - 25); //переходим на предыдущий месяц

        var month = myDate.getMonth()+1;
        if (month<10) month='0'+month;
        var day = myDate.getDate();
        if (day<10) day='0'+day;
        var year = myDate.getFullYear();
        $("#dateFrom").val(year+'-'+month+'-01');//начинаем статистику с первого дня месяца
    });

    $("#dateNow").click(function(){
        var myDate = new Date();

        var month = myDate.getMonth()+1;
        if (month<10) month='0'+month;
        var day = myDate.getDate();
        if (day<10) day='0'+day;
        var year = myDate.getFullYear();
        $("#dateTo").val(year+'-'+month+'-'+day);//начинаем статистику с первого дня месяца
    });
</script>

    <!--Include reserve AJAX library-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="public/js/bootstrap.js"></script>

    <!--Подключаем chosen.jquery.min.js-->
    <script src="public/chosen/chosen.jquery.min.js" type="text/javascript"></script>
    <!--Включаем плагин-->
    <script type="text/javascript">
        $(".chzn-select").chosen({search_contains:true}); $(".chzn-select-deselect").chosen({allow_single_deselect:true});
    </script>


</body>

</html>
