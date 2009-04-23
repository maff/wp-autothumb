<div class="wrap">
<div class="icon32" id="icon-options-general"><br/></div>
        <h2>Autothumb Options</h2>
        <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <fieldset class="options">
                <table class="form-table">
                        <tr>
                                <th scope="row"><label for="autothumb_password">PHPThumb High Security Password:</label></th>
                                <td>
                                        <input type="text" class="regular-text" id="autothumb_high_security_password" name="autothumb_high_security_password"  value="<?php echo get_option("autothumb_high_security_password"); ?>" /><br />
                                </td>
                        </tr>
                </table>
        </fieldset>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="autothumb_high_security_password" />
        <p class="submit">
                <input type="submit" class="button-primary" name="Submit" value="<?php _e('Save Changes') ?>" />
        </p>
        </form>
</div>