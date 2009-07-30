<?php
if(substr($_SERVER['QUERY_STRING'], 0, 4) != 'src=') {
    if(strtolower(substr($_SERVER['QUERY_STRING'], 0, 1)) == '/') {
        $_SERVER['QUERY_STRING'] = str_replace($_SERVER['DOCUMENT_ROOT'], '/', $_SERVER['QUERY_STRING']);
    } elseif(strtolower(substr($_SERVER['QUERY_STRING'], 0, 4)) == 'http') {
        $search = array('http:/', 'https:/');
        $replace = array('http://', 'https://');
        $_SERVER['QUERY_STRING'] = str_replace($search, $replace, $_SERVER['QUERY_STRING']);
    } else {
        $_SERVER['QUERY_STRING'] = '/' . $_SERVER['QUERY_STRING'];
    }

    $_SERVER['QUERY_STRING'] = 'src=' . $_SERVER['QUERY_STRING'];
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

include('phpthumb/phpThumb.php');