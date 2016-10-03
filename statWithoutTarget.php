<?
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

$campWithName = array();
foreach ($metrika->data as $item){
    $campWithName[$item->CampaignID] = $item->Name;
}

//print_r($campWithName);
ksort($campWithName);//[13509742] => Окна горячие

$campWithStat = array();
for ($i = 0; $i < count($CampsID); $i++){
    $campWithStat[($CampsID[$i])] = 0;
}
?>