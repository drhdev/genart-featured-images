<?php
/**
 * Plugin Name:       GenArt Featured Images
 * Description:       Generate abstract WebP featured images for posts and apply SEO-friendly metadata.
 * Version:           0.1.1
 * Author:            drhdev
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       genart-featured-images
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Genart_Featured_Images' ) ) {
	/**
	 * Main plugin class.
	 */
	final class Genart_Featured_Images {

		/**
		 * Option name.
		 *
		 * @var string
		 */
		const OPTION_NAME = 'genart_featured_images_settings';

		/**
		 * Settings page slug.
		 *
		 * @var string
		 */
		const PAGE_SLUG = 'genart-featured-images';

		/**
		 * Nonce action.
		 *
		 * @var string
		 */
		const NONCE_ACTION = 'genart_featured_images_bulk';

		/**
		 * Plugin constructor.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'init_settings' ) );
			add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'register_post_metabox' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );

			add_action( 'wp_ajax_genart_featured_images_bulk_process', array( $this, 'handle_bulk_ajax' ) );
			add_action( 'wp_ajax_genart_featured_images_dry_run', array( $this, 'handle_dry_run' ) );
			add_action( 'wp_ajax_genart_featured_images_generate_single', array( $this, 'handle_generate_single_ajax' ) );
		}

		/**
		 * Loads translations.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'genart-featured-images', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Gets default plugin settings.
		 *
		 * @return array<string, string>
		 */
		private function get_default_settings() {
			return array(
				'algo'                     => '1',
				'palette'                  => 'modern_blue',
				'custom'                   => '',
				'seo_template'             => '%title% - %sitename%',
				'webp_quality'             => '85',
				'auto_generate_on_save'    => '1',
				'manual_button_enabled'    => '1',
				'manual_overwrite_existing' => '1',
			);
		}

		/**
		 * Gets plugin settings with defaults merged.
		 *
		 * @return array<string, string>
		 */
		private function get_settings() {
			$settings = get_option( self::OPTION_NAME, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			return wp_parse_args( $settings, $this->get_default_settings() );
		}

		/**
		 * Gets palette definitions.
		 *
		 * @return array<string, array<int, string>>
		 */
		private function get_palettes() {
			return array(
				'modern_blue' => array( '#003f5c', '#2f4b7c', '#665191', '#a05195', '#d45087' ),
				'sunset'      => array( '#ff9900', '#ff5500', '#ff0055', '#9900bb', '#330066' ),
				'nordic'      => array( '#2e3440', '#3b4252', '#434c5e', '#4c566a', '#d8dee9' ),
				'cyber'       => array( '#00ff41', '#008f11', '#003b00', '#000000', '#001100' ),
			);
		}

		/**
		 * Adds settings link on Plugins screen.
		 *
		 * @param string[] $links Existing links.
		 * @return string[]
		 */
		public function add_action_links( $links ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'genart-featured-images' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 * Adds options page.
		 *
		 * @return void
		 */
		public function add_menu() {
			add_menu_page(
				__( 'GenArt Featured Images', 'genart-featured-images' ),
				__( 'GenArt Featured Images', 'genart-featured-images' ),
				'manage_options',
				self::PAGE_SLUG,
				array( $this, 'render_page' ),
				'dashicons-format-image',
				58
			);
		}

		/**
		 * Registers settings.
		 *
		 * @return void
		 */
		public function init_settings() {
			register_setting(
				'genart_featured_images_group',
				self::OPTION_NAME,
				array(
					'sanitize_callback' => array( $this, 'sanitize_settings' ),
					'default'           => $this->get_default_settings(),
				)
			);
		}

		/**
		 * Sanitizes settings.
		 *
		 * @param mixed $input Raw option value.
		 * @return array<string, string>
		 */
		public function sanitize_settings( $input ) {
			$defaults = $this->get_default_settings();
			$output   = $defaults;
			$palettes = $this->get_palettes();

			if ( ! is_array( $input ) ) {
				add_settings_error( self::OPTION_NAME, 'invalid_settings', __( 'Invalid settings payload received.', 'genart-featured-images' ), 'error' );
				return $defaults;
			}

			if ( isset( $input['algo'] ) && in_array( (string) $input['algo'], array( '1', '2', '3' ), true ) ) {
				$output['algo'] = (string) $input['algo'];
			}

			if ( isset( $input['palette'] ) ) {
				$palette = sanitize_key( (string) $input['palette'] );
				if ( isset( $palettes[ $palette ] ) ) {
					$output['palette'] = $palette;
				}
			}

			if ( isset( $input['custom'] ) ) {
				$custom_hex       = $this->sanitize_custom_hex_list( (string) $input['custom'] );
				$output['custom'] = implode( ', ', $custom_hex );
			}

			if ( isset( $input['seo_template'] ) ) {
				$template = sanitize_text_field( (string) $input['seo_template'] );
				$template = trim( $template );
				if ( '' === $template ) {
					$template = $defaults['seo_template'];
				}
				$output['seo_template'] = $template;
			}

			if ( isset( $input['webp_quality'] ) ) {
				$quality = absint( $input['webp_quality'] );
				if ( $quality < 10 ) {
					$quality = 10;
				}
				if ( $quality > 100 ) {
					$quality = 100;
				}
				$output['webp_quality'] = (string) $quality;
			}

			$output['auto_generate_on_save']     = ! empty( $input['auto_generate_on_save'] ) ? '1' : '0';
			$output['manual_button_enabled']     = ! empty( $input['manual_button_enabled'] ) ? '1' : '0';
			$output['manual_overwrite_existing'] = ! empty( $input['manual_overwrite_existing'] ) ? '1' : '0';

			return $output;
		}

		/**
		 * Enqueues admin assets for settings page.
		 *
		 * @param string $hook_suffix Admin page hook.
		 * @return void
		 */
		public function enqueue_assets( $hook_suffix ) {
			if ( 'toplevel_page_' . self::PAGE_SLUG === $hook_suffix ) {
				wp_enqueue_style(
					'genart-featured-images-admin',
					plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
					array(),
					'0.1.1'
				);

				wp_enqueue_script(
					'genart-featured-images-admin',
					plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
					array( 'jquery' ),
					'0.1.1',
					true
				);

				wp_localize_script(
					'genart-featured-images-admin',
					'GenArtFeaturedImages',
					array(
						'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
						'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
						'dryRunAction' => 'genart_featured_images_dry_run',
						'bulkAction'   => 'genart_featured_images_bulk_process',
						'i18n'         => array(
							'runningDryRun' => __( 'Analyzing resources and pending posts...', 'genart-featured-images' ),
							'processing'    => __( 'Generating featured images...', 'genart-featured-images' ),
							'completed'     => __( 'Bulk generation completed successfully.', 'genart-featured-images' ),
							'requestFailed' => __( 'Request failed. Please refresh and try again.', 'genart-featured-images' ),
						),
					)
				);
			}

			if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen || 'post' !== $screen->post_type ) {
				return;
			}

			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
			wp_enqueue_script(
				'genart-featured-images-editor',
				plugin_dir_url( __FILE__ ) . 'assets/js/editor.js',
				array( 'jquery', 'wp-data' ),
				'0.1.1',
				true
			);

			wp_localize_script(
				'genart-featured-images-editor',
				'GenArtFeaturedImagesEditor',
				array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
					'postId'         => $post_id,
					'action'         => 'genart_featured_images_generate_single',
					'manualOverwrite' => '1' === $this->get_settings()['manual_overwrite_existing'],
					'i18n'           => array(
						'processing'  => __( 'Generating featured image...', 'genart-featured-images' ),
						'success'     => __( 'Featured image generated.', 'genart-featured-images' ),
						'error'       => __( 'Image generation failed.', 'genart-featured-images' ),
						'saveFirst'   => __( 'Please save the post once before generating a featured image.', 'genart-featured-images' ),
						'buttonLabel' => __( 'Generate Featured Image Now', 'genart-featured-images' ),
					),
				)
			);
		}

		/**
		 * Registers post editor metabox.
		 *
		 * @return void
		 */
		public function register_post_metabox() {
			$settings = $this->get_settings();
			if ( '1' !== $settings['manual_button_enabled'] ) {
				return;
			}
			if ( ! post_type_supports( 'post', 'thumbnail' ) ) {
				return;
			}

			add_meta_box(
				'genart-featured-image-generator',
				__( 'Generate Featured Image', 'genart-featured-images' ),
				array( $this, 'render_post_metabox' ),
				'post',
				'side',
				'high'
			);
		}

		/**
		 * Renders post editor metabox.
		 *
		 * @param WP_Post $post Current post object.
		 * @return void
		 */
		public function render_post_metabox( $post ) {
			$settings = $this->get_settings();
			?>
			<div class="genart-editor-box">
				<p><?php esc_html_e( 'Create a new generated featured image for this post.', 'genart-featured-images' ); ?></p>
				<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
					<p class="description">
						<?php if ( '1' === $settings['manual_overwrite_existing'] ) : ?>
							<?php esc_html_e( 'Current default: clicking the button replaces the existing featured image.', 'genart-featured-images' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Current default: existing featured image is kept when clicking the button.', 'genart-featured-images' ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<button type="button" class="button button-primary button-large genart-generate-featured-image" id="genart-generate-featured-image" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate Featured Image Now', 'genart-featured-images' ); ?>
				</button>
				<p class="description" id="genart-editor-generate-status" style="margin-top:8px;"></p>
			</div>
			<?php
		}

		/**
		 * Renders settings page.
		 *
		 * @return void
		 */
		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$options  = $this->get_settings();
			$palettes = $this->get_palettes();
			?>
			<div class="wrap genart-admin-wrap">
				<h1><?php esc_html_e( 'GenArt Featured Images', 'genart-featured-images' ); ?></h1>
				<?php settings_errors( self::OPTION_NAME ); ?>
				<?php if ( ! $this->can_generate_images() ) : ?>
					<div class="notice notice-error"><p><?php esc_html_e( 'Image generation is unavailable. Please enable the GD extension with WebP support in your PHP environment.', 'genart-featured-images' ); ?></p></div>
				<?php endif; ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'genart_featured_images_group' ); ?>
					<div class="genart-admin-grid">
						<div class="card genart-card">
							<h2><?php esc_html_e( '1) Default Image Design', 'genart-featured-images' ); ?></h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="genart-algo"><?php esc_html_e( 'Algorithm', 'genart-featured-images' ); ?></label></th>
									<td>
										<select id="genart-algo" name="<?php echo esc_attr( self::OPTION_NAME . '[algo]' ); ?>">
											<option value="1" <?php selected( $options['algo'], '1' ); ?>><?php esc_html_e( 'Mesh gradient', 'genart-featured-images' ); ?></option>
											<option value="2" <?php selected( $options['algo'], '2' ); ?>><?php esc_html_e( 'Bauhaus shapes', 'genart-featured-images' ); ?></option>
											<option value="3" <?php selected( $options['algo'], '3' ); ?>><?php esc_html_e( 'Digital stream', 'genart-featured-images' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'Defines the visual style of the generated featured image.', 'genart-featured-images' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="genart-palette"><?php esc_html_e( 'Palette', 'genart-featured-images' ); ?></label></th>
									<td>
										<select id="genart-palette" name="<?php echo esc_attr( self::OPTION_NAME . '[palette]' ); ?>">
											<?php foreach ( $palettes as $palette_key => $colors ) : ?>
												<option value="<?php echo esc_attr( $palette_key ); ?>" <?php selected( $options['palette'], $palette_key ); ?>>
													<?php echo esc_html( ucwords( str_replace( '_', ' ', $palette_key ) ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Choose a predefined color palette for generated images.', 'genart-featured-images' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="genart-custom"><?php esc_html_e( 'Custom hex colors', 'genart-featured-images' ); ?></label></th>
									<td>
										<input id="genart-custom" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[custom]' ); ?>" value="<?php echo esc_attr( $options['custom'] ); ?>" placeholder="#123456, #abcdef">
										<p class="description"><?php esc_html_e( 'Optional comma-separated list of HEX colors. If provided, it overrides the selected palette.', 'genart-featured-images' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="genart-webp-quality"><?php esc_html_e( 'WebP quality', 'genart-featured-images' ); ?></label></th>
									<td>
										<input id="genart-webp-quality" type="number" min="10" max="100" step="1" name="<?php echo esc_attr( self::OPTION_NAME . '[webp_quality]' ); ?>" value="<?php echo esc_attr( $options['webp_quality'] ); ?>" class="small-text">
										<p class="description"><?php esc_html_e( 'Compression quality from 10 (smallest) to 100 (best quality).', 'genart-featured-images' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
						<div class="card genart-card">
							<h2><?php esc_html_e( '2) SEO Metadata Defaults', 'genart-featured-images' ); ?></h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="genart-seo-template"><?php esc_html_e( 'ALT and title template', 'genart-featured-images' ); ?></label></th>
									<td>
										<input id="genart-seo-template" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[seo_template]' ); ?>" value="<?php echo esc_attr( $options['seo_template'] ); ?>">
										<p class="description"><?php esc_html_e( 'Available placeholders: %title% (post title), %sitename% (site title).', 'genart-featured-images' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
						<div class="card genart-card">
							<h2><?php esc_html_e( '3) Editor and Save Behavior', 'genart-featured-images' ); ?></h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><?php esc_html_e( 'Auto-generate on post save', 'genart-featured-images' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[auto_generate_on_save]' ); ?>" value="1" <?php checked( $options['auto_generate_on_save'], '1' ); ?>>
											<?php esc_html_e( 'If a post has no featured image on save, create one automatically.', 'genart-featured-images' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Manual generate button in post editor', 'genart-featured-images' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[manual_button_enabled]' ); ?>" value="1" <?php checked( $options['manual_button_enabled'], '1' ); ?>>
											<?php esc_html_e( 'Show a “Generate Featured Image Now” button in the post sidebar.', 'genart-featured-images' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Manual button overwrite behavior', 'genart-featured-images' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[manual_overwrite_existing]' ); ?>" value="1" <?php checked( $options['manual_overwrite_existing'], '1' ); ?>>
											<?php esc_html_e( 'When clicked, replace an existing featured image with a newly generated one.', 'genart-featured-images' ); ?>
										</label>
									</td>
								</tr>
							</table>
						</div>
						<div class="card genart-card">
							<h2><?php esc_html_e( '4) Bulk Generation', 'genart-featured-images' ); ?></h2>
							<p><?php esc_html_e( 'Generate featured images for existing posts without thumbnails.', 'genart-featured-images' ); ?></p>
							<p class="description"><?php esc_html_e( 'Dry run checks how many posts are pending and which batch profile (Safe/Balanced/Performance) will be used. It does not create images yet.', 'genart-featured-images' ); ?></p>
							<button id="genart-dry-run" type="button" class="button button-secondary"><?php esc_html_e( 'Run Dry Run', 'genart-featured-images' ); ?></button>
							<div id="dry-run-results" style="margin-top:12px;"></div>
							<button id="genart-start-bulk" type="button" class="button button-primary" style="margin-top:12px;display:none;"><?php esc_html_e( 'Start Bulk Generation', 'genart-featured-images' ); ?></button>
							<div id="bulk-status" style="margin-top:12px;"></div>
						</div>
					</div>
					<?php submit_button( __( 'Save Settings', 'genart-featured-images' ) ); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Handles dry-run request.
		 *
		 * @return void
		 */
		public function handle_dry_run() {
			check_ajax_referer( self::NONCE_ACTION );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Insufficient permissions.', 'genart-featured-images' ),
					),
					403
				);
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error(
					array(
						'message' => __( 'GD with WebP support is required to generate images.', 'genart-featured-images' ),
					),
					500
				);
			}

			$batch_size = $this->get_optimal_batch_size();
			$pending    = $this->count_posts_missing_thumbnail();

			$html = sprintf(
				'<div class="notice notice-info inline"><p><strong>%1$s</strong> %2$s<br><strong>%3$s</strong> %4$d</p></div>',
				esc_html__( 'Batch level:', 'genart-featured-images' ),
				esc_html( $this->get_level_name( $batch_size ) ),
				esc_html__( 'Posts pending image generation:', 'genart-featured-images' ),
				(int) $pending
			);

			wp_send_json_success(
				array(
					'html' => $html,
				)
			);
		}

		/**
		 * Handles bulk generation request.
		 *
		 * @return void
		 */
		public function handle_bulk_ajax() {
			check_ajax_referer( self::NONCE_ACTION );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Insufficient permissions.', 'genart-featured-images' ),
					),
					403
				);
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error(
					array(
						'message' => __( 'GD with WebP support is required to generate images.', 'genart-featured-images' ),
					),
					500
				);
			}

			$batch_size = $this->get_optimal_batch_size();
			$post_ids   = $this->get_posts_missing_thumbnail( $batch_size );
			$errors     = array();

			foreach ( $post_ids as $post_id ) {
				$result = $this->run_generation_safely( (int) $post_id, false );
				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				}
			}

			$remaining = $this->count_posts_missing_thumbnail();
			$message   = sprintf(
				/* translators: %d: remaining post count. */
				__( '%d posts remaining.', 'genart-featured-images' ),
				(int) $remaining
			);

			$response = array(
				'remaining' => (int) $remaining,
				'message'   => $message,
			);

			if ( ! empty( $errors ) ) {
				$response['errors'] = array_slice( array_unique( $errors ), 0, 3 );
			}

			wp_send_json_success( $response );
		}

		/**
		 * Generates featured image for one post from editor button.
		 *
		 * @return void
		 */
		public function handle_generate_single_ajax() {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
			if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid post.', 'genart-featured-images' ),
					),
					400
				);
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Insufficient permissions.', 'genart-featured-images' ),
					),
					403
				);
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error(
					array(
						'message' => __( 'GD with WebP support is required to generate images.', 'genart-featured-images' ),
					),
					500
				);
			}

			$settings       = $this->get_settings();
			$force_overwrite = ( '1' === $settings['manual_overwrite_existing'] );
			$result         = $this->run_generation_safely( $post_id, $force_overwrite );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error(
					array(
						'message' => $result->get_error_message(),
					),
					500
				);
			}

			$attachment_id  = absint( $result );
			$thumbnail_html = '';
			if ( ! function_exists( '_wp_post_thumbnail_html' ) ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
			}
			if ( function_exists( '_wp_post_thumbnail_html' ) ) {
				$thumbnail_html = _wp_post_thumbnail_html( $attachment_id, $post_id );
			}

			wp_send_json_success(
				array(
					'message'       => __( 'Featured image generated.', 'genart-featured-images' ),
					'attachmentId'  => $attachment_id,
					'thumbnailHtml' => $thumbnail_html,
				)
			);
		}

		/**
		 * Generates image when a post is saved.
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 * @return void
		 */
		public function on_save_post( $post_id, $post ) {
			if ( ! $post instanceof WP_Post ) {
				return;
			}

			$settings = $this->get_settings();
			if ( '1' !== $settings['auto_generate_on_save'] ) {
				return;
			}

			if ( ! $this->can_generate_images() ) {
				return;
			}

			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			if ( 'post' !== $post->post_type || has_post_thumbnail( $post_id ) ) {
				return;
			}

			if ( 'auto-draft' === $post->post_status ) {
				return;
			}

			if ( ! post_type_supports( 'post', 'thumbnail' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$result = $this->run_generation_safely( $post_id, false );
			if ( is_wp_error( $result ) ) {
				// Never interrupt post saving when image generation fails.
				do_action( 'genart_featured_images_generation_error', $result, $post_id );
			}
		}

		/**
		 * Runs image generation with temporary PHP warning-to-error conversion.
		 *
		 * @param int  $post_id Post ID.
		 * @param bool $force   Force generation.
		 * @return int|WP_Error
		 */
		private function run_generation_safely( $post_id, $force = false ) {
			set_error_handler(
				static function ( $severity, $message, $file, $line ) {
					throw new ErrorException( $message, 0, $severity, $file, $line );
				}
			);

			try {
				$result = $this->generate_for_post( $post_id, $force );
			} catch ( Throwable $throwable ) {
				$result = new WP_Error(
					'genart_runtime_error',
					__( 'Image generation failed due to a runtime error on the server.', 'genart-featured-images' )
				);
			}

			restore_error_handler();
			return $result;
		}

		/**
		 * Returns true if current environment can generate WebP files.
		 *
		 * @return bool
		 */
		private function can_generate_images() {
			if ( ! extension_loaded( 'gd' ) || ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagewebp' ) ) {
				return false;
			}

			if ( ! function_exists( 'imagetypes' ) || ! defined( 'IMG_WEBP' ) ) {
				return false;
			}

			return 0 !== ( imagetypes() & IMG_WEBP );
		}

		/**
		 * Gets batch size based on available memory.
		 *
		 * @return int
		 */
		private function get_optimal_batch_size() {
			$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
			$memory_mb    = $memory_limit > 0 ? ( $memory_limit / 1024 / 1024 ) : 256;

			if ( $memory_mb < 128 ) {
				return 2;
			}

			if ( $memory_mb < 256 ) {
				return 5;
			}

			return 10;
		}

		/**
		 * Gets human-readable batch profile name.
		 *
		 * @param int $size Batch size.
		 * @return string
		 */
		private function get_level_name( $size ) {
			if ( $size <= 2 ) {
				return __( 'Safe', 'genart-featured-images' );
			}

			if ( $size <= 5 ) {
				return __( 'Balanced', 'genart-featured-images' );
			}

			return __( 'Performance', 'genart-featured-images' );
		}

		/**
		 * Gets post IDs without a thumbnail.
		 *
		 * @param int $limit Query limit.
		 * @return int[]
		 */
		private function get_posts_missing_thumbnail( $limit ) {
			$query = new WP_Query(
				array(
					'post_type'              => 'post',
					'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
					'posts_per_page'         => $limit,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => array(
						array(
							'key'     => '_thumbnail_id',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

			if ( empty( $query->posts ) || ! is_array( $query->posts ) ) {
				return array();
			}

			return array_map( 'intval', $query->posts );
		}

		/**
		 * Counts posts without thumbnails.
		 *
		 * @return int
		 */
		private function count_posts_missing_thumbnail() {
			$query = new WP_Query(
				array(
					'post_type'              => 'post',
					'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => array(
						array(
							'key'     => '_thumbnail_id',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

			return isset( $query->found_posts ) ? (int) $query->found_posts : 0;
		}

		/**
		 * Generates featured image for a given post.
		 *
		 * @param int  $post_id Post ID.
		 * @param bool $force   Force creation even if thumbnail exists.
		 * @return int|WP_Error Attachment ID on success.
		 */
		private function generate_for_post( $post_id, $force = false ) {
			if ( has_post_thumbnail( $post_id ) && ! $force ) {
				return (int) get_post_thumbnail_id( $post_id );
			}

			$image = @imagecreatetruecolor( 1200, 630 );

			if ( false === $image ) {
				return new WP_Error( 'genart_image_create_failed', __( 'Unable to initialize image canvas.', 'genart-featured-images' ) );
			}

			$palette = $this->get_image_palette_colors( $image, 85 );

			if ( empty( $palette ) ) {
				imagedestroy( $image );
				return new WP_Error( 'genart_palette_failed', __( 'No valid colors available for rendering.', 'genart-featured-images' ) );
			}

			$background_palette = $this->get_image_palette_colors( $image, 0 );
			imagefill( $image, 0, 0, $background_palette[0] );

			$settings  = $this->get_settings();
			$algorithm = $settings['algo'];

			if ( '1' === $algorithm ) {
				for ( $i = 0; $i < 12; $i++ ) {
					imagefilledellipse(
						$image,
						wp_rand( 0, 1200 ),
						wp_rand( 0, 630 ),
						wp_rand( 400, 900 ),
						wp_rand( 400, 900 ),
						$palette[ array_rand( $palette ) ]
					);
				}
			} elseif ( '2' === $algorithm ) {
				for ( $i = 0; $i < 15; $i++ ) {
					imagefilledrectangle(
						$image,
						wp_rand( 0, 800 ),
						wp_rand( 0, 400 ),
						wp_rand( 400, 1200 ),
						wp_rand( 300, 630 ),
						$palette[ array_rand( $palette ) ]
					);
				}
			} else {
				for ( $i = 0; $i < 60; $i++ ) {
					imageline(
						$image,
						wp_rand( 0, 1200 ),
						0,
						wp_rand( 0, 1200 ),
						630,
						$palette[ array_rand( $palette ) ]
					);
				}
			}

			$result = $this->attach_generated_image( $image, $post_id );
			imagedestroy( $image );

			return $result;
		}

		/**
		 * Creates and attaches generated image to post.
		 *
		 * @param resource $image   GD image resource.
		 * @param int      $post_id Post ID.
		 * @return int|WP_Error
		 */
		private function attach_generated_image( $image, $post_id ) {
			$post_title = get_the_title( $post_id );
			$site_name  = get_bloginfo( 'name' );
			$settings   = $this->get_settings();

			$seo_text = strtr(
				$settings['seo_template'],
				array(
					'%title%'    => $post_title ? $post_title : '',
					'%sitename%' => $site_name ? $site_name : '',
				)
			);

			$seo_text = sanitize_text_field( $seo_text );
			if ( '' === $seo_text ) {
				$seo_text = sanitize_text_field( $post_title );
			}

			$tmp_path = wp_tempnam( 'genart-image-' . $post_id );
			if ( empty( $tmp_path ) ) {
				return new WP_Error( 'genart_temp_file_failed', __( 'Unable to create temporary file for image.', 'genart-featured-images' ) );
			}

			$quality  = absint( $settings['webp_quality'] );
			if ( $quality < 10 ) {
				$quality = 10;
			} elseif ( $quality > 100 ) {
				$quality = 100;
			}
			$rendered = @imagewebp( $image, $tmp_path, $quality );
			if ( false === $rendered ) {
				@unlink( $tmp_path );
				return new WP_Error( 'genart_webp_write_failed', __( 'Unable to write WebP image to temporary file.', 'genart-featured-images' ) );
			}

			$file_array = array(
				'name'     => sanitize_file_name( sanitize_title( $post_title ) . '-' . $post_id . '.webp' ),
				'tmp_name' => $tmp_path,
			);

			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_handle_sideload( $file_array, $post_id, $seo_text );
			if ( is_wp_error( $attachment_id ) ) {
				if ( file_exists( $tmp_path ) ) {
					@unlink( $tmp_path );
				}
				return $attachment_id;
			}

			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $seo_text );
			set_post_thumbnail( $post_id, $attachment_id );

			return (int) $attachment_id;
		}

		/**
		 * Returns allocated colors from palette/custom colors.
		 *
		 * @param resource $image GD image resource.
		 * @param int      $alpha Alpha channel (0-127).
		 * @return int[]
		 */
		private function get_image_palette_colors( $image, $alpha ) {
			$settings = $this->get_settings();
			$palette  = $this->get_palettes();
			$colors   = array();
			$hex_list = array();

			if ( ! empty( $settings['custom'] ) ) {
				$hex_list = $this->sanitize_custom_hex_list( $settings['custom'] );
			}

			if ( empty( $hex_list ) ) {
				$palette_key = isset( $palette[ $settings['palette'] ] ) ? $settings['palette'] : 'modern_blue';
				$hex_list    = $palette[ $palette_key ];
			}

			foreach ( $hex_list as $hex_color ) {
				$hex_color = ltrim( $hex_color, '#' );

				if ( 6 !== strlen( $hex_color ) ) {
					continue;
				}

				$red   = hexdec( substr( $hex_color, 0, 2 ) );
				$green = hexdec( substr( $hex_color, 2, 2 ) );
				$blue  = hexdec( substr( $hex_color, 4, 2 ) );

				$allocated = imagecolorallocatealpha( $image, $red, $green, $blue, $alpha );
				if ( false !== $allocated ) {
					$colors[] = $allocated;
				}
			}

			if ( empty( $colors ) ) {
				$fallback = imagecolorallocatealpha( $image, 0, 0, 0, $alpha );
				if ( false !== $fallback ) {
					$colors[] = $fallback;
				}
			}

			return $colors;
		}

		/**
		 * Sanitizes comma-separated list of HEX colors.
		 *
		 * @param string $raw_list Raw list.
		 * @return string[]
		 */
		private function sanitize_custom_hex_list( $raw_list ) {
			$raw_list = trim( $raw_list );
			if ( '' === $raw_list ) {
				return array();
			}

			$values = explode( ',', $raw_list );
			$valid  = array();

			foreach ( $values as $value ) {
				$value = trim( strtolower( $value ) );
				if ( preg_match( '/^#?[0-9a-f]{6}$/', $value ) ) {
					$valid[] = '#' . ltrim( $value, '#' );
				}
			}

			return array_values( array_unique( $valid ) );
		}
	}
}

new Genart_Featured_Images();
