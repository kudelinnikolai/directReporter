<?
function full_del_dir ($directory)
{
    $dir = opendir($directory);
    while(($file = readdir($dir)))
    {
        if ( is_file ($directory."/".$file))
        {
            unlink ($directory."/".$file);
        }
        else if ( is_dir ($directory."/".$file) &&
            ($file != ".") && ($file != ".."))
        {
            full_del_dir ($directory."/".$file);
        }
    }
    closedir ($dir);
}
full_del_dir ("excels");
?>