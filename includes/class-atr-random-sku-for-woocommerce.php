<?php
if (!defined('ABSPATH'))
    exit;

class ATR_random_sku_for_Woocommerce {

    /**
     * The single instance of ATR_random_sku_for_Woocommerce.
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct($file = '', $version = '1.0.0') {

        $this->_version = $version;
        $this->_token = 'atr_random_sku_for_woocommerce';

        // Load plugin environment variables
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        register_activation_hook($this->file, array($this, 'install'));

        // Load admin JS & CSS
        //add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        //add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
        // Load API for generic admin functions
        if (is_admin()) {
            $this->admin = new ATR_random_sku_for_Woocommerce_Admin_API();
        }
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
        // Handle localisation
        $this->load_plugin_textdomain();
        add_action('init', array($this, 'load_localisation'), 0);

        // Add auto sku button to product edit page
        add_action('woocommerce_product_options_general_product_data', array($this, 'woo_add_custom_general_fields'));

        // **** Check if the suggested sku exist in DB *****
        add_action('admin_footer', array($this, 'atr_check_sku_action_javascript'));
        add_action('wp_ajax_atr_check_sku_action', array($this, 'atr_check_sku_callback'));
    }

// End __construct ()
    // Add auto sku button to product edit page
    public function woo_add_custom_general_fields() {

        global $woocommerce, $post;

        echo '<div class="options_group">';
        ?>

<table >
  <tr>
    <th rowspan="2"><input id="auto-sku" type="button" class="button" value="auto sku" /></th>
    <td><input id="test_sku0" type="radio" value="0" name="test_sku" /><?php _e('Generate random SKU') ?>&nbsp;<input type="checkbox" name="overwrite" class="overwrite" value="no">Over write sku textbox<br /></td>
  </tr>
  <tr>
    <td><input id="test_sku1" type="radio" value="1" name="test_sku" checked /><?php _e('just check current SKU') ?></td>
  </tr>
</table>
        <p class="auto_sku_message">Select option and click the button.<br />"random" will replace the sku with random one and will check it.<br /> "just check" will check the sku without replacing it in the textbox.</p>
            <?php
            echo '</div>';
        }

        // **** Check if the suggested sku exist in DB *****
        function atr_check_sku_action_javascript() {
            ?>
        <script type="text/javascript" >
            jQuery(document).ready(function ($) {
                // onload
                if ($("#test_sku0").attr("checked"))
                    jQuery('#auto-sku').prop('value', 'auto sku');
                else
                    jQuery('#auto-sku').prop('value', 'check sku ');
                // On radio change
                $("input[name=test_sku]:radio").change(function () {
                    if ($("#test_sku0").attr("checked"))
                        jQuery('#auto-sku').prop('value', 'auto sku');
                    else
                        jQuery('#auto-sku').prop('value', 'check sku ');
                    //$('#log').val($('#log').val()+ $(this).val() + '|');
                })
                jQuery('#auto-sku').click(function (event) {
                    event.preventDefault();
                    var random_id_check;
                    var test_skuValue = jQuery("input[name='test_sku']:checked").val();
                    if ((!jQuery('#_sku').val().length > 0) && (test_skuValue === '1')) {
                        alert('sku is empty!');
                    } else {
                        if (test_skuValue === '0') {
                            random_id_check = <?php
        $random_number = '';
        if (get_option('atr_select_sku_format') === 'charactersforsku') {
            if ((get_option('atr_sku_length') != '') && (get_option('atr_characters_for_SKU') != ''))  {
                $sku_characters = get_option('atr_characters_for_SKU');
                $sku_length = get_option('atr_sku_length');
                $random_number = 'makeid("' . $sku_characters . '",' . $sku_length . ')';
            } else {
                $random_number = 'makeid("abcdefghijklmnopqrstuvwxyz0123456789", 8)';
            }
        }
        if (get_option('atr_select_sku_format') === 'maxminsku') {
            //$random_number = 'randomNumberFromRange(100000000, 999999999)';
            if ((get_option('atr_min_number_for_number') != '') && (get_option('atr_max_number_for_number') != '') ) {
                $min_num = get_option('atr_min_number_for_number');
                $max_num = get_option('atr_max_number_for_number');
                $random_number = 'randomNumberFromRange(' . $min_num . ',' . $max_num . ')';
            } else {
                $random_number = 'randomNumberFromRange(100000000, 999999999)';
            }
        }
//$random_number = 'makeid()';
//$random_number = rand(100000,999999); 
        echo $random_number;
        ?>
                        } else {
                            random_id_check = jQuery('#_sku').val();
                        }
                        var data = {
                            'action': 'atr_check_sku_action',
                            'sku_to_pass': random_id_check
                        };
                        //var random_id_check = randomNumberFromRange(100000, 999999);
                        jQuery.post(ajaxurl, data, function (response) {
                            if (response === '0') { // select count = 0 no much sku found in db
                                if (jQuery('#_sku').val().length > 0) {
                                    if (jQuery('.overwrite').prop("checked") == true) {
                                        jQuery('.auto_sku_message').html('<span style="color:blue;font-weight:bold;">' + random_id_check + '</span> not exists! Pasted to sku field.');
                                        jQuery('#_sku').val(random_id_check);
                                        //alert("no-overwrite is checked.");
                                    } else {
                                        jQuery('.auto_sku_message').html('<span style="color:blue;font-weight:bold;">' + random_id_check + '</span> not exists! You can copy paste it. ');
                                        //alert("no-overwrite is unchecked.");
                                    }
                                } else {
                                    jQuery('#_sku').val(random_id_check);
                                    jQuery('.auto_sku_message').html(random_id_check + ' not exists! Pasted to sku field.');
                                }

                                //jQuery('#auto-sku').prop('value', 'Random sku: ' + jQuery('#_sku').val() + ' not exists! You can use it.');

                                jQuery('.auto_sku_message').css('color', 'green');
                            } else { // select count > 0
                                jQuery('.auto_sku_message').html('<span style="color:blue;font-weight:bold;">' + random_id_check + '</span> already exists! Found ' + response + ' products with this sku');
                                jQuery('.auto_sku_message').css('color', 'red');
                            }
                        });
                    }


                    //var random_id_check = <?php //$random_number = 'makeid()'; echo $random_number;            ?>;
                    //var random_id_check = jQuery('#_sku').val(); // For TEST only


                });
            });

            // generate random sku and write it in the sku textbox
            function randomNumberFromRange(min, max)
            {
                var randomNumber = Math.floor(Math.random() * (max - min + 1) + min);
                return randomNumber;
            }
            function makeid(possible, sku_length)
            {
                var text = "";
                //var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
                    <?php //if (!get_option('min_number_for_number'))  ?>
                //var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
                for (var i = 0; i < sku_length; i++)
                    text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
            }
        </script> 
        <?php
    }

//    function getRandomString($length = 8) {
//        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//        $string = '';
//
//        for ($i = 0; $i < $length; $i++) {
//            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
//        }
//
//        return $string;
//    }

    function atr_check_sku_callback() {
        global $wpdb;
        $sku = strval($_POST['sku_to_pass']);
        //$product_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value= %s LIMIT 1", $sku));
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT count(meta_value) FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value= %s LIMIT 1", $sku));
        wp_reset_query();
        echo $product_id;
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles($hook = '') {
        wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-admin');
    }

// End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts($hook = '') {
        wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery'), $this->_version);
        wp_enqueue_script($this->_token . '-admin');
    }

// End admin_enqueue_scripts ()
    /**
     * Load plugin localisation
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_localisation() {
        load_plugin_textdomain('atr-random-sku-for-woocommerce', false, dirname(plugin_basename($this->file)) . '/lang/');
    }

// End load_localisation ()

    /**
     * Load plugin textdomain
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain() {
        $domain = 'atr-random-sku-for-woocommerce';

        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, dirname(plugin_basename($this->file)) . '/lang/');
    }

// End load_plugin_textdomain ()

    /**
     * Main ATR_random_sku_for_Woocommerce Instance
     *
     * Ensures only one instance of ATR_random_sku_for_Woocommerce is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see ATR_random_sku_for_Woocommerce()
     * @return Main ATR_random_sku_for_Woocommerce instance
     */
    public static function instance($file = '', $version = '1.0.0') {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

// End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install() {
        $this->_log_version_number();
    }

// End install ()

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number() {
        update_option($this->_token . '_version', $this->_version);
    }

// End _log_version_number ()
}
