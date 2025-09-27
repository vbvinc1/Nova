<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Gemini_SEO_Helper
 * @subpackage Gemini_SEO_Helper/admin
 * @author     Your Name <email@example.com>
 */
class Gemini_SEO_Helper_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/gemini-seo-helper-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/gemini-seo-helper-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Enqueue scripts and styles for the block editor (Gutenberg).
	 *
	 * @since    1.0.0
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			$this->plugin_name . '-editor-script',
			plugin_dir_url( __FILE__ ) . 'js/gemini-seo-helper-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-data', 'wp-compose', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-editor-script',
			'geminiSeoHelper',
			array(
				'restUrl' => esc_url_raw( rest_url( 'gemini-seo-helper/v1/generate-seo-data' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'generateSeoData' => __( 'Generate SEO Data', 'gemini-seo-helper' ),
					'generating'      => __( 'Generating...', 'gemini-seo-helper' ),
					'applySeoData'    => __( 'Apply SEO Data', 'gemini-seo-helper' ),
					'applying'        => __( 'Applying...', 'gemini-seo-helper' ),
					'seoTitle'        => __( 'SEO Title', 'gemini-seo-helper' ),
					'metaDescription' => __( 'Meta Description', 'gemini-seo-helper' ),
					'focusKeywords'   => __( 'Focus Keywords', 'gemini-seo-helper' ),
					'apiKeyMissing'   => __( 'Gemini API Key is missing. Please configure it in the plugin settings.', 'gemini-seo-helper' ),
					'noPostId'        => __( 'Could not determine the post ID.', 'gemini-seo-helper' ),
					'generatedSuccessfully' => __( 'SEO data generated successfully!', 'gemini-seo-helper' ),
					'error'           => __( 'Error:', 'gemini-seo-helper' ),
				),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-editor-style',
			plugin_dir_url( __FILE__ ) . 'css/gemini-seo-helper-editor.css',
			array( 'wp-components' ),
			$this->version,
			'all'
		);
	}

	/**
	 * Add a new menu item under the main WordPress admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'Gemini SEO Helper Settings', 'gemini-seo-helper' ),
			__( 'Gemini SEO', 'gemini-seo-helper' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-superhero',
			100
		);
	}

	/**
	 * Render the admin page for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/gemini-seo-helper-admin-display.php';
	}

	/**
	 * Register all settings for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Register a setting for the Gemini API Key.
		register_setting(
			'gemini_seo_helper_settings_group',
			'gemini_seo_helper_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		// Register a setting for the Advanced SEO Prompt.
		register_setting(
			'gemini_seo_helper_settings_group',
			'gemini_seo_helper_advanced_prompt',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => 'You are an experienced Google SEO manager with 15 years of experience. Generate SEO titles, meta descriptions, and focus keywords according to the latest Google guidelines and best practices.',
			)
		);

		// Register a setting for the Max Content Length for AI analysis.
		register_setting(
			'gemini_seo_helper_settings_group',
			'gemini_seo_helper_max_content_length',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 10000,
				'description'       => __( 'Maximum number of characters to send to Gemini for content analysis.', 'gemini-seo-helper' ),
			)
		);

		// Add a settings section for API settings.
		add_settings_section(
			'gemini_seo_helper_api_section',
			__( 'Gemini API Settings', 'gemini-seo-helper' ),
			array( $this, 'gemini_api_section_callback' ),
			'gemini-seo-helper'
		);

		// Add a settings field for the Gemini API Key.
		add_settings_field(
			'gemini_seo_helper_api_key_field',
			__( 'Gemini API Key', 'gemini-seo-helper' ),
			array( $this, 'gemini_api_key_callback' ),
			'gemini-seo-helper',
			'gemini_seo_helper_api_section'
		);

		// Add a settings section for Prompt settings.
		add_settings_section(
			'gemini_seo_helper_prompt_section',
			__( 'Advanced SEO Prompt', 'gemini-seo-helper' ),
			array( $this, 'gemini_prompt_section_callback' ),
			'gemini-seo-helper'
		);

		// Add a settings field for the Advanced SEO Prompt.
		add_settings_field(
			'gemini_seo_helper_advanced_prompt_field',
			__( 'Custom Prompt', 'gemini-seo-helper' ),
			array( $this, 'gemini_advanced_prompt_callback' ),
			'gemini-seo-helper',
			'gemini_seo_helper_prompt_section'
		);

		// Add a settings section for Advanced settings.
		add_settings_section(
			'gemini_seo_helper_advanced_section',
			__( 'Advanced Settings', 'gemini-seo-helper' ),
			array( $this, 'gemini_advanced_section_callback' ),
			'gemini-seo-helper'
		);

		// Add a settings field for Max Content Length.
		add_settings_field(
			'gemini_seo_helper_max_content_length_field',
			__( 'Max Content Length', 'gemini-seo-helper' ),
			array( $this, 'gemini_max_content_length_callback' ),
			'gemini-seo-helper',
			'gemini_seo_helper_advanced_section'
		);
	}

	/**
	 * Callback for the Gemini API Settings section.
	 *
	 * @since    1.0.0
	 */
	public function gemini_api_section_callback() {
		echo '<p>' . esc_html__( 'Enter your Gemini API key to enable AI-powered SEO features.', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Callback for the Gemini API Key field.
	 *
	 * @since    1.0.0
	 */
	public function gemini_api_key_callback() {
		$api_key = get_option( 'gemini_seo_helper_api_key' );
		echo '<input type="text" id="gemini_seo_helper_api_key" name="gemini_seo_helper_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'You can get your Gemini API key from the Google AI Studio.', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Callback for the Advanced SEO Prompt section.
	 *
	 * @since    1.0.0
	 */
	public function gemini_prompt_section_callback() {
		echo '<p>' . esc_html__( 'Customize the prompt used to instruct Gemini AI for SEO generation.', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Callback for the Advanced SEO Prompt field.
	 *
	 * @since    1.0.0
	 */
	public function gemini_advanced_prompt_callback() {
		$advanced_prompt = get_option( 'gemini_seo_helper_advanced_prompt' );
		echo '<textarea id="gemini_seo_helper_advanced_prompt" name="gemini_seo_helper_advanced_prompt" rows="5" cols="50" class="large-text">' . esc_textarea( $advanced_prompt ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'This prompt defines the persona and instructions for Gemini AI when generating SEO content. Default: You are an experienced Google SEO manager with 15 years of experience. Generate SEO titles, meta descriptions, and focus keywords according to the latest Google guidelines and best practices.', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Callback for the Advanced Settings section.
	 *
	 * @since    1.0.0
	 */
	public function gemini_advanced_section_callback() {
		echo '<p>' . esc_html__( 'Configure advanced options for the Gemini SEO Helper.', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Callback for the Max Content Length field.
	 *
	 * @since    1.0.0
	 */
	public function gemini_max_content_length_callback() {
		$max_length = get_option( 'gemini_seo_helper_max_content_length' );
		echo '<input type="number" id="gemini_seo_helper_max_content_length" name="gemini_seo_helper_max_content_length" value="' . esc_attr( $max_length ) . '" class="small-text" min="1000" step="100" />';
		echo '<p class="description">' . esc_html__( 'Set the maximum number of characters from the post content to send to Gemini for analysis. Adjust based on Gemini API limits and performance. (Default: 10000)', 'gemini-seo-helper' ) . '</p>';
	}

	/**
	 * Add a meta box to the post editing screen for the Classic Editor.
	 *
	 * @since    1.0.0
	 */
	public function add_seo_meta_box() {
		add_meta_box(
			'gemini-seo-helper-meta-box',
			__( 'Gemini SEO Helper', 'gemini-seo-helper' ),
			array( $this, 'render_seo_meta_box' ),
			array( 'post', 'page' ), // Limit to post and page post types.
			'normal',
			'high'
		);
	}

	/**
	 * Render the content of the SEO meta box for the Classic Editor.
	 *
	 * @since    1.0.0
	 */
	public function render_seo_meta_box() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/gemini-seo-helper-meta-box-display.php';
	}

}

