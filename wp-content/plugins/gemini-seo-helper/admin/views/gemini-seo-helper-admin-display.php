<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Gemini_SEO_Helper
 * @subpackage Gemini_SEO_Helper/admin/partials
 */
?>

<div class="wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'gemini_seo_helper_settings_group' );
		do_settings_sections( 'gemini-seo-helper' );
		submit_button();
		?>
	</form>

</div>

