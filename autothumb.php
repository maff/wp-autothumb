<?php
/*
Plugin Name: AutoThumb
Plugin URI: http://maff.ailoo.net/projects/autothumb/
Description: A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into Wordpress.
Author: Mathias Geat
Version: 0.3.1.1
Author URI: http://ailoo.net/
*/

define(AUTOTHUMB_PATH, dirname(__FILE__) . '/');

/* Plugin
------------------------------------------------------------------------------------- */

/**
 * getphpthumburl
 *
 * Creates an URL to phpThumb
 *
 * @param string $image         the path/URL to the image
 * @param string $params        phpThumb parameters
 * @param bool   $xhtmlOutput   if set to false, URLs won't contain escaped HTML entities (e.g. &amp;)
 */
function getphpthumburl($image, $params = 'w=800', $xhtmlOutput = true)
{
    include(AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php');

    if(!empty($image)) {
        $query = 'src=' . $image;

        // append phpThumb parameters
        if(!empty($params)) $query = $query . '&' . $params;
        
        // generate hash for security
        $hash = md5($query . $PHPTHUMB_CONFIG['high_security_password']);
        
        // append hash to query
        $query .= '&hash=' . $hash;    
        
        if($xhtmlOutput) {
            // generate XHTML valid URLs
            $query = str_replace('&', '&amp;', $query);
        }
        
        // path to image.php
        $phpthumb = get_bloginfo('wpurl') . '/wp-content/plugins/autothumb/image.php';

        // generate URL and return the result   
        return $phpthumb . '?' . $query;
    }   
    
    return false;
}

/**
 * autothumb
 *
 * Automatically rewrite img tags to phpThumb
 *
 * @param string $content       the content containing img tags
 */
function autothumb($content)
{
    $imagesearch = array();
    $imagereplace = array();
    
    $pattern = '/<img[^>]*>/';
    preg_match_all($pattern, $content, $toReplace);
    
    // get relative path from document root (e.g. if WP is installed in subdirectory)
    $relativePath = str_replace('\\', '/', AUTOTHUMB_PATH);
    $relativePath = str_replace('wp-content/plugins/autothumb/', '', $relativePath);
    $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '/', $relativePath);
    
    if(substr($basePath, 0, 1) != '/')
        $basePath = '/' . $basePath;
    
    for($n = 0; $n < count($toReplace[0]); $n++) {        
        $imagetag = $toReplace[0][$n];
        
        $search = array();
        $result = array();
        $image = array();
        
        $search['src'] = '/src="([^"]*)"/';
        $search['width'] = '/width="[0-9]+"/';
        $search['height'] = '/height="[0-9]+"/';
        $search['imagephp'] = '/image.php/';
        $search['phpthumb'] = '/phpThumb.php/';
        
        preg_match($search['src'], $imagetag, $result['src']);
        preg_match($search['width'], $imagetag, $result['width']);
        preg_match($search['height'], $imagetag, $result['height']);
        
        $result['src'][1] = trim($result['src'][1]);
        if(!empty($result['src'][1])) {
            $image['src'] = $result['src'][1];
            
            if(strtolower(substr($image['src'], 0, 1)) == '/') {
                $image['src'] = str_replace($_SERVER['DOCUMENT_ROOT'], $relativePath, $image['src']);
            } elseif(strotolower(substr($image['src'], 0, 1)) != '/') {
                $image['src'] = $relativePath . $image['src'];
            }
            
            if(!empty($result['width'][0])) {
                $image['width'] = str_replace('width="', '', $result['width'][0]);
                $image['width'] = trim(str_replace('"', '', $image['width']));
            }
            
            if(!empty($result['height'][0])) {
                $image['height'] = str_replace('height="', '', $result['height'][0]);
                $image['height'] = trim(str_replace('"', '', $image['height']));
            }
            
            preg_match($search['imagephp'], $image['src'], $result['imagephp']);
            preg_match($search['phpthumb'], $image['src'], $result['phpthumb']);

            if(count($result['imagephp']) == 0 && count($result['phpthumb']) == 0) {
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
                
                $newsrc = getphpthumburl($image['src'], $ptoptionstring);
                $newtag = preg_replace('/src="([^"]*)"/', 'src="'.$newsrc.'"', $imagetag);
                $newtag = preg_replace('/ width="[^"]*"/', '', $newtag);
                $newtag = preg_replace('/ height="[^"]*"/', '', $newtag);
                $newtag = str_replace('  ', ' ', $newtag);
                
                $imagesearch[$n] = $imagetag;
                $imagereplace[$n] = $newtag;
            }
        }
    }
    
    if(count($imagesearch) > 0 && count($imagesearch) == count($imagereplace)) {
        $content = str_replace($imagesearch, $imagereplace, $content);
    }
    
    return $content;
}

add_filter('the_content', 'autothumb');


/* Option Panel (thanks to Ben for this addition - see comments on plugin home page)
------------------------------------------------------------------------------------- */

add_option('autothumb_high_security_password', 'Type your own password here', '', true);

function autothumb_add_options()
{
    add_options_page('Autothumb options', 'Autothumb', 8, basename(__FILE__), 'autothumb_options_subpanel');
}

function autothumb_options_subpanel()
{
    include('autothumb-options-panel.php');
}

function autothumb_update_high_security_password()
{
        $configFile = AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php';
        $config = file($configFile);
        
        $needle = "/^\s*\\\$PHPTHUMB\_CONFIG\[\'high_security_password.*/";
        
        $result = null;
        $i = 0;
        foreach($config as $line){
            $line = rtrim($line, "\r\n") . PHP_EOL;
            if(preg_match($needle, $line)) {
                $result = $i;
                break;
            }
                
            ++$i;
        }
        
        if($result !== null) {
            $config[$result] = "\$PHPTHUMB_CONFIG['high_security_password']   = '" . get_option('autothumb_high_security_password') . "';  // required if 'high_security_enabled' is true, must be at least 5 characters long" . PHP_EOL;
        }

        file_put_contents($configFile, implode($config));
}

add_action('admin_menu', 'autothumb_add_options');
add_action('update_option_autothumb_high_security_password', 'autothumb_update_high_security_password');