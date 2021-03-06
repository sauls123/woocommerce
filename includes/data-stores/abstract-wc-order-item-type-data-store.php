<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
abstract class Abstract_WC_Order_Item_Type_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'order_item';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 * @var string
	 */
	protected $object_id_field_for_meta = 'order_item_id';

	/**
	 * Create a new order item in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $item->get_name(),
			'order_item_type' => $item->get_type(),
			'order_id'        => $item->get_order_id(),
		) );
		$item->set_id( $wpdb->insert_id );
		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();

		do_action( 'woocommerce_new_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Update a order item in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function update( &$item ) {
		global $wpdb;

		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $item->get_name(),
			'order_item_type' => $item->get_type(),
			'order_id'        => $item->get_order_id(),
		), array( 'order_item_id' => $item->get_id() ) );

		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();

		do_action( 'woocommerce_update_order_item', $item->get_id(), $item, $item->get_order_id() );
	}

	/**
	 * Remove an order item from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;
			do_action( 'woocommerce_before_delete_order_item', $item->get_id() );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', array( 'order_item_id' => $item->get_id() ) );
			do_action( 'woocommerce_delete_order_item', $item->get_id() );
		}
	}

	/**
	 * Read a order item from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item->get_id() ) );

		if ( ! $data ) {
			throw new Exception( __( 'Invalid order item.', 'woocommerce' ) );
		}

		$item->set_props( array(
			'order_id' => $data->order_id,
			'name'     => $data->order_item_name,
			'type'     => $data->order_item_type,
		) );
		$item->read_meta_data();
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function save_item_data( &$item ) {}
}
