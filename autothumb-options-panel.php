<div class="wrap">
    <div class="icon32"><br /></div>
    <h2>Autothumb</h2>    
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>  
        <div id="poststuff" class="ui-sortable">    
            <div class="postbox">
            <h3><?php _e('Settings') ?></h3>

                <div class="inside">
                    <table class="form-table">
                            <tr>
                                    <th scope="row">
                                        <label for="autothumb_high_security_password">High Security Password</label>
                                    </th>
                                    <td>
                                            <input type="text" class="regular-text" id="autothumb_high_security_password" name="autothumb_high_security_password"  value="<?php echo(get_option("autothumb_high_security_password")); ?>" /><br />
                                            
                                            Please use a long random string as password. This password will prevent abuse of the script by calling different URLs (would create huge server load).<br />
                                            You can use one of the following strings:
                                            <ul>
                                                <li><kbd><?php echo wp_generate_password(50, true) ?></kbd></li>
                                                <li><kbd><?php echo wp_generate_password(50, true) ?></kbd></li>
                                                <li><kbd><?php echo wp_generate_password(50, true) ?></kbd></li>
                                            </ul>
                                    </td>
                            </tr>
                    </table>
                    
                  
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="page_options" value="autothumb_high_security_password" />
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" name="Submit" value="<?php _e('Save Changes') ?>" />
                    </p>                
                    
                </div>
            </div>
            
            <div class="postbox">
                <h3>Information</h3>
                <div class="inside less">
                    <p>Plugin: AutoThumb | Version: 0.3 | <?php _e('Author') ?>: <a href="http://ailoo.net">Mathias Geat</a><br /></p>
                </div>
            </div>
        </div>
    </form>
</div>