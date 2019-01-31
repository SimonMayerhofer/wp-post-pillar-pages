<?php
/**
 * Options Page for the plugin settings.
 *
 * @package pillar-pages
 */

namespace PillarPages;

 /**
  * Settings Page for Pillar Pages Plugin
  */
final class SettingsPage {
	/**
	 * Holds the values to be used in the fields callbacks
	 *
	 * @var $options
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'Pillar Pages',
			'Pillar Pages',
			'manage_options',
			'pillar-pages-settings',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'pillar_pages' );
		?>
		<div class="wrap">
			<h1>Pillar Pages Settings</h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'pillar_pages' );
				do_settings_sections( 'pillar-pages-settings' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'pillar_pages', // Option group
			'pillar_pages', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'pillar_pages_setting_section', // ID
			'Pillar Page IDs', // Title
			array( $this, 'print_section_info' ), // Callback
			'pillar-pages-settings' // Page
		);

		add_settings_field(
			'post_ids', // ID
			'Post IDs', // Title
			array( $this, 'post_ids_callback' ), // Callback
			'pillar-pages-settings', // Page
			'pillar_pages_setting_section' // Section
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 */
	public function sanitize( $input ) {
		$new_input = array();

		if ( isset( $input['post_ids'] ) ) {
			$new_input['post_ids'] = sanitize_text_field( $input['post_ids'] );
			// remove everything except commas and numbers.
			$new_input['post_ids'] = preg_replace( '/[^\d,]/', '', $new_input['post_ids'] );
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		?>
		<p>Enter all IDs from posts which should be used as pillar pages. Separate them by commas. e.g. <code>1, 32, 3432, 57</code>.</p>
		<p><span style="color: #f00;">WARNING: </span> If you add (or remove) IDs here or you change the permalink of a post you added here, you need to <strong>save your permalinks</strong> again.<br>This is necessary to display the posts of the pillar pages later on.</p>
		<p><a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">Go to permalink options page</a></p>
		<p><strong style="color: #f00;">IF YOU DELETE A PILLAR PAGE YOU CAN'T RESTORE IT.</strong><br>If you remove it from the following field, you can add simply add it again. But if you delete it from WordPress 'move it to trash' it's gone.<br>You can only add it back if you manually add the Custom Post Type inside a plugin or theme again.</p>
		<?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function post_ids_callback() {
		printf(
			'<input type="text" id="post_ids" name="pillar_pages[post_ids]" value="%s" />',
			isset( $this->options['post_ids'] ) ? esc_attr( $this->options['post_ids'] ) : ''
		);
	}
}
