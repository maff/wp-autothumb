<?php
/*
Plugin Name: AutoThumb
Plugin URI: http://ailoo.net/projects/autothumb/
Description: A plugin which integrates <a href="http://phpthumb.sourceforge.net/">phpThumb</a> into WordPress.
Version: 0.6.1
Author: Mathias Geat
Author URI: http://ailoo.net/
*/

$autoThumb = new AutoThumb(dirname(__FILE__));

function autothumb($content)
{
    global $autoThumb;
    return $autoThumb->processContent($content);
}

function getphpthumburl($image, $params = 'w=800', $xhtmlOutput = true)
{
    global $autoThumb;
    return $autoThumb->getUrl($image, $params, $xhtmlOutput);
}

class AutoThumb
{
    /**
     * AutoThumb version
     *
     * @var string
     */
    protected $version = '0.6.1';

    /**
     * Determines if config and permissions are ok
     *
     * @var bool
     */
    protected $setUpAndWorking = false;

    /**
     * Base AutoThumb path
     *
     * @var string
     */
    protected $path;

    /**
     * High security password used to hash urls
     *
     * @var string
     */
    protected $highSecurityPassword;

    /**
     * WordPress rewrite instance
     *
     * @var WP_Rewrite
     */
    protected $rewrite;

    /**
     * Working directory
     *
     * @var string
     */
    protected $workingDir = 'autothumb';

    /**
     * Cache directory
     *
     * @var string
     */
    protected $cacheDir = 'cache';

    /**
     * Cache for remote files
     *
     * @var string
     */
    protected $cacheSourceDir = 'source';

    /**
     * Filename of password file
     *
     * @var string
     */
    protected $passwordFile = 'highSecurityPassword.php';

    /**
     * Constructor
     */
    public function __construct($path)
    {
        $this->path = $path;

        if (is_admin()) {
            add_action('admin_init', array($this, 'checkEnvironment'));
            add_action('admin_init', array($this, 'registerSettings'));
            add_action('admin_init', array($this, 'generateRewriteRules'));
            add_action('admin_menu', array($this, 'addOptionsPage'));
        } else {
            $this->checkFrontendEnvironment();

            if (get_option('autothumb_apply_the_content') == 1) {
                add_filter('the_content', array($this, 'processContent'));
            }
        }
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Add settings page
     */
    public function addOptionsPage()
    {
        add_options_page(
            'AutoThumb options', 'AutoThumb', 'manage_options', basename(__FILE__), array($this, 'optionsSubpanel')
        );
    }

    /**
     * Settings page callback
     */
    public function optionsSubpanel()
    {
        include('autothumb-options-panel.php');
    }

    /**
     * Register DB settings
     */
    public function registerSettings()
    {
        register_setting('autothumb', 'autothumb_apply_the_content');
        register_setting('autothumb', 'autothumb_clean_urls');
        register_setting('autothumb', 'autothumb_clean_urls_path', array($this, 'updateCleanUrlsPath'));
    }

    /**
     * Add an admin message
     *
     * @param string $message
     * @param string $type
     */
    protected function addAdminMessage($message, $type = 'error')
    {
        add_action(
            'admin_notices', create_function('', "echo '<div class=\"{$type}\"><p>{$message}</p></div>';")
        );
    }

    /**
     * Check directory for validity
     *
     * @param  string $dir
     * @return bool
     */
    protected function dirExists($dir)
    {
        return file_exists($dir) && is_dir($dir) && is_writable($dir);
    }

    /**
     * Quick environment check without fallbacks for frontend
     *
     * @return boolean
     */
    public function checkFrontendEnvironment()
    {
        $workingDir = realpath($this->path . '/../..') . '/' . $this->workingDir;
        $passwordFile = $workingDir . '/' . $this->passwordFile;

        $cacheDir = $workingDir . '/' . $this->cacheDir;
        $cacheSourceDir = $cacheDir . '/' . $this->cacheSourceDir;

        if ($this->dirExists($workingDir)) {
            if ($this->dirExists($cacheSourceDir)) {
                if (file_exists($passwordFile)) {
                    include $passwordFile;
                    if (isset($PHPTHUMB_CONFIG['high_security_password'])) {
                        $password = (string) $PHPTHUMB_CONFIG['high_security_password'];
                        if (!empty($password) && strlen($password) > 4) {
                            $this->highSecurityPassword = $password;
                            $this->setUpAndWorking = true;
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if all is set up correctly
     *
     * @return bool
     */
    public function checkEnvironment()
    {
        if ($this->checkWorkingDir()) {
            $this->setUpAndWorking = true;
        }

        return $this->setUpAndWorking;
    }

    /**
     * Check working directory
     *
     * @return bool
     */
    protected function checkWorkingDir()
    {
        $workingDir = realpath($this->path . '/../..') . '/autothumb';

        if ($this->dirExists($workingDir)) {
            if ($this->checkPasswordFile($workingDir)
                    && $this->checkCacheDir($workingDir)) {
                return true;
            }
        } else {
            if (!mkdir($workingDir)) {
                $this->addAdminMessage('AutoThumb could not create working directory in "' . $workingDir . '" - please check your permissions.');
            } else {
                if ($this->checkPasswordFile($workingDir)
                        && $this->checkCacheDir($workingDir)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if password file exists and is readable and creates a new one if missing
     *
     * @param  string $workingDir
     * @return bool
     */
    protected function checkPasswordFile($workingDir)
    {
        $password = null;
        $passwordFile = $workingDir . '/highSecurityPassword.php';
        if (file_exists($passwordFile)) {
            include $passwordFile;
            if (isset($PHPTHUMB_CONFIG['high_security_password'])) {
                $password = (string) $PHPTHUMB_CONFIG['high_security_password'];
                if (!empty($password) && strlen($password) > 4) {
                    $this->highSecurityPassword = $password;
                }
            }

            if ($this->highSecurityPassword === null) {
                $this->addAdminMessage('AutoThumb password file in "' . $passwordFile . '" is invalid. Please fix it manually or delete it to automatically generate a new one.');
            }
        } else {
            $password = wp_generate_password(50, true);
            $passwordFileContent = "<?php\n\$PHPTHUMB_CONFIG['high_security_password'] = '" . $password . "';";
            if (file_put_contents($passwordFile, $passwordFileContent) === false) {
                $this->addAdminMessage('AutoThumb: Could not write high security password to "' . $passwordFile . '". Please check writing permissions.');
            } else {
                $this->highSecurityPassword = $password;
            }
        }

        return ($this->highSecurityPassword !== null);
    }

    /**
     * Check if cache directory exists
     *
     * @param  string $workingDir
     * @return bool
     */
    protected function checkCacheDir($workingDir)
    {
        $cacheDir = $workingDir . '/cache';
        $cacheSourceDir = $workingDir . '/cache/source';

        if ($this->dirExists($cacheDir) && $this->dirExists($cacheSourceDir)) {
            return true;
        } else {
            if (!mkdir($cacheSourceDir, 0777, true)) {
                $this->addAdminMessage('AutoThumb: Could not write create cache directory. Please check writing permissions.');
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Get WP_Rewrite instance
     *
     * @return WP_Rewrite
     */
    public function getRewrite()
    {
        if ($this->rewrite === null) {
            global $wp_rewrite;
            $this->rewrite = $wp_rewrite;
        }

        return $this->rewrite;
    }

    /**
     * Generate AutoThumb mod_rewrite rules
     *
     * @param bool   $_switch
     * @param string $_path
     * @param bool   $_flush
     */
    public function generateRewriteRules($_switch = false, $_path = false, $_flush = false)
    {
        $wpRewrite = $this->getRewrite();

        $switch = ($_switch !== false) ? $_switch : get_option('autothumb_clean_urls');
        $path = ($_path !== false) ? $_path : get_option('autothumb_clean_urls_path');

        if ($switch == 1 && $path !== false && !empty($path)) {
            $wpRewrite->non_wp_rules = array($path . '/(.*)$' => 'wp-content/plugins/autothumb/image.php?$1');
        }

        if ($_flush) {
            $wpRewrite->flush_rules();
        }
    }

    /**
     * Update rewrite rules
     *
     * @param  string $path
     * @return string
     */
    public function updateCleanUrlsPath($path)
    {
        $path = trim($path, '/\\ ');
        $this->generateRewriteRules(false, $path, true);
        return $path;
    }

    /**
     * Print mod_rewrite rules
     *
     * return string
     */
    public function getRewriteRules()
    {
        $this->generateRewriteRules();
        return $this->getRewrite()->mod_rewrite_rules();
    }

    /**
     * Automatically rewrite img tags to phpThumb
     *
     * @param  string $content  the content containing img tags
     * @return string
     */
    public function processContent($content)
    {
        if (!$this->setUpAndWorking) {
            return $content;
        }

        $imagesearch = array();
        $imagereplace = array();

        $pattern = '/<img[^>]*>/';
        preg_match_all($pattern, $content, $toReplace);

        for ($n = 0; $n < count($toReplace[0]); $n++) {
            $processImage = true;
            $imagetag = $toReplace[0][$n];
            $newtag = $imagetag;

            $search = array();
            $result = array();
            $image = array();

            $search['process'] = '/process="([0-1])"/';
            $search['src'] = '/src="([^"]*)"/';
            $search['width'] = '/width="[0-9]+"/';
            $search['height'] = '/height="[0-9]+"/';
            $search['imagephp'] = '/image.php/';
            $search['phpthumb'] = '/phpThumb.php/';

            preg_match($search['process'], $imagetag, $result['process']);

            // check process html attribute...abort if false
            if (count($result['process']) == 2) {
                if ($result['process'][1] == 0) {
                    $processImage = false;
                }

                $newtag = preg_replace('/ process="[0-1]"/', '', $newtag);
                $newtag = str_replace('  ', ' ', $newtag);
            }

            if ($processImage) {
                preg_match($search['src'], $imagetag, $result['src']);
                preg_match($search['width'], $imagetag, $result['width']);
                preg_match($search['height'], $imagetag, $result['height']);

                $result['src'][1] = trim($result['src'][1]);
                if (!empty($result['src'][1])) {
                    $image['src'] = $result['src'][1];

                    if (!empty($result['width'][0])) {
                        $image['width'] = str_replace('width="', '', $result['width'][0]);
                        $image['width'] = trim(str_replace('"', '', $image['width']));
                    }

                    if (!empty($result['height'][0])) {
                        $image['height'] = str_replace('height="', '', $result['height'][0]);
                        $image['height'] = trim(str_replace('"', '', $image['height']));
                    }

                    preg_match($search['imagephp'], $image['src'], $result['imagephp']);
                    preg_match($search['phpthumb'], $image['src'], $result['phpthumb']);

                    if (count($result['imagephp']) == 0 && count($result['phpthumb']) == 0) {
                        $ptoptions = array();
                        if (!empty($image['width']))
                            $ptoptions[] = 'w=' . $image['width'];
                        if (!empty($image['height']))
                            $ptoptions[] = 'h=' . $image['height'];

                        // thanks to netProphET for this addition
                        // this allows you to set phpthumb parameters in the image URL
                        // see http://modxcms.com/forums/index.php/topic,14858.msg102750.html#msg102750
                        if (preg_match("/^([^\?]*)\?(.*)$/", $image['src'], $ma)) {
                            $aParam = array();
                            $image['src'] = $ma[1];

                            $ma[2] = str_replace('{}', '[]', $ma[2]);
                            parse_str(urldecode($ma[2]), $aParam);

                            foreach ($aParam as $k => $param) {
                                // clean parameter keys
                                $k = str_replace('#038;', '', $k);
                                $k = str_replace('amp;', '', $k);

                                if (is_array($param) && count($param) > 0) {
                                    foreach ($param as $element) {
                                        $ptoptions[] = "{$k}[]={$element}";
                                    }
                                } else {
                                    if ($k == 'process' && $param == '0') {
                                        $processImage = false;
                                    }

                                    $ptoptions[] = "{$k}={$param}";
                                }
                            }
                        }

                        unset($aParam, $param);

                        // call phpthumb only if there are any parameters set
                        if (count($ptoptions) == 0) {
                            $processImage = false;
                        } else {
                            $additional_options = array();

                            // allow enlargement of images, uncomment this if you want
                            $additional_options[] = 'aoe=1';

                            // best quality
                            $additional_options[] = 'q=100';

                            // merge default options and overwrite default options if necessary
                            $phpthumb_options = array_merge($additional_options, $ptoptions);
                        }

                        if ($processImage) {
                            $ptoptionstring = '';
                            for ($i = 0; $i < count($phpthumb_options); $i++) {
                                if ($i != 0)
                                    $ptoptionstring .= '&';
                                $ptoptionstring .= $phpthumb_options[$i];
                            }

                            $newsrc = $this->getUrl($image['src'], $ptoptionstring);
                            $newtag = preg_replace('/src="([^"]*)"/', 'src="' . $newsrc . '"', $newtag);
                            $newtag = preg_replace('/ width="[^"]*"/', '', $newtag);
                            $newtag = preg_replace('/ height="[^"]*"/', '', $newtag);
                            $newtag = str_replace('  ', ' ', $newtag);
                        }
                    }
                }
            }

            if ($newtag != $imagetag) {
                $imagesearch[$n] = $imagetag;
                $imagereplace[$n] = $newtag;
            }
        }

        if (count($imagesearch) > 0 && count($imagesearch) == count($imagereplace)) {
            $content = str_replace($imagesearch, $imagereplace, $content);
        }

        return $content;
    }

    /**
     * Creates an URL to phpThumb
     *
     * @param  string $image        the path/URL to the image
     * @param  string $params       phpThumb parameters
     * @param  bool   $xhtmlOutput  if set to false, URLs won't contain escaped HTML entities (e.g. &amp;)
     * @return string
     */
    public function getUrl($image, $params = 'w=800', $xhtmlOutput = true)
    {
        if (!$this->setUpAndWorking) {
            return $image;
        }

        if (!empty($image)) {
            $httpSrc = false;
            if (strtolower(substr($image, 0, 1)) == '/') {
                $image = str_replace($_SERVER['DOCUMENT_ROOT'], '/', $image);
            } elseif (strtolower(substr($image, 0, 4)) == 'http') {
                $wordpress_url = parse_url(get_bloginfo('url'));
                $image_url = parse_url($image);

                // trim URL when it points to the same host
                if ($wordpress_url['host'] == $image_url['host']) {
                    $image = $image_url['path'];
                    $httpSrc = false;
                } else {
                    $httpSrc = true;
                }
            } else {
                $path = '';
                $blogurl = parse_url(get_bloginfo('wpurl'));
                if (isset($blogurl['path'])) {
                    $path = $blogurl['path'];
                }

                $image = $path . '/' . $image;
            }

            $queryString = 'src=' . $image . '&' . $params;

            $clean_urls = false;
            if (get_option('autothumb_clean_urls') == 1) {
                $clean_urls = true;
            }

            if ($clean_urls) {
                $imageSeparator = '';
                if ($httpSrc) {
                    $imageSeparator = '/';
                }

                $query = get_bloginfo('wpurl') . '/' . get_option('autothumb_clean_urls_path') . $imageSeparator . $image . '?' . $params;
            } else {
                $query = get_bloginfo('wpurl') . '/wp-content/plugins/autothumb/image.php?' . $queryString;
            }

            // generate hash for security
            $hash = md5($queryString . $this->highSecurityPassword);

            // append hash to query
            $query .= '&hash=' . $hash;

            if ($xhtmlOutput) {
                // generate XHTML valid URLs
                $query = str_replace('&', '&amp;', $query);
            }

            return $query;
        }

        return false;
    }
}