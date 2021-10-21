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

		if ( ! function_exists( 'bp_featured_members' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		add_action( 'add_meta_boxes_product', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'on_save' ) );

		// On order status complete.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_status_complete' ) );
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

		echo sprintf( '<label><input value="1" type="checkbox" name="set-as-featured-member" %s />%s</label>', checked( $set_featured_member, 1, false ), __( 'If Purchased, User will be set as Featured Member', 'bpfm-woocommerce-addon' ) );
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
			if ( get_post_meta( $item->get_product_id(), '__set_as_featured_member', true ) ) {
				$set_featured = true;
				break;
			}
		}

		if ( $set_featured && bp_featured_members()->is_featured( $order->get_user_id() ) ) {
			bp_featured_members()->add_user( $order->get_user_id() );
		}
	}
}

BPFM_WooCommerce_Addon::get_instance();

