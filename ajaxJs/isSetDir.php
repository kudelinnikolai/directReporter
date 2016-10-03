<?php
header('Content-Type: text/html; charset=utf-8');
$dir = $_POST["directory"];
if(!is_dir($dir)){
    echo "Директория не найдена";
}
else{
    echo "Директория обнаружена";
}
?>