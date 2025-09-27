<?php
/**
 * The Gemini API Client class.
 *
 * This class handles all interactions with the Gemini AI API.
 *
 * @since      1.0.0
 * @package    Gemini_SEO_Helper
 * @subpackage Gemini_SEO_Helper/includes
 * @author     Your Name <email@example.com>
 */
class Gemini_API_Client {

	/**
	 * The Gemini API Key.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_key    The Gemini API Key.
	 */
	private $api_key;

	/**
	 * The Advanced SEO Prompt.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $advanced_prompt    The Advanced SEO Prompt.
	 */
	private $advanced_prompt;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->api_key         = get_option( 'gemini_seo_helper_api_key', '' );
		$this->advanced_prompt = get_option( 'gemini_seo_helper_advanced_prompt', 'You are an experienced Google SEO manager with 15 years of experience. Generate SEO titles, meta descriptions, and focus keywords according to the latest Google guidelines and best practices.' );
	}

	/**
	 * Make a request to the Gemini API.
	 *
	 * @since    1.0.0
	 * @param    string    $prompt    The prompt to send to the Gemini API.
	 * @return   array|WP_Error      The API response or a WP_Error object on failure.
	 */
	public function make_request( $prompt ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'gemini_api_error', __( 'Gemini API Key is not set. Please configure it in the plugin settings.', 'gemini-seo-helper' ) );
		}

		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->api_key;

		$headers = array(
			'Content-Type' => 'application/json',
		);

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array( 'text' => $this->advanced_prompt . '\n\n' . $prompt ),
					),
				),
			),
		);

		$args = array(
			'headers'     => $headers,
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'timeout'     => 45, // seconds
			'data_format' => 'body',
		);

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( 200 !== $response_code ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'gemini-seo-helper' );
			return new WP_Error( 'gemini_api_error', sprintf( __( 'Gemini API Error (%d): %s', 'gemini-seo-helper' ), $response_code, $error_message ) );
		}

		return $data;
	}

	/**
	 * Generate SEO title, meta description, and focus keywords.
	 *
	 * @since    1.0.0
	 * @param    string    $content    The content of the post.
	 * @return   array|WP_Error      An array containing SEO title, meta description, and focus keywords, or a WP_Error object.
	 */
	public function generate_seo_data( $content ) {
		$prompt = sprintf(
			__( "Given the following content, generate an SEO title (max 60 characters), a meta description (max 160 characters), and 3-5 focus keywords. Provide the output in a JSON format with keys 'title', 'description', and 'keywords' (an array of strings).\n\nContent: %s", 'gemini-seo-helper' ),
			$content
		);

		$response = $this->make_request( $prompt );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Extract the text from the Gemini API response.
		$generated_text = '';
		if ( isset( $response['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$generated_text = $response['candidates'][0]['content']['parts'][0]['text'];
		}

		// Attempt to parse the JSON output.
		$seo_data = json_decode( $generated_text, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'gemini_api_parse_error', __( 'Failed to parse JSON response from Gemini API.', 'gemini-seo-helper' ) . ' Raw response: ' . $generated_text );
		}

		// Validate the structure of the parsed data.
		if ( ! isset( $seo_data['title'], $seo_data['description'], $seo_data['keywords'] ) || ! is_array( $seo_data['keywords'] ) ) {
			return new WP_Error( 'gemini_api_invalid_format', __( 'Gemini API returned data in an unexpected format.', 'gemini-seo-helper' ) . ' Raw response: ' . $generated_text );
		}

		return $seo_data;
	}
}

