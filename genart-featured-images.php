<?php
/**
 * Plugin Name:       GenArt Featured Images
 * Description:       Generate abstract WebP featured images for posts and apply SEO-friendly metadata.
 * Version:           0.1.2
 * Author:            drhdev
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       genart-featured-images
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
		 * Help page slug.
		 *
		 * @var string
		 */
		const HELP_PAGE_SLUG = 'genart-featured-images-help';

		/**
		 * Nonce action.
		 *
		 * @var string
		 */
		const NONCE_ACTION = 'genart_featured_images_bulk';

		/**
		 * Post meta key for style.
		 *
		 * @var string
		 */
		const META_STYLE = '_genart_featured_images_style';

		/**
		 * Post meta key for scheme.
		 *
		 * @var string
		 */
		const META_SCHEME = '_genart_featured_images_scheme';

		/**
		 * Attachment meta marker for generated media.
		 *
		 * @var string
		 */
		const META_GENERATED = '_genart_featured_images_generated';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'init_settings' ) );
			add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'register_post_metabox' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );

			add_action( 'wp_ajax_genart_featured_images_bulk_process', array( $this, 'handle_bulk_ajax' ) );
			add_action( 'wp_ajax_genart_featured_images_dry_run', array( $this, 'handle_dry_run' ) );
			add_action( 'wp_ajax_genart_featured_images_generate_single', array( $this, 'handle_generate_single_ajax' ) );
			add_action( 'wp_ajax_genart_featured_images_cleanup_generated', array( $this, 'handle_cleanup_generated_ajax' ) );
		}

		/**
		 * Gets built-in palette definitions.
		 *
		 * @return array<string, array<int, string>>
		 */
		private function get_builtin_palettes() {
			return array(
				'modern_blue' => array( '#003f5c', '#2f4b7c', '#665191', '#a05195', '#d45087' ),
				'sunset'      => array( '#ff9900', '#ff5500', '#ff0055', '#9900bb', '#330066' ),
				'nordic'      => array( '#2e3440', '#3b4252', '#434c5e', '#4c566a', '#d8dee9' ),
				'cyber'       => array( '#00ff41', '#008f11', '#003b00', '#000000', '#001100' ),
			);
		}

		/**
		 * Gets default settings.
		 *
		 * @return array<string, mixed>
		 */
		private function get_default_settings() {
			return array(
				'algo'                      => '1',
				'palette'                   => 'modern_blue',
				'seo_template'              => '%title% - %sitename%',
				'webp_quality'              => '85',
				'randomize_defaults'        => '1',
				'auto_generate_on_save'     => '1',
				'manual_button_enabled'     => '1',
				'manual_overwrite_existing' => '1',
				'custom_schemes'            => array(),
				'rules'                     => array(),
			);
		}

		/**
		 * Gets plugin settings merged with defaults.
		 *
		 * @return array<string, mixed>
		 */
		private function get_settings() {
			$settings = get_option( self::OPTION_NAME, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$settings = wp_parse_args( $settings, $this->get_default_settings() );
			if ( empty( $settings['custom_schemes'] ) || ! is_array( $settings['custom_schemes'] ) ) {
				$settings['custom_schemes'] = array();
			}

			return $settings;
		}

		/**
		 * Gets all available schemes (built-in + custom).
		 *
		 * @return array<string, array{name: string, colors: array<int, string>, source: string}>
		 */
		private function get_all_schemes() {
			$schemes = array();

			foreach ( $this->get_builtin_palettes() as $id => $colors ) {
				$schemes[ $id ] = array(
					'name'   => ucwords( str_replace( '_', ' ', $id ) ),
					'colors' => $colors,
					'source' => 'built-in',
				);
			}

			$settings = $this->get_settings();
			foreach ( $settings['custom_schemes'] as $scheme ) {
				if ( empty( $scheme['id'] ) || empty( $scheme['name'] ) || empty( $scheme['colors'] ) || ! is_array( $scheme['colors'] ) ) {
					continue;
				}
				$schemes[ $scheme['id'] ] = array(
					'name'   => (string) $scheme['name'],
					'colors' => array_values( $scheme['colors'] ),
					'source' => 'custom',
				);
			}

			return $schemes;
		}

		/**
		 * Gets available algorithms.
		 *
		 * @return array<string, string>
		 */
		private function get_algorithms() {
			return array(
				'1' => 'Mesh gradient',
				'2' => 'Bauhaus shapes',
				'3' => 'Digital stream',
			);
		}

		/**
		 * Sanitizes generation rules.
		 *
		 * @param mixed $input Rules input.
		 * @return array<int, array{id:string,taxonomy:string,term_id:int,algos:array<int,string>,schemes:array<int,string>}>
		 */
		private function sanitize_rules( $input, $allowed_schemes = array() ) {
			if ( ! is_array( $input ) ) {
				return array();
			}

			$allowed_algos   = array_keys( $this->get_algorithms() );
			if ( empty( $allowed_schemes ) ) {
				$allowed_schemes = array_keys( $this->get_all_schemes() );
			}
			$allowed_tax     = array( 'post_tag', 'category' );
			$rules           = array();
			$seen_terms      = array(
				'post_tag' => array(),
				'category' => array(),
			);
			$duplicate_count = 0;

			foreach ( $input as $index => $row ) {
				if ( ! is_array( $row ) || ! empty( $row['remove'] ) ) {
					continue;
				}

				$taxonomy = isset( $row['taxonomy'] ) ? sanitize_key( (string) $row['taxonomy'] ) : '';
				if ( ! in_array( $taxonomy, $allowed_tax, true ) ) {
					continue;
				}

				$term_id = isset( $row['term_id'] ) ? absint( $row['term_id'] ) : 0;
				if ( $term_id <= 0 || in_array( $term_id, $seen_terms[ $taxonomy ], true ) ) {
					if ( $term_id > 0 ) {
						$duplicate_count++;
					}
					continue;
				}

				$algos = array();
				if ( isset( $row['algos'] ) && is_array( $row['algos'] ) ) {
					foreach ( $row['algos'] as $algo ) {
						$algo = sanitize_text_field( (string) $algo );
						if ( in_array( $algo, $allowed_algos, true ) ) {
							$algos[] = $algo;
						}
					}
				}
				$algos = array_values( array_unique( $algos ) );

				$schemes = array();
				if ( isset( $row['schemes'] ) && is_array( $row['schemes'] ) ) {
					foreach ( $row['schemes'] as $scheme ) {
						$scheme = sanitize_key( (string) $scheme );
						if ( in_array( $scheme, $allowed_schemes, true ) ) {
							$schemes[] = $scheme;
						}
					}
				}
				$schemes = array_values( array_unique( $schemes ) );

				$rules[] = array(
					'id'       => 'rule-' . sanitize_key( (string) $index ) . '-' . $term_id,
					'taxonomy' => $taxonomy,
					'term_id'  => $term_id,
					'algos'    => $algos,
					'schemes'  => $schemes,
				);
				$seen_terms[ $taxonomy ][] = $term_id;
			}

			if ( $duplicate_count > 0 ) {
				add_settings_error(
					self::OPTION_NAME,
					'rule_duplicates_removed',
					'Some duplicate rules were removed automatically. Each category or tag can only be used once.',
					'warning'
				);
			}

			return $rules;
		}

		/**
		 * Adds settings link in plugins list.
		 *
		 * @param string[] $links Existing links.
		 * @return string[]
		 */
		public function add_action_links( $links ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 * Adds admin menu page.
		 *
		 * @return void
		 */
		public function add_menu() {
			add_menu_page(
				'GenArt Featured Images',
				'GenArt Featured Images',
				'manage_options',
				self::PAGE_SLUG,
				array( $this, 'render_page' ),
				'dashicons-format-image',
				58
			);

			add_submenu_page(
				self::PAGE_SLUG,
				'Settings',
				'Settings',
				'manage_options',
				self::PAGE_SLUG,
				array( $this, 'render_page' )
			);

			add_submenu_page(
				self::PAGE_SLUG,
				'Help',
				'Help',
				'manage_options',
				self::HELP_PAGE_SLUG,
				array( $this, 'render_help_page' )
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
		 * Sanitizes custom schemes.
		 *
		 * @param mixed $input Raw custom schemes input.
		 * @return array<int, array{id:string,name:string,colors:array<int,string>}>
		 */
		private function sanitize_custom_schemes( $input ) {
			if ( ! is_array( $input ) ) {
				return array();
			}

			$sanitized = array();
			foreach ( $input as $row ) {
				if ( ! is_array( $row ) || ! empty( $row['remove'] ) ) {
					continue;
				}

				$name = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
				$name = trim( $name );
				if ( '' === $name ) {
					continue;
				}

				$colors = isset( $row['colors'] ) ? $this->sanitize_hex_list( (string) $row['colors'] ) : array();
				if ( count( $colors ) < 2 ) {
					continue;
				}

				$id = isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '';
				if ( '' === $id ) {
					$id = sanitize_title( $name );
				}
				if ( '' === $id ) {
					continue;
				}

				$sanitized[] = array(
					'id'     => $id,
					'name'   => $name,
					'colors' => $colors,
				);
			}

			// Ensure unique IDs.
			$unique = array();
			foreach ( $sanitized as $scheme ) {
				$base_id = $scheme['id'];
				$id      = $base_id;
				$index   = 2;
				while ( isset( $unique[ $id ] ) ) {
					$id = $base_id . '-' . $index;
					$index++;
				}
				$scheme['id'] = $id;
				$unique[ $id ] = $scheme;
			}

			return array_values( $unique );
		}

		/**
		 * Sanitizes settings.
		 *
		 * @param mixed $input Raw option value.
		 * @return array<string, mixed>
		 */
		public function sanitize_settings( $input ) {
			$defaults = $this->get_default_settings();
			$output   = $defaults;

			if ( ! is_array( $input ) ) {
				add_settings_error( self::OPTION_NAME, 'invalid_settings', 'Invalid settings payload received.', 'error' );
				return $defaults;
			}

			if ( isset( $input['algo'] ) && in_array( (string) $input['algo'], array( '1', '2', '3' ), true ) ) {
				$output['algo'] = (string) $input['algo'];
			}

			if ( isset( $input['seo_template'] ) ) {
				$template = sanitize_text_field( (string) $input['seo_template'] );
				$template = trim( $template );
				$output['seo_template'] = '' !== $template ? $template : (string) $defaults['seo_template'];
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
			$output['randomize_defaults']        = ! empty( $input['randomize_defaults'] ) ? '1' : '0';
			$output['manual_button_enabled']     = ! empty( $input['manual_button_enabled'] ) ? '1' : '0';
			$output['manual_overwrite_existing'] = ! empty( $input['manual_overwrite_existing'] ) ? '1' : '0';
			$output['custom_schemes']            = $this->sanitize_custom_schemes( $input['custom_schemes'] ?? array() );

			$allowed_schemes = array_keys( $this->get_builtin_palettes() );
			foreach ( $output['custom_schemes'] as $custom_scheme ) {
				if ( ! empty( $custom_scheme['id'] ) ) {
					$allowed_schemes[] = (string) $custom_scheme['id'];
				}
			}

			if ( isset( $input['palette'] ) ) {
				$palette = sanitize_key( (string) $input['palette'] );
				if ( in_array( $palette, $allowed_schemes, true ) ) {
					$output['palette'] = $palette;
				}
			}

			if ( ! in_array( (string) $output['palette'], $allowed_schemes, true ) ) {
				$output['palette'] = (string) $defaults['palette'];
			}

			$output['rules'] = $this->sanitize_rules( $input['rules'] ?? array(), $allowed_schemes );

			return $output;
		}

		/**
		 * Enqueues assets.
		 *
		 * @param string $hook_suffix Admin hook.
		 * @return void
		 */
		public function enqueue_assets( $hook_suffix ) {
			$is_settings_page = in_array(
				$hook_suffix,
				array(
					'toplevel_page_' . self::PAGE_SLUG,
					self::PAGE_SLUG . '_page_' . self::PAGE_SLUG,
				),
				true
			);
			$is_help_page = ( self::PAGE_SLUG . '_page_' . self::HELP_PAGE_SLUG ) === $hook_suffix;

			if ( $is_settings_page || $is_help_page ) {
				$category_terms = get_terms(
					array(
						'taxonomy'   => 'category',
						'hide_empty' => false,
						'fields'     => 'id=>name',
					)
				);
				$tag_terms = get_terms(
					array(
						'taxonomy'   => 'post_tag',
						'hide_empty' => false,
						'fields'     => 'id=>name',
					)
				);
				if ( is_wp_error( $category_terms ) || ! is_array( $category_terms ) ) {
					$category_terms = array();
				}
				if ( is_wp_error( $tag_terms ) || ! is_array( $tag_terms ) ) {
					$tag_terms = array();
				}

				wp_enqueue_style(
					'genart-featured-images-admin',
					plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
					array(),
					'0.1.2'
				);
			}

			if ( $is_settings_page ) {
				wp_enqueue_script(
					'genart-featured-images-admin',
					plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
					array( 'jquery' ),
					'0.1.2',
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
							'runningDryRun' => 'Analyzing resources and pending posts...',
							'processing'    => 'Generating featured images...',
							'completed'     => 'Bulk generation completed successfully.',
							'requestFailed' => 'Request failed. Please refresh and try again.',
							'cleanupRunning'=> 'Cleaning up unused generated images...',
							'cleanupDone'   => 'Cleanup finished.',
							'cleanupConfirm'=> "Cleanup is permanent and cannot be undone.\n\nOnly plugin-generated and currently unused featured images will be deleted.\n\nProceed with cleanup?",
						),
						'cleanupAction'=> 'genart_featured_images_cleanup_generated',
						'optionName'   => self::OPTION_NAME,
						'algorithms'   => $this->get_algorithms(),
						'schemes'      => $this->get_all_schemes(),
						'terms'        => array(
							'category' => $category_terms,
							'post_tag' => $tag_terms,
						),
					)
				);
			}

			if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
				return;
			}

			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
			wp_enqueue_script(
				'genart-featured-images-editor',
				plugin_dir_url( __FILE__ ) . 'assets/js/editor.js',
				array( 'jquery' ),
				'0.1.2',
				true
			);

			wp_localize_script(
				'genart-featured-images-editor',
				'GenArtFeaturedImagesEditor',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
					'postId'  => $post_id,
					'action'  => 'genart_featured_images_generate_single',
					'i18n'    => array(
						'processing'  => 'Generating featured image...',
						'success'     => 'Featured image generated.',
						'error'       => 'Image generation failed.',
						'saveFirst'   => 'Please save the post once before generating a featured image.',
						'buttonLabel' => 'Generate Featured Image Now',
					),
				)
			);
		}

		/**
		 * Registers editor metabox.
		 *
		 * @return void
		 */
		public function register_post_metabox() {
			$settings = $this->get_settings();
			if ( '1' !== $settings['manual_button_enabled'] ) {
				return;
			}

			foreach ( array( 'post', 'page' ) as $post_type ) {
				if ( ! post_type_supports( $post_type, 'thumbnail' ) ) {
					continue;
				}
				add_meta_box(
					'genart-featured-image-generator',
					'Generate Featured Image',
					array( $this, 'render_post_metabox' ),
					$post_type,
					'side',
					'high'
				);
			}
		}

		/**
		 * Gets selected style and scheme for a post.
		 *
		 * @param int $post_id Post ID.
		 * @return array{algo:string,palette:string}
		 */
		private function get_post_generation_preferences( $post_id ) {
			$schemes = $this->get_all_schemes();

			$algo = get_post_meta( $post_id, self::META_STYLE, true );
			if ( ! in_array( (string) $algo, array( '1', '2', '3' ), true ) ) {
				$algo = '';
			}

			$palette = get_post_meta( $post_id, self::META_SCHEME, true );
			if ( ! is_string( $palette ) || ! isset( $schemes[ $palette ] ) ) {
				$palette = '';
			}

			return array(
				'algo'    => (string) $algo,
				'palette' => (string) $palette,
			);
		}

		/**
		 * Renders post editor metabox.
		 *
		 * @param WP_Post $post Current post.
		 * @return void
		 */
		public function render_post_metabox( $post ) {
			$settings = $this->get_settings();
			$schemes  = $this->get_all_schemes();
			$prefs    = $this->get_post_generation_preferences( $post->ID );

			wp_nonce_field( 'genart_post_settings', 'genart_post_settings_nonce' );
			?>
			<div class="genart-editor-box">
				<p>Create a new generated featured image for this post.</p>

				<p><strong>Art style</strong></p>
				<select name="genart_post_style" id="genart-post-style" class="genart-scroll-select" size="3">
					<option value="1" <?php selected( $prefs['algo'], '1' ); ?>>Mesh gradient</option>
					<option value="2" <?php selected( $prefs['algo'], '2' ); ?>>Bauhaus shapes</option>
					<option value="3" <?php selected( $prefs['algo'], '3' ); ?>>Digital stream</option>
				</select>

				<p style="margin-top:10px;"><strong>Color scheme</strong></p>
				<select name="genart_post_scheme" id="genart-post-scheme" class="genart-scroll-select" size="6">
					<?php foreach ( $schemes as $scheme_id => $scheme ) : ?>
						<option value="<?php echo esc_attr( $scheme_id ); ?>" <?php selected( $prefs['palette'], $scheme_id ); ?>>
							<?php echo esc_html( $scheme['name'] . ( 'custom' === $scheme['source'] ? ' (custom)' : '' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
					<p class="description">
						<?php if ( '1' === $settings['manual_overwrite_existing'] ) : ?>
							Current default: clicking the button replaces the existing featured image.
						<?php else : ?>
							Current default: existing featured image is kept when clicking the button.
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<button type="button" class="button button-primary button-large genart-generate-featured-image" id="genart-generate-featured-image" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
					Generate Featured Image Now
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

			$options = $this->get_settings();
			$schemes = $this->get_all_schemes();
			$rules   = ( ! empty( $options['rules'] ) && is_array( $options['rules'] ) ) ? $options['rules'] : array();
			$algos   = $this->get_algorithms();
			$category_terms = get_terms(
				array(
					'taxonomy'   => 'category',
					'hide_empty' => false,
					'fields'     => 'id=>name',
				)
			);
			$tag_terms = get_terms(
				array(
					'taxonomy'   => 'post_tag',
					'hide_empty' => false,
					'fields'     => 'id=>name',
				)
			);
			if ( is_wp_error( $category_terms ) || ! is_array( $category_terms ) ) {
				$category_terms = array();
			}
			if ( is_wp_error( $tag_terms ) || ! is_array( $tag_terms ) ) {
				$tag_terms = array();
			}
			?>
			<div class="wrap genart-admin-wrap">
				<h1>GenArt Featured Images</h1>
				<p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::HELP_PAGE_SLUG ) ); ?>">Open detailed help</a></p>
				<?php settings_errors( self::OPTION_NAME ); ?>
				<?php if ( ! $this->can_generate_images() ) : ?>
					<div class="notice notice-error"><p>Image generation is unavailable. Please enable the GD extension with WebP support in your PHP environment.</p></div>
				<?php endif; ?>

				<form method="post" action="options.php">
					<?php settings_fields( 'genart_featured_images_group' ); ?>
					<div class="genart-admin-grid">
						<div class="card genart-card">
							<h2>1) Default image design</h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="genart-algo">Default art style</label></th>
									<td>
										<select id="genart-algo" name="<?php echo esc_attr( self::OPTION_NAME . '[algo]' ); ?>">
											<option value="1" <?php selected( $options['algo'], '1' ); ?>>Mesh gradient</option>
											<option value="2" <?php selected( $options['algo'], '2' ); ?>>Bauhaus shapes</option>
											<option value="3" <?php selected( $options['algo'], '3' ); ?>>Digital stream</option>
										</select>
										<p class="description">Used when no per-post style has been selected.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="genart-palette">Default color scheme</label></th>
									<td>
										<select id="genart-palette" name="<?php echo esc_attr( self::OPTION_NAME . '[palette]' ); ?>" class="genart-scroll-select" size="6">
											<?php foreach ( $schemes as $scheme_id => $scheme ) : ?>
												<option value="<?php echo esc_attr( $scheme_id ); ?>" <?php selected( $options['palette'], $scheme_id ); ?>>
													<?php echo esc_html( $scheme['name'] . ( 'custom' === $scheme['source'] ? ' (custom)' : '' ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">Used when no per-post color scheme has been selected.</p>
									</td>
								</tr>
								<tr>
									<th scope="row">Default selection mode</th>
									<td>
										<label>
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[randomize_defaults]' ); ?>" value="1" <?php checked( $options['randomize_defaults'], '1' ); ?>>
											Randomize algorithm and color scheme for new posts/pages when no rule applies.
										</label>
										<p class="description">Recommended default: enabled. When disabled, the fixed defaults above are used.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="genart-webp-quality">WebP quality</label></th>
									<td>
										<input id="genart-webp-quality" type="number" min="10" max="100" step="1" class="small-text" name="<?php echo esc_attr( self::OPTION_NAME . '[webp_quality]' ); ?>" value="<?php echo esc_attr( $options['webp_quality'] ); ?>">
										<p class="description">Compression quality from 10 (smallest) to 100 (best quality).</p>
									</td>
								</tr>
							</table>
						</div>

						<div class="card genart-card">
							<h2>2) SEO metadata defaults</h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="genart-seo-template">ALT and title template</label></th>
									<td>
										<input id="genart-seo-template" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[seo_template]' ); ?>" value="<?php echo esc_attr( $options['seo_template'] ); ?>">
										<p class="description">Placeholders: %title% (post title), %sitename% (site title).</p>
									</td>
								</tr>
							</table>
						</div>

						<div class="card genart-card">
							<h2>3) Editor and save behavior</h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row">Auto-generate on post save</th>
									<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[auto_generate_on_save]' ); ?>" value="1" <?php checked( $options['auto_generate_on_save'], '1' ); ?>> If a post has no featured image on save, create one automatically.</label></td>
								</tr>
								<tr>
									<th scope="row">Manual generate button in editor</th>
									<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[manual_button_enabled]' ); ?>" value="1" <?php checked( $options['manual_button_enabled'], '1' ); ?>> Show a "Generate Featured Image Now" button in the post sidebar.</label></td>
								</tr>
								<tr>
									<th scope="row">Manual button overwrite behavior</th>
									<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[manual_overwrite_existing]' ); ?>" value="1" <?php checked( $options['manual_overwrite_existing'], '1' ); ?>> When clicked, replace an existing featured image with a newly generated one.</label></td>
								</tr>
							</table>
						</div>

						<div class="card genart-card">
							<h2>4) Category and tag rules</h2>
							<p class="description">Rules are evaluated in order. Tag rules have higher priority than category rules. Each term can only be used once to prevent conflicting defaults.</p>
							<table class="widefat striped" id="genart-rules-table">
								<thead>
									<tr>
										<th>Rule type</th>
										<th>Term</th>
										<th>Algorithms (one or more)</th>
										<th>Color schemes (one or more)</th>
										<th>Remove</th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $rules ) ) : ?>
										<tr class="genart-no-rules-row"><td colspan="5">No rules configured. Defaults will be used.</td></tr>
									<?php else : ?>
										<?php foreach ( $rules as $index => $rule ) : ?>
											<?php
											$rule_tax   = isset( $rule['taxonomy'] ) ? (string) $rule['taxonomy'] : 'category';
											$rule_terms = 'post_tag' === $rule_tax ? $tag_terms : $category_terms;
											$rule_algos = isset( $rule['algos'] ) && is_array( $rule['algos'] ) ? $rule['algos'] : array();
											$rule_schemes = isset( $rule['schemes'] ) && is_array( $rule['schemes'] ) ? $rule['schemes'] : array();
											?>
											<tr class="genart-rule-row">
												<td>
													<select name="<?php echo esc_attr( self::OPTION_NAME . '[rules][' . $index . '][taxonomy]' ); ?>" class="genart-rule-taxonomy">
														<option value="category" <?php selected( $rule_tax, 'category' ); ?>>Category</option>
														<option value="post_tag" <?php selected( $rule_tax, 'post_tag' ); ?>>Tag</option>
													</select>
												</td>
												<td>
													<select name="<?php echo esc_attr( self::OPTION_NAME . '[rules][' . $index . '][term_id]' ); ?>" class="genart-rule-term">
														<option value="">Select term</option>
														<?php foreach ( $rule_terms as $term_id => $term_name ) : ?>
															<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( absint( $rule['term_id'] ), absint( $term_id ) ); ?>><?php echo esc_html( $term_name ); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td>
													<select multiple class="genart-scroll-select genart-rule-algos" name="<?php echo esc_attr( self::OPTION_NAME . '[rules][' . $index . '][algos][]' ); ?>" size="3">
														<?php foreach ( $algos as $algo_id => $algo_name ) : ?>
															<option value="<?php echo esc_attr( $algo_id ); ?>" <?php selected( in_array( $algo_id, $rule_algos, true ), true ); ?>><?php echo esc_html( $algo_name ); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td>
													<select multiple class="genart-scroll-select genart-rule-schemes" name="<?php echo esc_attr( self::OPTION_NAME . '[rules][' . $index . '][schemes][]' ); ?>" size="6">
														<?php foreach ( $schemes as $scheme_id => $scheme ) : ?>
															<option value="<?php echo esc_attr( $scheme_id ); ?>" <?php selected( in_array( $scheme_id, $rule_schemes, true ), true ); ?>><?php echo esc_html( $scheme['name'] ); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[rules][' . $index . '][remove]' ); ?>" value="1"> Remove</label></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
							<p style="margin-top:10px;"><button type="button" class="button" id="genart-add-rule-row">Add rule</button></p>
						</div>

						<div class="card genart-card">
							<h2>5) Custom color schemes</h2>
							<p class="description">Add named schemes that will appear in both settings and post editor dropdowns. Colors must be comma-separated HEX values (example: #112233, #445566, #778899).</p>
							<table class="widefat striped" id="genart-custom-schemes-table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Colors</th>
										<th>Remove</th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $options['custom_schemes'] ) ) : ?>
										<tr class="genart-no-schemes-row"><td colspan="3">No custom color schemes yet.</td></tr>
									<?php else : ?>
										<?php foreach ( $options['custom_schemes'] as $index => $scheme ) : ?>
											<tr class="genart-custom-scheme-row">
												<td>
													<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME . '[custom_schemes][' . $index . '][id]' ); ?>" value="<?php echo esc_attr( $scheme['id'] ); ?>">
													<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[custom_schemes][' . $index . '][name]' ); ?>" value="<?php echo esc_attr( $scheme['name'] ); ?>">
												</td>
												<td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[custom_schemes][' . $index . '][colors]' ); ?>" value="<?php echo esc_attr( implode( ', ', $scheme['colors'] ) ); ?>"></td>
												<td>
													<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[custom_schemes][' . $index . '][remove]' ); ?>" value="1"> Remove</label>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
							<p style="margin-top:10px;"><button type="button" class="button" id="genart-add-scheme-row">Add custom scheme</button></p>
						</div>

						<div class="card genart-card">
							<h2>6) Bulk generation</h2>
							<p>Generate featured images for existing posts without thumbnails.</p>
							<p class="description">Dry run checks how many posts are pending and which batch profile (Safe/Balanced/Performance) will be used. It does not create images yet.</p>
							<button id="genart-dry-run" type="button" class="button button-secondary">Run Dry Run</button>
							<div id="dry-run-results" style="margin-top:12px;"></div>
							<button id="genart-start-bulk" type="button" class="button button-primary" style="margin-top:12px;display:none;">Start Bulk Generation</button>
							<div id="bulk-status" style="margin-top:12px;"></div>
						</div>

						<div class="card genart-card">
							<h2>7) Cleanup generated media</h2>
							<p>This tool removes media library items created by this plugin that are currently unused as featured image anywhere in WordPress.</p>
							<ul>
								<li>Only plugin-generated items are considered.</li>
								<li>Any item still used as featured image by any post type is always kept.</li>
								<li>Manually uploaded media and non-plugin media are never deleted.</li>
								<li>WordPress-generated sub-sizes are deleted together with their parent attachment.</li>
							</ul>
							<p class="description"><strong>Use with caution:</strong> cleanup permanently deletes matching attachments. A confirmation dialog appears before execution.</p>
							<button id="genart-run-cleanup" type="button" class="button">Run Cleanup</button>
							<div id="genart-cleanup-status" style="margin-top:12px;"></div>
						</div>
					</div>

					<?php submit_button( 'Save Settings' ); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Renders help page.
		 *
		 * @return void
		 */
		public function render_help_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap genart-admin-wrap">
				<h1>GenArt Featured Images Help</h1>
				<div class="genart-admin-grid">
					<div class="card genart-card">
						<h2>How generation priority works</h2>
						<ol>
							<li>Manual selection in post/page editor (style + scheme) has highest priority.</li>
							<li>Saved per-post/page preferences are used next.</li>
							<li>Matching rules are evaluated after that: tag rules first, then category rules.</li>
							<li>If no manual/post/rule value is available, plugin defaults are used.</li>
							<li>If random defaults are enabled, default algorithm/scheme are randomly selected from available lists.</li>
						</ol>
						<p class="description">This order guarantees that editor choices can intentionally override global rules.</p>
					</div>

					<div class="card genart-card">
						<h2>Rule best practices</h2>
						<ul>
							<li>Create only specific rules that are needed for your editorial workflow.</li>
							<li>Prefer tag rules for highly specific content themes.</li>
							<li>Use category rules for broad defaults.</li>
							<li>Avoid too many overlapping conceptual rules. Keep rules easy to audit.</li>
							<li>Each category/tag can only be used once in rules to prevent direct conflicts.</li>
						</ul>
					</div>

					<div class="card genart-card">
						<h2>Cleanup: what it does</h2>
						<p>Cleanup deletes only attachments created by this plugin and currently unused as featured image anywhere in WordPress.</p>
						<ul>
							<li>Plugin-created images are identified by internal marker metadata.</li>
							<li>Any plugin image still used as featured image by any post type is kept.</li>
							<li>Manually uploaded media is never selected by cleanup logic.</li>
							<li>WordPress generated sub-sizes are removed together with the attachment by core media deletion.</li>
						</ul>
						<p class="description"><strong>Important:</strong> Cleanup is irreversible. Run it only when you are sure old generated media is no longer needed.</p>
					</div>

					<div class="card genart-card">
						<h2>Recommended workflow</h2>
						<ol>
							<li>Define defaults and random mode first.</li>
							<li>Add only essential custom schemes.</li>
							<li>Create taxonomy rules for repeated editorial patterns.</li>
							<li>Use editor manual controls when specific posts/pages require exceptions.</li>
							<li>Use dry run before bulk generation.</li>
							<li>Run cleanup periodically to remove unused generated media.</li>
						</ol>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Handles dry run.
		 *
		 * @return void
		 */
		public function handle_dry_run() {
			check_ajax_referer( self::NONCE_ACTION );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error( array( 'message' => 'GD with WebP support is required to generate images.' ), 500 );
			}

			$batch_size = $this->get_optimal_batch_size();
			$pending    = $this->count_posts_missing_thumbnail();

			$html = sprintf(
				'<div class="notice notice-info inline"><p><strong>Batch level:</strong> %1$s<br><strong>Posts pending image generation:</strong> %2$d</p></div>',
				esc_html( $this->get_level_name( $batch_size ) ),
				(int) $pending
			);

			wp_send_json_success( array( 'html' => $html ) );
		}

		/**
		 * Handles bulk generation.
		 *
		 * @return void
		 */
		public function handle_bulk_ajax() {
			check_ajax_referer( self::NONCE_ACTION );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error( array( 'message' => 'GD with WebP support is required to generate images.' ), 500 );
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
			$response  = array(
				'remaining' => (int) $remaining,
				'message'   => sprintf( '%d posts remaining.', (int) $remaining ),
			);
			if ( ! empty( $errors ) ) {
				$response['errors'] = array_slice( array_unique( $errors ), 0, 3 );
			}

			wp_send_json_success( $response );
		}

		/**
		 * Cleans up unused plugin-generated images.
		 *
		 * @return void
		 */
		public function handle_cleanup_generated_ajax() {
			check_ajax_referer( self::NONCE_ACTION );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			$attachments = get_posts(
				array(
					'post_type'              => 'attachment',
					'post_status'            => 'inherit',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => array(
						array(
							'key'   => self::META_GENERATED,
							'value' => '1',
						),
					),
				)
			);

			$used_attachment_ids = $this->get_used_featured_attachment_ids();

			$deleted = 0;
			$kept    = 0;
			$errors  = 0;

			foreach ( $attachments as $attachment_id ) {
				$attachment_id = absint( $attachment_id );
				if ( $attachment_id <= 0 ) {
					continue;
				}

				if ( isset( $used_attachment_ids[ $attachment_id ] ) ) {
					$kept++;
					continue;
				}

				// Force delete so WordPress also removes attachment files and generated sub-sizes.
				$removed = wp_delete_attachment( $attachment_id, true );
				if ( $removed ) {
					$deleted++;
				} else {
					$errors++;
				}
			}

			wp_send_json_success(
				array(
					'message' => sprintf(
						'Cleanup finished. Deleted: %1$d, Kept (still used): %2$d, Errors: %3$d.',
						$deleted,
						$kept,
						$errors
					),
					'deleted' => $deleted,
					'kept'    => $kept,
					'errors'  => $errors,
				)
			);
		}

		/**
		 * Gets attachment IDs currently used as featured image by any post type.
		 *
		 * @return array<int, bool>
		 */
		private function get_used_featured_attachment_ids() {
			global $wpdb;

			$meta_key = '_thumbnail_id';
			$results  = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$meta_key
				)
			);

			$used = array();
			if ( empty( $results ) || ! is_array( $results ) ) {
				return $used;
			}

			foreach ( $results as $attachment_id ) {
				$attachment_id = absint( $attachment_id );
				if ( $attachment_id > 0 ) {
					$used[ $attachment_id ] = true;
				}
			}

			return $used;
		}

		/**
		 * Handles single post generation in editor.
		 *
		 * @return void
		 */
		public function handle_generate_single_ajax() {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
			if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'post', 'page' ), true ) ) {
				wp_send_json_error( array( 'message' => 'Invalid post.' ), 400 );
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			if ( ! $this->can_generate_images() ) {
				wp_send_json_error( array( 'message' => 'GD with WebP support is required to generate images.' ), 500 );
			}

			$style = isset( $_POST['style'] ) ? (string) wp_unslash( $_POST['style'] ) : '';
			if ( ! in_array( $style, array( '1', '2', '3' ), true ) ) {
				$style = '';
			}

			$scheme = isset( $_POST['scheme'] ) ? sanitize_key( wp_unslash( $_POST['scheme'] ) ) : '';
			if ( '' !== $scheme && ! isset( $this->get_all_schemes()[ $scheme ] ) ) {
				$scheme = '';
			}

			if ( '' !== $style ) {
				update_post_meta( $post_id, self::META_STYLE, $style );
			}
			if ( '' !== $scheme ) {
				update_post_meta( $post_id, self::META_SCHEME, $scheme );
			}

			$settings = $this->get_settings();
			$force    = ( '1' === $settings['manual_overwrite_existing'] );
			$result   = $this->run_generation_safely(
				$post_id,
				$force,
				array(
					'algo'    => $style,
					'palette' => $scheme,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
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
					'message'       => 'Featured image generated.',
					'attachmentId'  => $attachment_id,
					'thumbnailHtml' => $thumbnail_html,
				)
			);
		}

		/**
		 * Save handler.
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post Post object.
		 * @return void
		 */
		public function on_save_post( $post_id, $post ) {
			if ( ! $post instanceof WP_Post ) {
				return;
			}
			if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
				return;
			}

			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( isset( $_POST['genart_post_settings_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['genart_post_settings_nonce'] ) );
				if ( wp_verify_nonce( $nonce, 'genart_post_settings' ) ) {
					if ( isset( $_POST['genart_post_style'] ) ) {
						$style = sanitize_text_field( wp_unslash( $_POST['genart_post_style'] ) );
						if ( in_array( $style, array( '1', '2', '3' ), true ) ) {
							update_post_meta( $post_id, self::META_STYLE, $style );
						}
					}

					if ( isset( $_POST['genart_post_scheme'] ) ) {
						$scheme = sanitize_key( wp_unslash( $_POST['genart_post_scheme'] ) );
						if ( isset( $this->get_all_schemes()[ $scheme ] ) ) {
							update_post_meta( $post_id, self::META_SCHEME, $scheme );
						}
					}
				}
			}

			$settings = $this->get_settings();
			if ( '1' !== $settings['auto_generate_on_save'] || has_post_thumbnail( $post_id ) ) {
				return;
			}

			if ( 'auto-draft' === $post->post_status || ! post_type_supports( $post->post_type, 'thumbnail' ) || ! $this->can_generate_images() ) {
				return;
			}

			$prefs  = $this->get_post_generation_preferences( $post_id );
			$result = $this->run_generation_safely( $post_id, false, $prefs );
			if ( is_wp_error( $result ) ) {
				do_action( 'genart_featured_images_generation_error', $result, $post_id );
			}
		}

		/**
		 * Runs generation with warning-to-exception safeguard.
		 *
		 * @param int   $post_id Post ID.
		 * @param bool  $force Force replace existing featured image.
		 * @param array $overrides Optional overrides.
		 * @return int|WP_Error
		 */
		private function run_generation_safely( $post_id, $force = false, $overrides = array() ) {
			set_error_handler(
				static function ( $severity, $message, $file, $line ) {
					throw new ErrorException( $message, 0, $severity, $file, $line );
				}
			);

			try {
				$result = $this->generate_for_post( $post_id, $force, $overrides );
			} catch ( Throwable $throwable ) {
				$result = new WP_Error( 'genart_runtime_error', 'Image generation failed due to a runtime error on the server.' );
			}

			restore_error_handler();
			return $result;
		}

		/**
		 * Checks if generation is possible.
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
		 * Gets batch size by memory limit.
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
		 * Batch profile label.
		 *
		 * @param int $size Batch size.
		 * @return string
		 */
		private function get_level_name( $size ) {
			if ( $size <= 2 ) {
				return 'Safe';
			}
			if ( $size <= 5 ) {
				return 'Balanced';
			}
			return 'Performance';
		}

		/**
		 * Gets post IDs missing thumbnail.
		 *
		 * @param int $limit Limit.
		 * @return int[]
		 */
		private function get_posts_missing_thumbnail( $limit ) {
			$query = new WP_Query(
				array(
					'post_type'              => array( 'post', 'page' ),
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
		 * Counts posts missing thumbnail.
		 *
		 * @return int
		 */
		private function count_posts_missing_thumbnail() {
			$query = new WP_Query(
				array(
					'post_type'              => array( 'post', 'page' ),
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
		 * Returns first matching taxonomy rule for a post.
		 *
		 * @param int $post_id Post ID.
		 * @return array<string,mixed>|null
		 */
		private function get_matching_rule( $post_id ) {
			$settings = $this->get_settings();
			if ( empty( $settings['rules'] ) || ! is_array( $settings['rules'] ) ) {
				return null;
			}

			$tag_terms = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $tag_terms ) || ! is_array( $tag_terms ) ) {
				$tag_terms = array();
			}
			$cat_terms = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $cat_terms ) || ! is_array( $cat_terms ) ) {
				$cat_terms = array();
			}

			// Tag rules first (higher priority), then category rules.
			foreach ( array( 'post_tag', 'category' ) as $taxonomy ) {
				foreach ( $settings['rules'] as $rule ) {
					if ( ! is_array( $rule ) || $taxonomy !== ( $rule['taxonomy'] ?? '' ) ) {
						continue;
					}
					$term_id = isset( $rule['term_id'] ) ? absint( $rule['term_id'] ) : 0;
					if ( $term_id <= 0 ) {
						continue;
					}
					$haystack = 'post_tag' === $taxonomy ? $tag_terms : $cat_terms;
					if ( in_array( $term_id, $haystack, true ) ) {
						return $rule;
					}
				}
			}

			return null;
		}

		/**
		 * Picks a random value from allowed list.
		 *
		 * @param array<int,string> $values Allowed values.
		 * @param string            $fallback Fallback value.
		 * @return string
		 */
		private function pick_random_value( $values, $fallback ) {
			if ( empty( $values ) ) {
				return $fallback;
			}
			return (string) $values[ array_rand( $values ) ];
		}

		/**
		 * Resolves generation config (style + scheme colors).
		 *
		 * @param int   $post_id Post ID.
		 * @param array $overrides Overrides.
		 * @return array{algo:string,palette:string,colors:array<int,string>}
		 */
		private function resolve_generation_config( $post_id, $overrides = array() ) {
			$settings     = $this->get_settings();
			$schemes      = $this->get_all_schemes();
			$algorithms   = array_keys( $this->get_algorithms() );
			$all_scheme_ids = array_keys( $schemes );
			$prefs        = $this->get_post_generation_preferences( $post_id );
			$rule         = $this->get_matching_rule( $post_id );

			$algo = '';
			$palette = '';

			// 1) Explicit overrides (manual button AJAX).
			if ( isset( $overrides['algo'] ) && in_array( (string) $overrides['algo'], $algorithms, true ) ) {
				$algo = (string) $overrides['algo'];
			}
			if ( isset( $overrides['palette'] ) ) {
				$override_palette = sanitize_key( (string) $overrides['palette'] );
				if ( isset( $schemes[ $override_palette ] ) ) {
					$palette = $override_palette;
				}
			}

			// 2) Post-level choices.
			if ( '' === $algo && '' !== $prefs['algo'] ) {
				$algo = $prefs['algo'];
			}
			if ( '' === $palette && '' !== $prefs['palette'] ) {
				$palette = $prefs['palette'];
			}

			// 3) Rule-level choices.
			if ( $rule ) {
				$rule_algos = isset( $rule['algos'] ) && is_array( $rule['algos'] ) ? array_values( array_intersect( $rule['algos'], $algorithms ) ) : array();
				$rule_schemes = isset( $rule['schemes'] ) && is_array( $rule['schemes'] ) ? array_values( array_intersect( $rule['schemes'], $all_scheme_ids ) ) : array();

				if ( '' === $algo ) {
					$algo = $this->pick_random_value( $rule_algos, '' );
				}
				if ( '' === $palette ) {
					$palette = $this->pick_random_value( $rule_schemes, '' );
				}
			}

			// 4) Global defaults (randomized by default).
			$default_algo = in_array( (string) $settings['algo'], $algorithms, true ) ? (string) $settings['algo'] : '1';
			$default_palette = isset( $schemes[ (string) $settings['palette'] ] ) ? (string) $settings['palette'] : 'modern_blue';

			if ( '1' === (string) $settings['randomize_defaults'] ) {
				if ( '' === $algo ) {
					$algo = $this->pick_random_value( $algorithms, $default_algo );
				}
				if ( '' === $palette ) {
					$palette = $this->pick_random_value( $all_scheme_ids, $default_palette );
				}
			} else {
				if ( '' === $algo ) {
					$algo = $default_algo;
				}
				if ( '' === $palette ) {
					$palette = $default_palette;
				}
			}

			if ( ! isset( $schemes[ $palette ] ) ) {
				$palette = 'modern_blue';
			}

			return array(
				'algo'    => $algo,
				'palette' => $palette,
				'colors'  => $schemes[ $palette ]['colors'],
			);
		}

		/**
		 * Generates featured image.
		 *
		 * @param int   $post_id Post ID.
		 * @param bool  $force Force replace.
		 * @param array $overrides Overrides.
		 * @return int|WP_Error
		 */
		private function generate_for_post( $post_id, $force = false, $overrides = array() ) {
			if ( has_post_thumbnail( $post_id ) && ! $force ) {
				return (int) get_post_thumbnail_id( $post_id );
			}

			$config = $this->resolve_generation_config( $post_id, $overrides );
			$image  = @imagecreatetruecolor( 1200, 630 );
			if ( false === $image ) {
				return new WP_Error( 'genart_image_create_failed', 'Unable to initialize image canvas.' );
			}

			$palette = $this->get_image_palette_colors( $image, 85, $config['colors'] );
			if ( empty( $palette ) ) {
				imagedestroy( $image );
				return new WP_Error( 'genart_palette_failed', 'No valid colors available for rendering.' );
			}

			$background_palette = $this->get_image_palette_colors( $image, 0, $config['colors'] );
			imagefill( $image, 0, 0, $background_palette[0] );

			if ( '1' === $config['algo'] ) {
				for ( $i = 0; $i < 12; $i++ ) {
					imagefilledellipse( $image, wp_rand( 0, 1200 ), wp_rand( 0, 630 ), wp_rand( 400, 900 ), wp_rand( 400, 900 ), $palette[ array_rand( $palette ) ] );
				}
			} elseif ( '2' === $config['algo'] ) {
				for ( $i = 0; $i < 15; $i++ ) {
					imagefilledrectangle( $image, wp_rand( 0, 800 ), wp_rand( 0, 400 ), wp_rand( 400, 1200 ), wp_rand( 300, 630 ), $palette[ array_rand( $palette ) ] );
				}
			} else {
				for ( $i = 0; $i < 60; $i++ ) {
					imageline( $image, wp_rand( 0, 1200 ), 0, wp_rand( 0, 1200 ), 630, $palette[ array_rand( $palette ) ] );
				}
			}

			$result = $this->attach_generated_image( $image, $post_id );
			imagedestroy( $image );
			return $result;
		}

		/**
		 * Attaches generated image.
		 *
		 * @param resource $image GD image resource.
		 * @param int      $post_id Post ID.
		 * @return int|WP_Error
		 */
		private function attach_generated_image( $image, $post_id ) {
			$post_title = get_the_title( $post_id );
			$site_name  = get_bloginfo( 'name' );
			$settings   = $this->get_settings();

			$seo_text = strtr(
				(string) $settings['seo_template'],
				array(
					'%title%'    => $post_title ? $post_title : '',
					'%sitename%' => $site_name ? $site_name : '',
				)
			);
			$seo_text = sanitize_text_field( $seo_text );
			if ( '' === $seo_text ) {
				$seo_text = sanitize_text_field( (string) $post_title );
			}

			$tmp_path = wp_tempnam( 'genart-image-' . $post_id );
			if ( empty( $tmp_path ) ) {
				return new WP_Error( 'genart_temp_file_failed', 'Unable to create temporary file for image.' );
			}

			$quality = absint( $settings['webp_quality'] );
			if ( $quality < 10 ) {
				$quality = 10;
			} elseif ( $quality > 100 ) {
				$quality = 100;
			}

			$rendered = @imagewebp( $image, $tmp_path, $quality );
			if ( false === $rendered ) {
				@unlink( $tmp_path );
				return new WP_Error( 'genart_webp_write_failed', 'Unable to write WebP image to temporary file.' );
			}

			$file_array = array(
				'name'     => sanitize_file_name( 'genart-featured-' . sanitize_title( (string) $post_title ) . '-' . $post_id . '.webp' ),
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
			update_post_meta( $attachment_id, self::META_GENERATED, '1' );
			update_post_meta( $attachment_id, '_genart_featured_images_source_post_id', (int) $post_id );
			wp_update_post(
				array(
					'ID'           => $attachment_id,
					'post_title'   => 'GenArt Generated - ' . ( $post_title ? $post_title : 'Untitled' ),
					'post_content' => 'Generated by GenArt Featured Images plugin.',
				)
			);
			set_post_thumbnail( $post_id, $attachment_id );

			return (int) $attachment_id;
		}

		/**
		 * Allocates colors on image resource.
		 *
		 * @param resource          $image GD image resource.
		 * @param int               $alpha Alpha channel.
		 * @param array<int,string> $hex_list HEX colors.
		 * @return int[]
		 */
		private function get_image_palette_colors( $image, $alpha, $hex_list ) {
			$colors = array();

			foreach ( $hex_list as $hex_color ) {
				$hex_color = ltrim( (string) $hex_color, '#' );
				if ( 6 !== strlen( $hex_color ) ) {
					continue;
				}

				$red   = hexdec( substr( $hex_color, 0, 2 ) );
				$green = hexdec( substr( $hex_color, 2, 2 ) );
				$blue  = hexdec( substr( $hex_color, 4, 2 ) );
				$index = imagecolorallocatealpha( $image, $red, $green, $blue, $alpha );
				if ( false !== $index ) {
					$colors[] = $index;
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
		 * Sanitizes comma-separated HEX list.
		 *
		 * @param string $raw_list Input list.
		 * @return string[]
		 */
		private function sanitize_hex_list( $raw_list ) {
			$raw_list = trim( $raw_list );
			if ( '' === $raw_list ) {
				return array();
			}

			$values = explode( ',', $raw_list );
			$valid  = array();

			foreach ( $values as $value ) {
				$value = trim( strtolower( (string) $value ) );
				if ( preg_match( '/^#?[0-9a-f]{6}$/', $value ) ) {
					$valid[] = '#' . ltrim( $value, '#' );
				}
			}

			return array_values( array_unique( $valid ) );
		}
	}
}

new Genart_Featured_Images();
