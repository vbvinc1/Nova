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
			'/generate-seo-data/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_seo_data_callback' ),
				'permission_callback' => array( $this, 'generate_seo_data_permissions_check' ),
				'args'                => array(
					'post_id' => array(
						'description'       => __( 'ID of the post to analyze.', 'gemini-seo-helper' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'content' => array(
						'description'       => __( 'The content of the post to analyze (optional, will fetch if not provided).', 'gemini-seo-helper' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/apply-seo-data/(?P<post_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'apply_seo_data_callback' ),
				'permission_callback' => array( $this, 'generate_seo_data_permissions_check' ), // Same permissions as generation.
				'args'                => array(
					'post_id' => array(
						'description'       => __( 'ID of the post to update.', 'gemini-seo-helper' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'seo_title' => array(
						'description'       => __( 'The SEO title to apply.', 'gemini-seo-helper' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'meta_description' => array(
						'description'       => __( 'The meta description to apply.', 'gemini-seo-helper' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'focus_keywords' => array(
						'description'       => __( 'The focus keywords to apply (comma-separated string).', 'gemini-seo-helper' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
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
	 * Extracts content from a post, handling various formats including custom HTML.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The ID of the post.
	 * @return   string             The extracted content.
	 */
	private function extract_post_content( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$content = $post->post_content;

		// Apply 'the_content' filters to ensure shortcodes are processed and content is formatted.
		// This is crucial for custom HTML posts where content might be stored in raw form or use shortcodes.
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		// Strip HTML tags to get plain text for AI analysis.
		$content = wp_strip_all_tags( $content, true );

		// Limit content length to avoid exceeding API token limits and for performance.
		$max_length = apply_filters( 'gemini_seo_helper_max_content_length', 10000 ); // Default to 10,000 characters.
		if ( strlen( $content ) > $max_length ) {
			$content = substr( $content, 0, $max_length );
		}

		return $content;
	}

	/**
	 * Handles the request to generate SEO data using Gemini AI.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function generate_seo_data_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		$content = $request->get_param( 'content' );

		// If content is not provided in the request, extract it from the post.
		if ( empty( $content ) && $post_id ) {
			$content = $this->extract_post_content( $post_id );
		}

		if ( empty( $content ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No content found for analysis. Please ensure the post has content.', 'gemini-seo-helper' ),
				),
				400
			);
		}

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

	/**
	 * Handles the request to apply SEO data to a post, compatible with Rank Math and Yoast SEO.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function apply_seo_data_callback( WP_REST_Request $request ) {
		$post_id          = $request->get_param( 'post_id' );
		$seo_title        = $request->get_param( 'seo_title' );
		$meta_description = $request->get_param( 'meta_description' );
		$focus_keywords   = $request->get_param( 'focus_keywords' );

		if ( ! $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post ID is required to apply SEO data.', 'gemini-seo-helper' ),
				),
				400
			);
		}

		// Update standard WordPress post meta.
		if ( ! empty( $seo_title ) ) {
			update_post_meta( $post_id, '_gemini_seo_title', $seo_title );
		}
		if ( ! empty( $meta_description ) ) {
			update_post_meta( $post_id, '_gemini_meta_description', $meta_description );
		}
		if ( ! empty( $focus_keywords ) ) {
			update_post_meta( $post_id, '_gemini_focus_keywords', $focus_keywords );
		}

		// Attempt to integrate with Rank Math.
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			if ( ! empty( $seo_title ) ) {
				update_post_meta( $post_id, 'rank_math_title', $seo_title );
			}
			if ( ! empty( $meta_description ) ) {
				update_post_meta( $post_id, 'rank_math_description', $meta_description );
			}
			if ( ! empty( $focus_keywords ) ) {
				update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_keywords );
			}
		}

		// Attempt to integrate with Yoast SEO.
		if ( defined( 'WPSEO_VERSION' ) ) {
			if ( ! empty( $seo_title ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
			}
			if ( ! empty( $meta_description ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
			}
			if ( ! empty( $focus_keywords ) ) {
				// Yoast stores keywords as a comma-separated string in a specific meta key.
				update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keywords );
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'SEO data applied successfully.', 'gemini-seo-helper' ),
			),
			200
		);
	}
}

