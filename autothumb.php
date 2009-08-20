<?php
/*
Plugin Name: AutoThumb
Plugin URI: http://maff.ailoo.net/projects/autothumb/
Description: A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into Wordpress.
Author: Mathias Geat
Version: 0.4.4
Author URI: http://ailoo.net/
*/

define(AUTOTHUMB_PATH, dirname(__FILE__) . '/');

/* Initialization
------------------------------------------------------------------------------------- */

if (is_admin()) {
    add_action('admin_menu', 'autothumb_add_options_page');
    add_action('admin_init', 'autothumb_check');
    add_action('admin_init', 'autothumb_register_settings');
    add_action('admin_init', 'autothumb_generate_rewrite_rules');
} else {
    if(get_option('autothumb_apply_the_content') == 1) {
        add_filter('the_content', 'autothumb');
    }
}


/* Plugin
------------------------------------------------------------------------------------- */

/**
 * getphpthumburl
 *
 * Creates an URL to phpThumb
 *
 * @param  string $image        the path/URL to the image
 * @param  string $params       phpThumb parameters
 * @param  bool   $xhtmlOutput  if set to false, URLs won't contain escaped HTML entities (e.g. &amp;)
 * @return string
 */
function getphpthumburl($image, $params = 'w=800', $xhtmlOutput = true)
{
    include(AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php');

    if(!empty($image)) {
        $httpSrc = false;
        if(strtolower(substr($image, 0, 1)) == '/') {
            $image = str_replace($_SERVER['DOCUMENT_ROOT'], '/', $image);
        } elseif(strtolower(substr($image, 0, 4)) == 'http') {
            $httpSrc = true;
        } else {
            $blogurl = parse_url(get_bloginfo('wpurl'));                
            $image = $blogurl['path'] . '/' . $image;
        }  
    
        $queryString = 'src=' . $image . '&' . $params;
        
        $clean_urls = false;
        if(get_option('autothumb_clean_urls') == 1) {
            $clean_urls = true;
        }
        
        if($clean_urls) {
            $imageSeparator = '';
            if($httpSrc) {
                $imageSeparator = '/';
            }
        
            $query = get_bloginfo('wpurl') . '/' . get_option('autothumb_clean_urls_path') . $imageSeparator . $image . '?' . $params;
        } else {
            $query = get_bloginfo('wpurl') . '/wp-content/plugins/autothumb/image.php?' . $queryString;
        }
        
        // generate hash for security
        $hash = md5($queryString . $PHPTHUMB_CONFIG['high_security_password']);
        
        // append hash to query
        $query .= '&hash=' . $hash;    
        
        if($xhtmlOutput) {
            // generate XHTML valid URLs
            $query = str_replace('&', '&amp;', $query);
        }
        
        return $query;
    }   
    
    return false;
}

/**
 * autothumb
 *
 * Automatically rewrite img tags to phpThumb
 *
 * @param  string $content  the content containing img tags
 * @return string
 */
function autothumb($content)
{
    $imagesearch = array();
    $imagereplace = array();
    
    $pattern = '/<img[^>]*>/';
    preg_match_all($pattern, $content, $toReplace);
    
    for($n = 0; $n < count($toReplace[0]); $n++)
    {
        $processImage = true;
        $imagetag = $toReplace[0][$n];
        $newtag = $imagetag;
        
        $search = array();
        $result = array();
        $image = array();
        
        $search['process'] = '/process="[0-1]"/';
        $search['src'] = '/src="([^"]*)"/';
        $search['width'] = '/width="[0-9]+"/';
        $search['height'] = '/height="[0-9]+"/';
        $search['imagephp'] = '/image.php/';
        $search['phpthumb'] = '/phpThumb.php/';
        
        preg_match($search['process'], $imagetag, $result['process']);
        
        // check process html attribute...abort if false
        if(!empty($result['process'][1])) {
            if($result['process'][1] == 0) {
                $processImage = false;
            }
            
            $newtag = preg_replace('/ process="[0-1]"/', '', $newtag);
            $newtag = str_replace('  ', ' ', $newtag);
        }
            
        if($processImage) {       
            preg_match($search['src'], $imagetag, $result['src']);
            preg_match($search['width'], $imagetag, $result['width']);
            preg_match($search['height'], $imagetag, $result['height']);
            
            $result['src'][1] = trim($result['src'][1]);
            if(!empty($result['src'][1])) {
                $image['src'] = $result['src'][1];
                
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
                    
                    // best quality
                    $ptoptions[] = 'q=100';
                    
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
                                foreach($param as $element) {
                                    $ptoptions[] = "{$k}[]={$element}";
                                }
                            }
                            else
                            {
                                if($k == 'process' && $param == '0') {
                                    $processImage = false;
                                }
                                
                                $ptoptions[] = "{$k}={$param}";
                            }
                        }
                    }
                    unset($aParam, $param);
                    
                    if($processImage) {
                        $ptoptionstring = '';
                        for($i = 0; $i < count($ptoptions); $i++) {
                            if($i != 0) $ptoptionstring .= '&';
                            $ptoptionstring .= $ptoptions[$i];
                        }
                        
                        $newsrc = getphpthumburl($image['src'], $ptoptionstring);
                        $newtag = preg_replace('/src="([^"]*)"/', 'src="' . $newsrc . '"', $newtag);
                        $newtag = preg_replace('/ width="[^"]*"/', '', $newtag);
                        $newtag = preg_replace('/ height="[^"]*"/', '', $newtag);
                        $newtag = str_replace('  ', ' ', $newtag);
                    }
                }
            }
        }
        
        if($newtag != $imagetag) {
            $imagesearch[$n] = $imagetag;
            $imagereplace[$n] = $newtag;
        }        
    }
    
    if(count($imagesearch) > 0 && count($imagesearch) == count($imagereplace)) {
        $content = str_replace($imagesearch, $imagereplace, $content);
    }
    
    return $content;
}


/* Option Panel (thanks to Ben for the idea - see comments on plugin home page)
------------------------------------------------------------------------------------- */


function autothumb_add_options_page()
{
    add_options_page('Autothumb options', 'Autothumb', 8, basename(__FILE__), 'autothumb_options_subpanel');
}

function autothumb_register_settings()
{
    register_setting('autothumb', 'autothumb_high_security_password', 'autothumb_high_security_password_update');
    register_setting('autothumb', 'autothumb_apply_the_content');
    register_setting('autothumb', 'autothumb_clean_urls');    
    register_setting('autothumb', 'autothumb_clean_urls_path', 'autothumb_clean_urls_path_update');    
}

function autothumb_check()
{
    if(!is_writable(AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php')) {
        add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>AutoThumb is not able to write to the phpThumb.config.php file. Please fix file permissions or edit the file manually.</p></div>';"));            
    }

    $high_security_password = get_option('autothumb_high_security_password');
    if($high_security_password === false || empty($high_security_password)) {
        add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>AutoThumb High Security Password is not set. Please update your <a href=\"" . get_bloginfo('wpurl') . "/wp-admin/options-general.php?page=autothumb.php\">settings</a>.</p></div>';"));    
    }
}

function autothumb_options_subpanel()
{
    include('autothumb-options-panel.php');
}

// write password to config file
function autothumb_high_security_password_update($password)
{
    $password = trim($password);
    if(strlen($password) < 5) return;

    $configFile = AUTOTHUMB_PATH . 'phpthumb/phpThumb.config.php';
    
    if(is_writable($configFile)) {
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
            $config[$result] = "\$PHPTHUMB_CONFIG['high_security_password']   = '" . $password . "';  // required if 'high_security_enabled' is true, must be at least 5 characters long" . PHP_EOL;
        }

        file_put_contents($configFile, implode($config));
    }
    
    return $password;
}

function autothumb_clean_urls_path_update($path)
{
    $path = trim($path, '/\\ ');
    autothumb_generate_rewrite_rules(false, $path, true);
    return $path;
}

function autothumb_generate_rewrite_rules($_switch = false, $_path = false, $_flush = false)
{
    global $wp_rewrite;
    
    $switch = ($_switch !== false) ? $_switch : get_option('autothumb_clean_urls');
    $path = ($_path !== false) ? $_path : get_option('autothumb_clean_urls_path');
    
    if($switch == 1 && $path !== false && !empty($path)) {
        $wp_rewrite->non_wp_rules = array($path . '/(.*)$' => 'wp-content/plugins/autothumb/image.php?$1');
    }
    
    if($_flush) {
        $wp_rewrite->flush_rules();
    }
}

function autothumb_print_rewrite_rules()
{
    global $wp_rewrite;
    autothumb_generate_rewrite_rules();
    print_r($wp_rewrite->mod_rewrite_rules());
}