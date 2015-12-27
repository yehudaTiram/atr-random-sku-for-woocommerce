<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_random_sku_for_Woocommerce_Settings {

	/**
	 * The single instance of ATR_random_sku_for_Woocommerce_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'atr_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'ATR rand sku Woo', 'atr-random-sku-for-woocommerce' ) , __( 'ATR rand sku Woo', 'atr-random-sku-for-woocommerce' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
	}



	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'atr-random-sku-for-woocommerce' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['standard'] = array(
			'title'					=> __( 'Options', 'atr-random-sku-for-woocommerce' ),
			'description'			=> __( 'Set your preferences', 'atr-random-sku-for-woocommerce' ),
			'fields'				=> array(
 				array(
					'id' 			=> 'select_sku_format',
					'label'			=> __( 'Use a number for SKU', 'wordpress-plugin-template' ),
					'description'           => __( 'Select SKU format as a number or as a string. Set the relevant options in the following fields.', 'wordpress-plugin-template' ),
					'type'			=> 'radio',
					'options'		=> array( 'maxminsku' => 'Use max min', 'charactersforsku' => 'Use string' ),
					'default'		=> 'charactersforsku'
				),   
				array(
					'id' 			=> 'min_number_for_number',
					'label'			=> __( 'Write min number' , 'wordpress-plugin-template' ),
					'description'           => __( 'Use this min number for SKU', 'wordpress-plugin-template' ),
					'type'			=> 'number',
					'default'		=> '100000000',
					'placeholder'           => __( '100000000', 'wordpress-plugin-template' )
				),                             
				array(
					'id' 			=> 'max_number_for_number',
					'label'			=> __( 'Write max number' , 'wordpress-plugin-template' ),
					'description'           => __( 'Use this max number for SKU', 'wordpress-plugin-template' ),
					'type'			=> 'number',
					'default'		=> '999999999',
					'placeholder'           => __( '999999999', 'wordpress-plugin-template' )
				),                             
  
				array(
					'id' 			=> 'characters_for_SKU',
					'label'			=> __( 'Characters for SKU' , 'wordpress-plugin-template' ),
					'description'           => __( 'The SKU contains only these characters.', 'wordpress-plugin-template' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'           => __( 'abcdefghijklmnopqrstuvwxyz0123456789', 'wordpress-plugin-template' )
				), 
				array(
					'id' 			=> 'sku_length',
					'label'			=> __( 'SKU length' , 'wordpress-plugin-template' ),
					'description'           => __( 'Use this length for SKU', 'wordpress-plugin-template' ),
					'type'			=> 'number',
					'default'		=> '',
					'placeholder'           => __( '8', 'wordpress-plugin-template' )
				),                               
                            
                          

			)
		);



		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
//			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
//				$current_section = $_POST['tab'];
//			} else {
//				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
//					$current_section = $_GET['tab'];
//				}
//			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );

				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'ATR random sku for Woocommerce Settings' , 'atr-random-sku-for-woocommerce' ) . '</h2>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();
				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'atr-random-sku-for-woocommerce' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Main ATR_random_sku_for_Woocommerce_Settings Instance
	 *
	 * Ensures only one instance of ATR_random_sku_for_Woocommerce_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see ATR_random_sku_for_Woocommerce()
	 * @return Main ATR_random_sku_for_Woocommerce_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}