<?php
/**
 * Provide a meta box view for the Classic Editor.
 *
 * This file is used to markup the meta box content for the Classic Editor.
 *
 * @package    Gemini_SEO_Helper
 * @subpackage Gemini_SEO_Helper/admin/partials
 */

$post_id = get_the_ID();
$post_content = get_post_field( 'post_content', $post_id );

$seo_title = get_post_meta( $post_id, '_gemini_seo_title', true );
$meta_description = get_post_meta( $post_id, '_gemini_meta_description', true );
$focus_keywords = get_post_meta( $post_id, '_gemini_focus_keywords', true );

?>

<div class="gemini-seo-helper-meta-box">
	<button type="button" class="button button-primary" id="gemini-generate-seo-data-classic">
		<?php esc_html_e( 'Generate SEO Data', 'gemini-seo-helper' ); ?>
	</button>
	<span class="spinner" id="gemini-seo-spinner" style="float: none;"></span>
	<div id="gemini-seo-messages"></div>

	<div class="gemini-seo-output" style="margin-top: 20px;">
		<p>
			<label for="gemini_seo_title"><strong><?php esc_html_e( 'SEO Title', 'gemini-seo-helper' ); ?>:</strong></label><br>
			<input type="text" id="gemini_seo_title" name="_gemini_seo_title" class="large-text" value="<?php echo esc_attr( $seo_title ); ?>">
		</p>
		<p>
			<label for="gemini_meta_description"><strong><?php esc_html_e( 'Meta Description', 'gemini-seo-helper' ); ?>:</strong></label><br>
			<textarea id="gemini_meta_description" name="_gemini_meta_description" class="large-text" rows="3"><?php echo esc_textarea( $meta_description ); ?></textarea>
		</p>
		<p>
			<label for="gemini_focus_keywords"><strong><?php esc_html_e( 'Focus Keywords', 'gemini-seo-helper' ); ?>:</strong></label><br>
			<input type="text" id="gemini_focus_keywords" name="_gemini_focus_keywords" class="large-text" value="<?php echo esc_attr( $focus_keywords ); ?>">
		</p>
	</div>

	<script>
		jQuery(document).ready(function($) {
			$("#gemini-generate-seo-data-classic").on("click", function() {
				var button = $(this);
				var spinner = $("#gemini-seo-spinner");
				var messages = $("#gemini-seo-messages");
				var postContent = $("#content").val(); // For Classic Editor

				button.prop("disabled", true).text("<?php esc_html_e( 'Generating...', 'gemini-seo-helper' ); ?>");
				spinner.addClass("is-active");
				messages.empty().removeClass("notice notice-error notice-success");

				if (!postContent) {
					messages.addClass("notice notice-error").html("<p><?php esc_html_e( 'Please add some content to the post before generating SEO data.', 'gemini-seo-helper' ); ?></p>");
					button.prop("disabled", false).text("<?php esc_html_e( 'Generate SEO Data', 'gemini-seo-helper' ); ?>");
					spinner.removeClass("is-active");
					return;
				}

				$.ajax({
					url: ajaxurl,
					type: "POST",
					data: {
						action: "gemini_generate_seo_data_classic",
						_wpnonce: "<?php echo wp_create_nonce( 'gemini_generate_seo_data_classic_nonce' ); ?>",
						content: postContent,
					},
					success: function(response) {
						if (response.success) {
							$("#gemini_seo_title").val(response.data.title);
							$("#gemini_meta_description").val(response.data.description);
							$("#gemini_focus_keywords").val(response.data.keywords.join(", "));
							messages.addClass("notice notice-success").html("<p><?php esc_html_e( 'SEO data generated successfully!', 'gemini-seo-helper' ); ?></p>");
						} else {
							messages.addClass("notice notice-error").html("<p>" + response.data.message + "</p>");
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						messages.addClass("notice notice-error").html("<p><?php esc_html_e( 'An unknown error occurred.', 'gemini-seo-helper' ); ?> " + errorThrown + "</p>");
					},
					complete: function() {
						button.prop("disabled", false).text("<?php esc_html_e( 'Generate SEO Data', 'gemini-seo-helper' ); ?>");
						spinner.removeClass("is-active");
					}
				});
			});
		});
	</script>
</div>

