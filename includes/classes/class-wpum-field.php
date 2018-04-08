<?php
/**
 * Database abstraction layer to work with fields stored into the database.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that stores the DB field object.
 */
class WPUM_Field {

	/**
	 * Field ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Group ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $group_id = 0;

	/**
	 * Field order.
	 *
	 * @access protected
	 * @var int
	 */
	protected $field_order = 0;

	/**
	 * Wether the field is a primary field or not.
	 *
	 * @var boolean
	 */
	protected $is_primary = false;

	/**
	 * Holds a special ID for primary fields.
	 *
	 * @var string
	 */
	protected $primary_id = null;

	/**
	 * The field type.
	 *
	 * @var boolean
	 */
	protected $type = false;

	/**
	 * The nicename of the field type.
	 *
	 * @var string
	 */
	protected $type_nicename = null;

	/**
	 * Field Name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = null;

	/**
	 * Field Description.
	 *
	 * @access protected
	 * @var string
	 */
	protected $description = null;

	/**
	 * Determine the visibility of this field.
	 *
	 * @var string
	 */
	protected $visibility = null;

	/**
	 * Determine the editability of this field.
	 *
	 * @var string
	 */
	protected $editable = null;

	/**
	 * Determine wether the field is required or not.
	 *
	 * @var boolean
	 */
	protected $required = false;

	/**
	 * The Database Abstraction
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @param mixed|boolean $_id
	 */
	public function __construct( $_id_or_field = false ) {

		$this->db = new WPUM_DB_Fields();

		if( empty( $_id_or_field ) ) {
			return false;
		}

		if( is_a( $_id_or_field, 'WPUM_Field' ) ) {
			$field = $_id_or_field;
		} else {
			$_id_or_field = intval( $_id_or_field );
			$field        = $this->db->get( $_id_or_field );
		}

		if ( $field ) {
			$this->setup_field( $field );
		} else {
			return false;
		}

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __get( $key ) {
		if( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			return new WP_Error( 'wpum-field-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );
		}
	}

	/**
	 * Setup the field.
	 *
	 * @param mixed $field
	 * @return void
	 */
	private function setup_field( $field = null ) {

		if ( null == $field ) {
			return false;
		}

		if ( ! is_object( $field ) ) {
			return false;
		}

		if ( is_wp_error( $field ) ) {
			return false;
		}

		foreach ( $field as $key => $value ) {
			switch ( $key ) {
				default:
					$this->$key = $value;
					break;
			}
		}

		if ( ! empty( $this->id ) ) {

			$this->type_nicename = $this->get_field_type_name( $this->type );
			$this->is_primary    = $this->set_as_primary_field( $this->type );
			$this->required      = $this->get_meta( 'required' );
			$this->visibility    = $this->get_meta( 'visibility' );
			$this->editable      = $this->get_meta( 'editing' );

			return true;
		}

		return false;

	}

	/**
	 * Retrieve the field id.
	 *
	 * @return void
	 */
	public function get_ID() {
		return $this->id;
	}

	/**
	 * Retrieve the group id assigned to the field.
	 *
	 * @return string
	 */
	public function get_group_id() {
		return $this->group_id;
	}

	/**
	 * Retrieve the order priority number of the field.
	 *
	 * @return string
	 */
	public function get_field_order() {
		return $this->field_order;
	}

	/**
	 * Retrieve the field name.
	 *
	 * @return void
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Retrieve the field type.
	 *
	 * @return void
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Retrieve the field description.
	 *
	 * @return void
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if this is a primary field.
	 *
	 * @return boolean
	 */
	public function is_primary() {
		return $this->is_primary;
	}

	/**
	 * Return the primary id if this field is a primary field.
	 *
	 * @return mixed
	 */
	public function get_primary_id() {
		return $this->primary_id;
	}

	/**
	 * Retrieve the visibility of the field.
	 *
	 * @return string
	 */
	public function get_visibility() {
		return $this->visibility;
	}

	/**
	 * Retrieve the editability of the field.
	 *
	 * @return string
	 */
	public function get_editable() {
		return $this->editable;
	}

	/**
	 * Retrieve the name of the field type from it's class.
	 *
	 * @param string $type
	 * @return void
	 */
	private function get_field_type_name( $type ) {

		$registered_types = wpum_get_registered_field_types_names();
		$type_name        = '';

		if( array_key_exists( $type, $registered_types ) ) {
			$type_name = $registered_types[ $type ];
		} else {

			switch ( $type ) {
				case 'username':
					$type_name = esc_html__( 'Username' );
					break;
				case 'user_email':
					$type_name = esc_html__( 'User email' );
					break;
				case 'user_password':
					$type_name = esc_html__( 'User password' );
					break;
				case 'user_firstname':
					$type_name = esc_html__( 'First name' );
					break;
				case 'user_lastname':
					$type_name = esc_html__( 'Last name' );
					break;
				case 'user_nickname':
					$type_name = esc_html__( 'Nickname' );
					break;
				case 'user_displayname':
					$type_name = esc_html__( 'Display name' );
					break;
				case 'user_website':
					$type_name = esc_html__( 'Website' );
					break;
				case 'user_description':
					$type_name = esc_html__( 'Description' );
					break;
				case 'user_avatar':
					$type_name = esc_html__( 'Avatar' );
					break;
				default:
					$type_name = $registered_types['text'];
					break;
			}

		}

		return $type_name;
	}

	/**
	 * Retrieve the field's type nice name.
	 *
	 * @return string
	 */
	public function get_type_nicename() {
		return $this->type_nicename;
	}

	/**
	 * Set a field as primary field when the type within a list of specific fields.
	 *
	 * Modify the filed type if it's a primary field so that we can still load a general field type
	 * to define all the settings within the editor.
	 *
	 * @param string $type
	 * @return void
	 */
	private function set_as_primary_field( $type ) {

		$primary = false;

		if( in_array( $type, wpum_get_primary_field_types() ) ) {
			$primary = true;
			$this->primary_id = $type;

			switch ( $type ) {
				case 'username':
				case 'user_firstname':
				case 'user_lastname':
				case 'user_nickname':
					$this->type = 'text';
					break;
				case 'user_website':
					$this->type = 'url';
					break;
				case 'user_email':
					$this->type = 'email';
					break;
				case 'user_password':
					$this->type = 'password';
					break;
				case 'user_displayname':
					$this->type = 'dropdown';
					break;
				case 'user_description':
					$this->type = 'textarea';
					break;
				case 'user_avatar':
					$this->type = 'file';
					break;
			}

		}

		return $primary;

	}

	/**
	 * Check if the field is required or not.
	 *
	 * @return boolean
	 */
	public function is_required() {
		return (bool) $this->required;
	}

	/**
	 * Check if a field exists.
	 *
	 * @return boolean
	 */
	public function exists() {
		if ( ! $this->id > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Add a new field to the database.
	 *
	 * @param array $args
	 * @return void
	 */
	public function add( $args ) {

		if ( empty( $args['name'] ) || empty( $args['type'] ) || empty( $args['group_id'] ) ) {
			return false;
		}

		if ( ! empty( $this->id ) && $this->exists() ) {

			return $this->update( $args );

		} else {

			$args = apply_filters( 'wpum_insert_field', $args );
			$args = $this->sanitize_columns( $args );

			do_action( 'wpum_pre_insert_field', $args );

			foreach ( $args as $key => $value ) {
				$this->$key = $value;
			}

			if ( $id = $this->db->insert( $args ) ) {
				$this->id = $id;
				$this->setup_field( $id );
			}

		}

		do_action( 'wpum_post_insert_field', $args, $this->id );

		return $id;

	}

	/**
	 * Update an existing field.
	 *
	 * @param array $args
	 * @return void
	 */
	public function update( $args ) {

		$ret  = false;
		$args = apply_filters( 'wpum_update_field', $args, $this->id );
		$args = $this->sanitize_columns( $args );

		do_action( 'wpum_pre_update_field', $args, $this->id );

		if ( count( array_intersect_key( $args, $this->db->get_columns() ) ) > 0 ) {
			if ( $this->db->update( $this->id, $args ) ) {
				$field = $this->db->get( $this->id );
				$this->setup_field( $field );
				$ret = true;
			}
		} elseif ( 0 === count( array_intersect_key( $args, $this->db->get_columns() ) ) ) {
			$field = $this->db->get( $this->id );
			$this->setup_field( $field );
			$ret = true;
		}

		do_action( 'wpum_post_update_field', $args, $this->id );

		return $ret;

	}

	/**
	 * Sanitize columns before adding a field to the database.
	 *
	 * @param array $data
	 * @return void
	 */
	private function sanitize_columns( $data ) {

		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {

			// Only sanitize data that we were provided
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch( $type ) {
				case '%s':
					if( is_array( $data[$key] ) ) {
						$data[$key] = json_encode( $data[$key] );
					} else {
						$data[$key] = sanitize_text_field( $data[$key] );
					}
				break;
				case '%d':
					if ( ! is_numeric( $data[$key] ) || (int) $data[$key] !== absint( $data[$key] ) ) {
						$data[$key] = $default_values[$key];
					} else {
						$data[$key] = absint( $data[$key] );
					}
				break;
				default:
					$data[$key] = sanitize_text_field( $data[$key] );
				break;
			}

		}

		return $data;

	}

	/**
	 * Retrieve field meta field for a field.
	 *
	 * @param   string $meta_key      The meta key to retrieve.
	 * @param   bool   $single        Whether to return a single value.
	 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return WPUM()->field_meta->get_meta( $this->id, $meta_key, $single );
	}

	/**
	 * Add meta data field to a field.
	 *
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   bool   $unique        Optional, default is false. Whether the same key should not be added.
	 * @return  bool                  False for failure. True for success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return WPUM()->field_meta->add_meta( $this->id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update field meta field based on field ID.
	 *
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 * @return  bool                  False on failure, true if success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return WPUM()->field_meta->update_meta( $this->id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete field meta field based on field ID.
	 *
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 * @return  bool                  False on failure, true if success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function delete_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return WPUM()->field_meta->delete_meta( $this->id, $meta_key, $meta_value, $prev_value );
	}

}
