<?php

if (!class_exists('CQOC_Admin')) {
    class CQOC_Admin
    {
        public static function cqoc_add_menu()
        {
            add_menu_page(
                __('Change Quantity on Checkout For WooCommerce', 'cqoc'),
                __('CQOC', 'cqoc'),
                'manage_options',
                'cqoc-settings-page',
                array( __CLASS__, 'cqocSettingsPage' ),
                'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0nMS4wJyBlbmNvZGluZz0nVVRGLTgnIHN0YW5kYWxvbmU9J25vJyA/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAnLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4nICdodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQnPgo8c3ZnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZycgeG1sbnM6eGxpbms9J2h0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsnIHdpZHRoPScxMDI0JyBoZWlnaHQ9JzEwMjQnPgoKPGcgdHJhbnNmb3JtPSdtYXRyaXgoMTEuNjggMCAwIDkuODEgNTEyIDY4NC4zNSknICA+CjxnIHN0eWxlPScnIHZlY3Rvci1lZmZlY3Q9J25vbi1zY2FsaW5nLXN0cm9rZScgICA+CgkJPGcgdHJhbnNmb3JtPSdtYXRyaXgoMSAwIDAgMSA5LjgxIC0xMS40MiknICA+Cjxwb2x5Z29uIHN0eWxlPSdzdHJva2U6IG5vbmU7IHN0cm9rZS13aWR0aDogMTsgc3Ryb2tlLWRhc2hhcnJheTogbm9uZTsgc3Ryb2tlLWxpbmVjYXA6IGJ1dHQ7IHN0cm9rZS1kYXNob2Zmc2V0OiAwOyBzdHJva2UtbGluZWpvaW46IG1pdGVyOyBzdHJva2UtbWl0ZXJsaW1pdDogNDsgZmlsbDogcmdiKDEwMSwxMDEsMTAxKTsgZmlsbC1ydWxlOiBub256ZXJvOyBvcGFjaXR5OiAxOycgdmVjdG9yLWVmZmVjdD0nbm9uLXNjYWxpbmctc3Ryb2tlJyAgcG9pbnRzPScxMy4yLDEyLjM5IC0yMC42NCwxMi4zOSAtMjQuNzgsLTEyLjM5IDI0Ljc4LC0xMi4zOSAnIC8+CjwvZz4KCQk8ZyB0cmFuc2Zvcm09J21hdHJpeCgxIDAgMCAxIC0yLjQ2IC05LjIyKScgID4KPHBhdGggc3R5bGU9J3N0cm9rZTogbm9uZTsgc3Ryb2tlLXdpZHRoOiAxOyBzdHJva2UtZGFzaGFycmF5OiBub25lOyBzdHJva2UtbGluZWNhcDogYnV0dDsgc3Ryb2tlLWRhc2hvZmZzZXQ6IDA7IHN0cm9rZS1saW5lam9pbjogbWl0ZXI7IHN0cm9rZS1taXRlcmxpbWl0OiA0OyBmaWxsOiByZ2IoMTAxLDEwMSwxMDEpOyBmaWxsLXJ1bGU6IG5vbnplcm87IG9wYWNpdHk6IDE7JyB2ZWN0b3ItZWZmZWN0PSdub24tc2NhbGluZy1zdHJva2UnICB0cmFuc2Zvcm09JyB0cmFuc2xhdGUoLTQ1LjEzLCAtNDAuNzgpJyBkPSdNIDE0LjI0MyAyMS40NzcgQyAxMy4wMzcgMjAuNzQ1IDEyLjY2OSAxOS4yMjUgMTMuMzUxIDE4LjAxOSBDIDE0LjA4NDAwMDAwMDAwMDAwMSAxNi44MTMgMTUuNjU0IDE2LjM5NSAxNi44NiAxNy4xMjcgQyAyMi42NzQgMjAuNTg1IDI2LjY1NSAyNi43MTMgMjkuMzggMzQuNzI5IEMgMzEuNzkxIDQxLjggMzMuMjAyIDUwLjMzOSAzNC4wOTQgNTkuNzE3IEwgNzQuNzQgNTkuNzE3IEMgNzYuMTA0IDU5LjcxNyA3Ny4yNTYgNjAuODY3IDc3LjI1NiA2Mi4yODIgQyA3Ny4yNTYgNjMuNjk2OTk5OTk5OTk5OTk2IDc2LjEwNSA2NC43OTU5OTk5OTk5OTk5OSA3NC43NCA2NC43OTU5OTk5OTk5OTk5OSBMIDMyIDY0Ljc5NTk5OTk5OTk5OTk5IEMgMzAuNTg1IDY0Ljg5OTk5OTk5OTk5OTk5IDI5LjM4IDYzLjkwNTk5OTk5OTk5OTk5IDI5LjI3NSA2Mi40OTE5OTk5OTk5OTk5OSBDIDI4LjQzOCA1Mi41Mzg5OTk5OTk5OTk5OSAyNy4wMjMgNDMuNTc5OTk5OTk5OTk5OTkgMjQuNTYxOTk5OTk5OTk5OTk4IDM2LjM1Mjk5OTk5OTk5OTk5NCBDIDIyLjIwNSAyOS40OTEgMTguOTU2IDI0LjI1MiAxNC4yNDMgMjEuNDc3IHonIHN0cm9rZS1saW5lY2FwPSdyb3VuZCcgLz4KPC9nPgoJCTxnIHRyYW5zZm9ybT0nbWF0cml4KDEgMCAwIDEgLTEzLjYxIDI2LjA2KScgID4KPGNpcmNsZSBzdHlsZT0nc3Ryb2tlOiBub25lOyBzdHJva2Utd2lkdGg6IDE7IHN0cm9rZS1kYXNoYXJyYXk6IG5vbmU7IHN0cm9rZS1saW5lY2FwOiBidXR0OyBzdHJva2UtZGFzaG9mZnNldDogMDsgc3Ryb2tlLWxpbmVqb2luOiBtaXRlcjsgc3Ryb2tlLW1pdGVybGltaXQ6IDQ7IGZpbGw6IHJnYigxMDEsMTAxLDEwMSk7IGZpbGwtcnVsZTogbm9uemVybzsgb3BhY2l0eTogMTsnIHZlY3Rvci1lZmZlY3Q9J25vbi1zY2FsaW5nLXN0cm9rZScgIGN4PScwJyBjeT0nMCcgcj0nNy4xNzYnIC8+CjwvZz4KCQk8ZyB0cmFuc2Zvcm09J21hdHJpeCgxIDAgMCAxIDIxLjkxIDI2LjA2KScgID4KPGNpcmNsZSBzdHlsZT0nc3Ryb2tlOiBub25lOyBzdHJva2Utd2lkdGg6IDE7IHN0cm9rZS1kYXNoYXJyYXk6IG5vbmU7IHN0cm9rZS1saW5lY2FwOiBidXR0OyBzdHJva2UtZGFzaG9mZnNldDogMDsgc3Ryb2tlLWxpbmVqb2luOiBtaXRlcjsgc3Ryb2tlLW1pdGVybGltaXQ6IDQ7IGZpbGw6IHJnYigxMDEsMTAxLDEwMSk7IGZpbGwtcnVsZTogbm9uemVybzsgb3BhY2l0eTogMTsnIHZlY3Rvci1lZmZlY3Q9J25vbi1zY2FsaW5nLXN0cm9rZScgIGN4PScwJyBjeT0nMCcgcj0nNy4xNzYnIC8+CjwvZz4KPC9nPgo8L2c+CjxnIHRyYW5zZm9ybT0nbWF0cml4KDQuMzkgMCAwIDMuODkgMjg0LjU1IDE2MC42MSknIGlkPSc1MzllYWMzNC05MWY1LTRkZDYtYTIxYy1mY2Q0MjI3NTgwYmEnICA+CjxwYXRoIHN0eWxlPSdzdHJva2U6IHJnYigwLDAsMCk7IHN0cm9rZS13aWR0aDogMDsgc3Ryb2tlLWRhc2hhcnJheTogbm9uZTsgc3Ryb2tlLWxpbmVjYXA6IGJ1dHQ7IHN0cm9rZS1kYXNob2Zmc2V0OiAwOyBzdHJva2UtbGluZWpvaW46IG1pdGVyOyBzdHJva2UtbWl0ZXJsaW1pdDogNDsgZmlsbDogcmdiKDEwMSwxMDEsMTAxKTsgZmlsbC1ydWxlOiBub256ZXJvOyBvcGFjaXR5OiAxOycgdmVjdG9yLWVmZmVjdD0nbm9uLXNjYWxpbmctc3Ryb2tlJyAgdHJhbnNmb3JtPScgdHJhbnNsYXRlKC01MCwgLTUwKScgZD0nTSA0MC43NDcgMTMuOTU1IEwgNTkuMjU0MDAwMDAwMDAwMDA1IDEzLjk1NSBDIDYwLjM1MyAxMy45NTUgNjEuMjQyMDAwMDAwMDAwMDA0IDE0Ljg0NSA2MS4yNDIwMDAwMDAwMDAwMDQgMTUuOTQxIEwgNjEuMjQyMDAwMDAwMDAwMDA0IDM4Ljc4NyBMIDg0LjA4NjAwMDAwMDAwMDAxIDM4Ljc4NyBDIDg1LjE4NDAwMDAwMDAwMDAxIDM4Ljc4NyA4Ni4wNzAwMDAwMDAwMDAwMSAzOS42NzUgODYuMDcwMDAwMDAwMDAwMDEgNDAuNzcyOTk5OTk5OTk5OTk2IEwgODYuMDcwMDAwMDAwMDAwMDEgNTkuMjI2IEMgODYuMDcwMDAwMDAwMDAwMDEgNjAuMzI0OTk5OTk5OTk5OTk2IDg1LjE4MyA2MS4yMTE5OTk5OTk5OTk5OTYgODQuMDg2MDAwMDAwMDAwMDEgNjEuMjExOTk5OTk5OTk5OTk2IEwgNjEuMjQyIDYxLjIxMTk5OTk5OTk5OTk5NiBMIDYxLjI0MiA4NC4wNTc5OTk5OTk5OTk5OSBDIDYxLjI0MiA4NS4xNTcgNjAuMzUyIDg2LjA0NCA1OS4yNTQgODYuMDQ0IEwgNDAuNzQ3IDg2LjA0NCBDIDM5LjY1MiA4Ni4wNDQgMzguNzYyIDg1LjE1NTk5OTk5OTk5OTk5IDM4Ljc2MiA4NC4wNTc5OTk5OTk5OTk5OSBMIDM4Ljc2MiA2MS4yMTMgTCAxNS45MTUgNjEuMjEzIEMgMTQuODE5OTk5OTk5OTk5OTk5IDYxLjIxMyAxMy45MyA2MC4zMjUgMTMuOTMgNTkuMjI3MDAwMDAwMDAwMDA0IEwgMTMuOTMgNDAuNzczIEMgMTMuOTMgMzkuNjc0MDAwMDAwMDAwMDEgMTQuODIgMzguNzg3MDAwMDAwMDAwMDA2IDE1LjkxNSAzOC43ODcwMDAwMDAwMDAwMDYgTCAzOC43NjIgMzguNzg3MDAwMDAwMDAwMDA2IEwgMzguNzYyIDE1Ljk0MSBDIDM4Ljc2MiAxNC44NDUgMzkuNjUxIDEzLjk1NSA0MC43NDcgMTMuOTU1IHonIHN0cm9rZS1saW5lY2FwPSdyb3VuZCcgLz4KPC9nPgo8ZyB0cmFuc2Zvcm09J21hdHJpeCg0LjA1IDAgMCA0LjU4IDc1MC43MyAxNTAuMzYpJyBpZD0nOWFjMTZiOTctYzQ3ZC00NjViLWFkNjItNDE5NGU3OTEyNzBkJyAgPgo8cGF0aCBzdHlsZT0nc3Ryb2tlOiByZ2IoMCwwLDApOyBzdHJva2Utd2lkdGg6IDA7IHN0cm9rZS1kYXNoYXJyYXk6IG5vbmU7IHN0cm9rZS1saW5lY2FwOiBidXR0OyBzdHJva2UtZGFzaG9mZnNldDogMDsgc3Ryb2tlLWxpbmVqb2luOiBtaXRlcjsgc3Ryb2tlLW1pdGVybGltaXQ6IDQ7IGZpbGw6IHJnYigxMDEsMTAxLDEwMSk7IGZpbGwtcnVsZTogbm9uemVybzsgb3BhY2l0eTogMTsnIHZlY3Rvci1lZmZlY3Q9J25vbi1zY2FsaW5nLXN0cm9rZScgIHRyYW5zZm9ybT0nIHRyYW5zbGF0ZSgtNTAsIC01MCknIGQ9J00gOTQuNzUgNTAgQyA5NC43NSA1Ni4yMTMgODkuNTE0IDYxLjI1IDgzLjA1NCA2MS4yNSBMIDE2Ljk0NiA2MS4yNSBDIDEwLjQ4NiA2MS4yNSA1LjI1IDU2LjIxMyA1LjI1IDUwIEwgNS4yNSA1MCBDIDUuMjUgNDMuNzg3IDEwLjQ4NiAzOC43NSAxNi45NDU5OTk5OTk5OTk5OTggMzguNzUgTCA4My4wNTMgMzguNzUgQyA4OS41MTQgMzguNzUgOTQuNzUgNDMuNzg3IDk0Ljc1IDUwIEwgOTQuNzUgNTAgeicgc3Ryb2tlLWxpbmVjYXA9J3JvdW5kJyAvPgo8L2c+Cjwvc3ZnPg==',
                '55.6'
            );
            // add_submenu_page('cqoc-settings-page', __('Change Quantity on Checkout For WooCommerce', 'cqoc'), __('cqoc', 'cqoc'), 'manage_options', 'cqoc-settings-page', array( __CLASS__, 'cqocSettingsPage' ), 1);

            add_action('admin_init', array(__CLASS__ , 'registerSettings'));
        }

        public static function cqocSettingsPage()
        {
            ?>
            <div class="wrap">
                <h1>
                    Change Quantity on Checkout For WooCommerce
                </h1>
                <?php settings_errors(); ?>
                <form action="options.php" method="POST">
                    <?php
                        settings_fields('cqocplugin');
                        do_settings_sections('cqoc-settings-page');

                        submit_button();
                    ?>
                </form>
                <h2> Pro Version Settings </h2>
                <p style="font-size:16px;">
				    <strong><i>
				        Upgrade to <a href="https://www.navonmeshsolution.com/?utm_source=cqocupgradetopro&amp;utm_medium=link&amp;utm_campaign=CQOCLite" target="_blank">Change Quantity on Checkout PRO for WooCommerce</a> to include a quantity field in WooCommerce Checkout blocks and configure other settings for the traditional checkout page.</i></strong>
                </p>
                <img style="width:100%;" src="<?php echo CQOC_PLUGINS_URL . '/assets/images/cqoc-pro-setttings.png'?>" alt="PRO Settings"/>
            </div>
        <?php
        }
        public static function registerSettings()
        {
            if (empty(get_option('cqoc_addQuantityField'))){
                
                add_option('cqoc_addQuantityField', '1');
            }
            
            add_settings_section('cqoc_first_secion', null, null, 'cqoc-settings-page');
        
            add_settings_field('cqoc_addQuantityField', __('Add Quantity Field On Checkout', 'cqoc'), array(__CLASS__, 'checkboxHTML'), 'cqoc-settings-page', 'cqoc_first_secion', array('theName' => 'cqoc_addQuantityField', 'desc'=>'Activate the functionality of this plugin.'));
            register_setting('cqocplugin', 'cqoc_addQuantityField', array('sanitize_callback'=> array(__CLASS__,'sanitizeAddQantityField' ), 'default'=> '1'));
        
            add_settings_field('cqoc_hideDeleteIcon', __('Hide Delete Button', 'cqocp'), array(__CLASS__, 'checkboxHTML'), 'cqoc-settings-page', 'cqoc_first_secion', array('theName' => 'cqoc_hideDeleteIcon', 'desc'=>'Turn on this option to hide the delete symbol for products.'));
            register_setting('cqocplugin', 'cqoc_hideDeleteIcon', array('sanitize_callback'=> array(__CLASS__,'sanitizeDeleteIcon' ), 'default'=> '0'));
        

        }
        public static function checkboxHTML($args)
        {
            ?>
                <input type="checkbox" name="<?php echo $args['theName'] ?>" id="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?> > 
            <?php
            if (!empty($args['desc'])) {
                ?>
                    <label class="desc" for="<?php echo $args['theName'] ?>" > <?php echo $args['desc']; ?> </label>
                <?php
            }   
        }

        public static function sanitizeAddQantityField($input)
        {
            if ($input != 0 and $input != 1) {
                add_settings_error('cqoc_addQuantityField', 'cqoc_addQuantityField_error', 'Add Quantity Field should be enable or disable');
                return get_option('cqoc_addQuantityField');
            }
            return $input;
        }

        public static function sanitizeDeleteIcon($input)
        {
            if ($input != 0 and $input != 1) {
                add_settings_error('cqoc_hideDeleteIcon', 'cqoc_hideDeleteIcon_error', 'Delete Icons should be enable or disable');
                return get_option('cqoc_hideDeleteIcon');
            }
            return $input;
        }

        /**
		 * Add Settings link to WP->Plugins page.
		 *
		 * @param array $links - Links to be displayed.
		 * @return array $links - Includes custom links.
		 * @since 3.0.0
		 */
        public static function cqoc_settings_link( $links ){
            $settings_link = '<a href="admin.php?page=cqoc-settings-page">' . __( 'Settings', 'cqoc' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
        }
    }
}
new CQOC_Admin();