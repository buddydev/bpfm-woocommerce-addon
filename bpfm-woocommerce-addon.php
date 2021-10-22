<?php
/**
 * Plugin Name: BP Featured Members WC Addon
 * Version: 1.0.0
 * Plugin URI: https://buddydev.com/plugins/bp-skeleton
 * Description: Allows site admin to make woocommerce product as a new way for making member as featured.
 * Author: BuddyDev
 * Author URI: https://buddydev.com/
 * Requires PHP: 5.3
 **/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin class
 */
class BPFM_WooCommerce_Addon {

	/**
	 * Singleton Instance
	 *
	 * @var BPFM_WooCommerce_Addon|null
	 */
	private static $instance = null;

	/**
	 * The constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Returns singleton instance
	 *
	 * @return BPFM_WooCommerce_Addon|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup callbacks
	 */
	private function setup() {

		if ( ! function_exists( 'bp_featured_members' ) && ! function_exists( 'WC' ) ) {
			return;
		}

		add_action( 'add_meta_boxes_product', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'on_save' ) );

		// On order status complete.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_status_complete' ) );
		add_action( 'bp_template_redirect', array( $this, 'on_template_redirect' ) );
	}

	/**
	 * Add meta box to product post type
	 *
	 * @param WP_Post $post Post object.
	 */
	public function add_meta_box( $post ) {

		add_meta_box(
			'set_as_featured_member',
			__( 'Featured Member' ),
			array( $this, 'render_wc_featured_member_meta_box' ),
			get_current_screen()->id,
			'side',
			'core',
			$post
		);
	}

	/**
	 * Render Set as featured metabox
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_wc_featured_member_meta_box( $post ) {
		$set_featured_member = get_post_meta( $post->ID, '__set_as_featured_member', true );
		$set_featured_member = $set_featured_member ? 1 : 0;

		$selected_page_id = get_post_meta( $post->ID, '__already_featured_redirect_page_id', true );
		$selected_page_id = $selected_page_id ? $selected_page_id : '';
		?>
		<p>
			<label>
				<input value="1" type="checkbox"
				       name="set-as-featured-member" <?php checked( $set_featured_member, 1 ) ?> />
				<?php esc_html_e( 'If Purchased, User will be set as Featured Member', 'bpfm-woocommerce-addon' ); ?>
			</label>
		</p>
		<p>
			<label>
				<?php _e( 'Select page user will be redirected if already a featured member.', 'bpfm-woocommerce-addon' ); ?><br>
				<?php
				wp_dropdown_pages(
					array(
						'show_option_none' => __( '--Select--', 'bpfm-woocommerce-addon' ),
						'name'             => 'already-featured-member-redirect-page-id',
						'selected'         => $selected_page_id,
					)
				);
				?>
			</label>
		</p>
		<?php
	}

	/**
	 * On save
	 *
	 * @param int $post_id Post id.
	 */
	public function on_save( $post_id ) {

		if ( isset( $_POST['set-as-featured-member'] ) ) {
			update_post_meta( $post_id, '__set_as_featured_member', 1 );
		} else {
			delete_post_meta( $post_id, '__set_as_featured_member' );
		}

		if ( ! empty( $_POST['already-featured-member-redirect-page-id'] ) ) {
			update_post_meta( $post_id, '__already_featured_redirect_page_id', absint( $_POST['already-featured-member-redirect-page-id'] ) );
		} else {
			delete_post_meta( $post_id, '__already_featured_redirect_page_id' );
		}
	}

	/**
	 * On Order status complete
	 *
	 * @param int $order_id Order id.
	 */
	public function on_order_status_complete( $order_id ) {
		$order = wc_get_order( $order_id );

		$set_featured = false;
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();

			if ( get_post_meta( $product_id, '__set_as_featured_member', true ) ) {
				$set_featured = true;
				break;
			}
		}

		if ( $set_featured && ! bp_featured_members()->is_featured( $order->get_user_id() ) ) {
			bp_featured_members()->add_user( $order->get_user_id() );
		}
	}

	/**
	 * On redirect
	 */
	public function on_template_redirect() {

		// If not single product page or user not logged in.
		if ( ! is_product() || ! is_user_logged_in() ) {
			return;
		}

		$product_id = get_queried_object_id();

		// If not product making user featured redirect.
		if ( ! get_post_meta( $product_id, '__set_as_featured_member', true ) ) {
			return;
		}

		$is_featured_member = bp_featured_members()->is_featured( get_current_user_id() );

		if ( ! $is_featured_member ) {
			return;
		}

		$already_featured_redirect_page_id = get_post_meta( $product_id, '__already_featured_redirect_page_id', true );

		if ( $already_featured_redirect_page_id && ! is_page( $already_featured_redirect_page_id ) ) {
			wp_safe_redirect( get_permalink( $already_featured_redirect_page_id ) );
		}
	}
}

BPFM_WooCommerce_Addon::get_instance();

