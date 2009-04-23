<?php
/*
Plugin Name: AutoThumb
Plugin URI: http://maff.ailoo.net/projects/autothumb/
Description: A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into Wordpress.
Author: Mathias Geat
Version: 0.2
Author URI: http://ailoo.net/
*/

define(AUTOTHUMB_PATH, dirname(__FILE__) . '/');

function autothumb($content)
{
    $imagesearch = array();
    $imagereplace = array();
    
    $pattern = '/<img[^>]*>/';
    preg_match_all($pattern, $content, $replace);
    
    $basePath = str_replace('\\', '/', dirname(__FILE__));
    $basePath = str_replace('wp-content/plugins/autothumb', '', $basePath);
    $basePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $basePath);
    
    if(substr($basePath, 0, 1) != '/')
        $basePath = '/' . $basePath;
    
    for($n = 0; $n < count($replace[0]); $n++) {        
        $imagetag = $replace[0][$n];
        
        $search = array();
        $result = array();
        $image = array();
        
        $search['width'] = '/width="[0-9]+"/';
        $search['height'] = '/height="[0-9]+"/';
        $search['src'] = '/src="([^"]*)"/';
        $search['imagephp'] = '/image.php/';
        $search['phpthumb'] = '/phpThumb.php/';
        
        preg_match($search['width'], $imagetag, $result['width']);
        preg_match($search['height'], $imagetag, $result['height']);
        preg_match($search['src'], $imagetag, $result['src']);
        
        if(!empty($result['width'][0])) {
            $image['width'] = str_replace('width="', '', $result['width'][0]);
            $image['width'] = trim(str_replace('"', '', $image['width']));
        }
        
        if(!empty($result['height'][0])) {
            $image['height'] = str_replace('height="', '', $result['height'][0]);
            $image['height'] = trim(str_replace('"', '', $image['height']));
        }
        
        if(!empty($result['src'][1])) $image['src'] = $result['src'][1];
        
        preg_match($search['imagephp'], $image['src'], $result['imagephp']);
        preg_match($search['phpthumb'], $image['src'], $result['phpthumb']);

        if(count($result['imagephp']) == 0 && count($result['phpthumb']) == 0) {
            if(strpos($image['src'], $_SERVER['HTTP_HOST']) !== false) {        
                $image['src'] = str_replace(get_bloginfo('url') . '/', $basePath, $image['src']);
            }
            
            $ptoptions = array();
            if(!empty($image['width'])) $ptoptions[] = 'w=' . $image['width'];
            if(!empty($image['height'])) $ptoptions[] = 'h=' . $image['height'];
            
            // allow enlargement of images, uncomment this if you want
            $ptoptions[] = 'aoe=1';
            
            // thanks to netProphET for this addition
            // this allows you to set phpthumb parameters in the image URL
            // see http://modxcms.com/forums/index.php/topic,14858.msg102750.html#msg102750
            if(preg_match("/^([^\?]*)\?(.*)$/", $image['src'], $ma)) {
                $aParam = array();
                $image['src'] = $ma[1];
                
                $ma[2] = str_replace('{}', '[]', $ma[2]);
                parse_str(urldecode($ma[2]), $aParam);
                
                foreach($aParam as $k => $param) {
                    // clean parameter keys
                    $k = str_replace('#038;', '', $k);
                    $k = str_replace('amp;', '', $k);
                    
                    if(is_array($param) && count($param) > 0)
                    {
                        foreach($param as $element)
                            $ptoptions[] = "{$k}[]={$element}";
                    }
                    else
                    {
                        $ptoptions[] = "{$k}={$param}";
                    }
                }
            }
            unset($aParam, $param);
            
            $ptoptionstring = '';
            for($i = 0; $i < count($ptoptions); $i++) {
                if($i != 0) $ptoptionstring .= '&';
                $ptoptionstring .= $ptoptions[$i];
            }
            
            if((substr($image['src'], 0, 1) != '/') &&(substr($image['src'], 0, 7) != 'http://')) {
                //$ptoptionstring .= '#'.get_bloginfo('url').'/';
            }
            
            $newsrc = getphpthumburl($image['src'], $ptoptionstring);
            $newtag = preg_replace('/src="([^"]*)"/', 'src="'.$newsrc.'"', $imagetag);
            $newtag = preg_replace('/ width="[^"]*"/', '', $newtag);
            $newtag = preg_replace('/ height="[^"]*"/', '', $newtag);
            $newtag = str_replace('  ', ' ', $newtag);
            
            $imagesearch[$n] = $imagetag;
            $imagereplace[$n] = $newtag;
        }
    }
    
    if(count($imagesearch) > 0 && count($imagesearch) == count($imagereplace)) {
        $content = str_replace($imagesearch, $imagereplace, $content);
    }
    
    return $content;
}

function getphpthumburl($output, $options)
{
    include(AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php');

    // create options array
    $options = explode('#', $options);

    // $options[0] - query for phpThumb
    // $options[1] - optional path to the image - with ending slash
    // $options[2] - mode switch
    // $options[3] - XHTML URL switch

    // replace {} with [] for correct phpThumb filter calls
    /*
    $options[0] = str_replace('{', '[', $options[0]);
    $options[0] = str_replace('}', ']', $options[0]);
    */

    // check mode switch and create src parameter
    if($options[2] == 1) {
        $src = $options[1];
    }
    else {
        $src = $options[1].$output;
    }

    if(!empty($src)) {

        $src = 'src='.$src;

        // append phpThumb parameters
        if(!empty($options[0])) $options[0] = '&'.$options[0];

        // create full query
        $ptquery = $src.$options[0];
        
        // generate hash for security
        $hash = md5($ptquery.$PHPTHUMB_CONFIG['high_security_password']);
        
        // append hash to query
        $ptquery .= '&hash='.$hash;    
        
        // if XHTML URL switch is not set generate valid URLs by replacing & with &amp;
        if($options[3] != 1) {
            // generate XHTML valid URLs
            $ptquery = str_replace('&', '&amp;', $ptquery);
        }
        
        // path to image.php
        $phpthumb = get_bloginfo('wpurl') . '/wp-content/plugins/autothumb/image.php';

        // generate URL and return the result   
        return $phpthumb . '?' . $ptquery;
    }   
    else return false;
}

add_filter('the_content', 'autothumb');