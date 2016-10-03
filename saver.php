<?php
//require "garbageCollector.php";

Header("HTTP/1.1 200 OK");
Header("Connection: close");
Header("Content-Type: application/octet-stream");
/*header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");*/
Header("Accept-Ranges: bytes");
Header("Content-Disposition: Attachment; filename=".$_GET['fileName'].".xls");
Header("Content-Length: 50000");

readfile($_GET['fileName'].".xls");

?>