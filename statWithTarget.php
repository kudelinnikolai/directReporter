<?
$direct_url = "https://api.direct.yandex.ru/live/v4/json/";
$campWithName = array();

//echo"<pre>";
foreach ($CampsID as $id){
    $post_data = array("param" =>array(
    "CampaignIDS" => array($id),
    "Limit" => 1,
    ),
    "method" => "GetBanners",
    "token" => $Token,
    "locale" => "ru"
    );

    $post_data = json_encode($post_data);

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL,$direct_url);
    //curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $stat = curl_exec ($ch);
    curl_close($ch);

    $stat_o = json_decode($stat);

    //print_r($stat_o);
    if (isset($stat_o->data[0]->Href)){
        parse_str(parse_url($stat_o->data[0]->Href, PHP_URL_QUERY),$urlParams);
        $campWithName[$id] =$urlParams['utm_campaign']; // id=> "okna_rsy"
    } else{
        $AllIsBad = true;
    }
}

foreach ($campWithName as $key=>$val){ //Избавились от кампаний, в кт не указана статистика или указано подставляемое значение
    if ( ($val == "") || ( (substr($val, -1, 1) == "}") && (substr($val, 0, 1) == "{")) ){
        unset($campWithName[$key]);
    }
}

if(count($campWithName) == 0){
    $AllIsBad = true;
} else {
    ksort($campWithName);
}

//print_r($campWithName);
if( !$AllIsBad ){
    $metrika_url = "https://api-metrika.yandex.ru/stat/v1/data.json?id=".$CounterID."&metrics=ym:s:visits,ym:s:users&dimensions=ym:s:goal,ym:s:UTMCampaign,ym:s:UTMSource&pretty=1&oauth_token=".$Token."&date1=".$DateFrom."&date2=".$DateTo;

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL,$metrika_url);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $metrika = curl_exec ($ch);
    curl_close($ch);

    $metrika = json_decode($metrika);

    /*echo"<pre>";
    print_r($metrika);
    echo "$<br><br>";*/

    if( isset($metrika->data) ){
        $campnameWithStat = array();
        $keys = array();
        $values = array();

        foreach ($metrika->data as $item){
            if ($item->dimensions[2]->name == 'yandex'){
                array_push($keys, $item->dimensions[1]->name);
                array_push($values, $item->metrics[1]); //0 или 1???????????????????????????
            }
        }
        $campnameWithStat = array_combine($keys, $values);
    } else {
        $AllIsBad = true;
    }

    //print_r($campnameWithStat); // [okna_goryach] => 12


    //print_r($campWithName); // [13509742] => okna_goryach
}

if (!$AllIsBad){
    foreach ($campWithName as $key=>$val) {
        if( key_exists($val ,$campnameWithStat ) ){
            $campWithName[$key] = $campnameWithStat[$val];
        } else {
            unset($campWithName[$key]);
        }
    }

    $campWithStat = $campWithName;
    //print_r($campWithStat);//[13509742] => 12

    if(count($campWithStat) == 0) {
        $AllIsBad = true;
    }
}


if(!$AllIsBad){
    $metrika_url = "https://api.direct.yandex.ru/live/v4/json/";

    $ch = curl_init();
    $post_data = array(
        "param" =>array(
            "CampaignIDS" => $CampsID,
        ),
        "method" => "GetCampaignsParams",
        "token" => $Token,
        "locale" => "ru"
    );

    $post_data = json_encode($post_data);

    curl_setopt ($ch, CURLOPT_URL,$metrika_url);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $metrika = curl_exec ($ch);
    curl_close($ch);

    $metrika = json_decode($metrika);

//print_r($metrika);

    if(isset($metrika->data)){
        $campWithName = array();
        foreach ($metrika->data as $item){
            $campWithName[$item->CampaignID] = $item->Name;
        }
    } else {
        $AllIsBad = true;
    }
    //print_r($campWithName);
    ksort($campWithName);//[13509742] => Окна горячие
}

if(!$AllIsBad){
    if(count($campWithStat) < count($CampsID)){
        foreach ( $CampsID as $item) { //кампании, по которым нужна статистика, но они не попали в выборку
            if(!array_key_exists ( $item , $campWithStat )){
                $CampsWithoutConversion[] = $item;
            }
        }

        //var_dump($CampsWithoutConversion);
    }
}


if ($AllIsBad) {

    require "statWithoutTarget.php";
}

?>