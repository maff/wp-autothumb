<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2>AutoThumb</h2>
    <form method="post" action="options.php">
        <?php settings_fields('autothumb'); ?>
        <div id="poststuff" class="ui-sortable">
            <div class="postbox">
                <h3><?php _e('Settings') ?></h3>

                <div class="inside">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="autothumb_the_content">Apply AutoThumb on post/page content?</label>
                            </th>
                            <td>
                                <?php
                                $val = get_option('autothumb_apply_the_content');
                                if ($val !== false)
                                    $val = intval($val);
                                ?>
                                <select id="autothumb_apply_the_content" name="autothumb_apply_the_content">
                                    <option value="1"<?php echo ($val === 1 || $val === false) ? 'selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
                                    <option value="0"<?php echo ($val === 0) ? 'selected="selected"' : ''; ?>><?php _e('No'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label for="autothumb_clean_urls">Use clean URLs?</label>
                            </th>
                            <td>
                                <?php
                                $val = get_option('autothumb_clean_urls');
                                if ($val !== false)
                                    $val = intval($val);
                                ?>
                                <select id="autothumb_clean_urls" name="autothumb_clean_urls">
                                    <option value="1"<?php echo ($val === 1) ? 'selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
                                    <option value="0"<?php echo ($val === 0 || $val === false) ? 'selected="selected"' : ''; ?>><?php _e('No'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <th scope="row">
                            <label for="autothumb_clean_urls_path">Clean URLs base path</label>
                        </th>
                        <td>
                            <?php
                            $val = get_option('autothumb_clean_urls_path');
                            if ($val === false) {
                                $val = 'images';
                            }
                            ?>
                            <input type="text" class="regular-text" id="autothumb_clean_urls_path" name="autothumb_clean_urls_path"  value="<?php echo($val); ?>" /><br />
                        </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button-primary" name="Submit" value="<?php _e('Save Changes') ?>" />
                    </p>

                    <?php
                    $home_path = get_home_path();
                    if ((!file_exists($home_path . '.htaccess') && is_writable($home_path)) || is_writable($home_path . '.htaccess')) {
                        $writable = true;
                    } else {
                        $writable = false;
                    }
                    ?>

                    <?php if (!$writable): ?>
                        <p><?php _e('If your <code>.htaccess</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.') ?></p>
                        <p><textarea rows="7" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php print_r($this->getRewriteRules()) ?></textarea></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="postbox">
                <h3>Information</h3>
                <div class="inside less">
                    <p>Plugin: AutoThumb | Version: <?php echo $this->getVersion() ?> | <?php _e('Author') ?>: <a href="http://ailoo.net">Mathias Geat</a><br /></p>
                </div>
            </div>
        </div>
    </form>
</div>
