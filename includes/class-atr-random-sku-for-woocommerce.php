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

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action('init', array($this, 'load_localisation'), 0);

        // Add auto sku button to product edit page
        add_action('woocommerce_product_options_general_product_data', array($this, 'woo_add_custom_general_fields'));

        // **** Check if the suggested sku exist in DB *****
        add_action('admin_footer', array($this, 'my_action_javascript'));
        add_action('wp_ajax_my_action', array($this, 'atr_get_sku_callback'));
    }

// End __construct ()
 
    // Add auto sku button to product edit page
    public function woo_add_custom_general_fields() {

        global $woocommerce, $post;

        echo '<div class="options_group">';
        ?>
        <input id="auto-sku" type="button" class="button" value="auto sku" />&nbsp;&nbsp;
        <input id="test_sku0" type="radio" value="0" name="test_sku" checked /><?php _e('random') ?>&nbsp;
        <input id="test_sku1" type="radio" value="1" name="test_sku" /><?php _e('just check') ?>&nbsp;<p class="auto_sku_message">Select option and click the button.<br />"random" will replace the sku with random one and will check it.<br /> "just check" will check the sku without replacing it in the textbox.</p>
        <?php
        echo '</div>';
    }

    // **** Check if the suggested sku exist in DB *****
    function my_action_javascript() {
        ?>
        <script type="text/javascript" >
            jQuery(document).ready(function ($) {
                // onload - fir refresh page
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
                    if ((!jQuery('#_sku').val().length > 0) && (test_skuValue == '1')) {
                        alert('sku is empty!');
                    } else {
                        if (test_skuValue == '0') {
                            random_id_check = <?php $random_number = 'makeid()';
        echo $random_number; ?>
                        } else {
                            random_id_check = jQuery('#_sku').val();
                        }
                        var data = {
                            'action': 'my_action',
                            'sku_to_pass': random_id_check
                        };
                        //var random_id_check = randomNumberFromRange(100000, 999999);
                        jQuery.post(ajaxurl, data, function (response) {
                            if (response == '') {
                                jQuery('#_sku').val(random_id_check);
                                //jQuery('#auto-sku').prop('value', 'Random sku: ' + jQuery('#_sku').val() + ' not exists! You can use it.');
                                jQuery('.auto_sku_message').text(jQuery('#_sku').val() + ' not exists! You can use it.');
                                jQuery('.auto_sku_message').css('color', 'green');
                            } else {
                                jQuery('.auto_sku_message').text(response + ' already exists! Please click again.');
                                jQuery('.auto_sku_message').css('color', 'red');
                            }
                        });
                    }


                    //var random_id_check = <?php //$random_number = 'makeid()'; echo $random_number;         ?>;
                    //var random_id_check = jQuery('#_sku').val(); // For TEST only


                });
            });

            // generate random sku and write it in the sku textbox
            function randomNumberFromRange(min, max)
            {
                var randomNumber = Math.floor(Math.random() * (max - min + 1) + min);

                return randomNumber;
            }
            function makeid()
            {
                var text = "";
                //var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
                var possible = "0123456789";
                for (var i = 0; i < 5; i++)
                    text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
            }
        </script> 
        <?php
    }

    function atr_get_sku_callback() {
        global $wpdb;
        $sku = strval($_POST['sku_to_pass']);
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value= %s LIMIT 1", $sku));
        wp_reset_query();
        echo $product_id;
        wp_die(); // this is required to terminate immediately and return a proper response
    }

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
