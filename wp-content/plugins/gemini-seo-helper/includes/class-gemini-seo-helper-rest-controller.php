<?php
/**
 * The REST API controller for the Gemini SEO Helper plugin.
 *
 * This class handles registering custom REST API endpoints for Gemini AI interactions.
 *
 * @since      1.0.0
 * @package    Gemini_SEO_Helper
 * @subpackage Gemini_SEO_Helper/includes
 * @author     Your Name <email@example.com>
 */

class Gemini_SEO_Helper_REST_Controller extends WP_REST_Controller {

	/**
	 * The namespace of this controller.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $namespace    The namespace of this controller.
	 */
	protected $namespace;

	/**
	 * The Gemini API Client instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Gemini_API_Client    $gemini_api_client    The Gemini API Client instance.
	 */
	private $gemini_api_client;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->namespace = 'gemini-seo-helper/v1';
		$this->gemini_api_client = new Gemini_API_Client();
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/generate-seo-data',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_seo_data_callback' ),
				'permission_callback' => array( $this, 'generate_seo_data_permissions_check' ),
				'args'                => array(
					'content' => array(
						'description'       => __( 'The content of the post to analyze.', 'gemini-seo-helper' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);
	}

	/**
	 * Checks if a given request has access to generate SEO data.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_Error|bool            True if the request has access, WP_Error object otherwise.
	 */
	public function generate_seo_data_permissions_check( WP_REST_Request $request ) {
		// Only allow users with 'edit_posts' capability to access this endpoint.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'gemini-seo-helper' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Check if API key is set.
		$api_key = get_option( 'gemini_seo_helper_api_key' );
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'rest_gemini_api_key_missing',
				__( 'Gemini API Key is not configured. Please set it in the plugin settings.', 'gemini-seo-helper' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handles the request to generate SEO data using Gemini AI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function generate_seo_data_callback( WP_REST_Request $request ) {
		$content = $request->get_param( 'content' );

		$seo_data = $this->gemini_api_client->generate_seo_data( $content );

		if ( is_wp_error( $seo_data ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $seo_data->get_error_message(),
				),
				$seo_data->get_error_code() === 'rest_gemini_api_key_missing' ? 403 : 500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $seo_data,
			),
			200
		);
	}
}

