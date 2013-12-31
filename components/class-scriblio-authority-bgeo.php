<?php

class Scriblio_Authority_bGeo
{
	public $version = 1;
	public $meta_key = 'scriblio-authority-bgeo';
	public $id_base = 'scriblio-authority-bgeo';
	public $post_meta_defaults = array(
		'key' => 'value',
	);

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
	} // END __construct

	public function init()
	{
		register_taxonomy(
			'a-taxonomy',
			NULL,
			array(
				'label'   => 'A Taxonomy',
				'rewrite' => array(
					'slug' => 'a-tax',
				),
				'show_ui'      => FALSE,
				'hierarchical' => FALSE,
			)
		);

		if ( is_admin() )
		{
			wp_enqueue_style( $this->id_base, plugins_url( '/css/scriblio-authority-bgeo.css', __FILE__ ), array(), $this->version );
			wp_enqueue_script( $this->id_base, plugins_url( '/js/scriblio-authority-bgeo.js', __FILE__ ), array( 'jquery' ), $this->version, TRUE );
		}

	} // END init

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type != authority_record()->post_type_name )
		{
			return;
		}

		if ( 'my_tax' != $this->get_primary_tax( $post->ID ) )
		{
			return;
		}

		add_meta_box( $this->id_base, 'Geography', array( $this, 'meta_box' ), authority_record()->post_type_name, 'normal', 'high' );
	} // END add_meta_boxes

	public function meta_box( $post )
	{
		if ( 'my_tax' != $this->get_primary_tax( $post->ID ) )
		{
			return;
		}

		// metabox here

	} // END meta_box

	public function save_post( $post_id )
	{
		// Check that this isn't an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return;
		}//end if

		// Don't run on post revisions (almost always happens just before the real post is saved)
		if( wp_is_post_revision( $post_id ) )
		{
			return;
		}//end if

		// Only work on authority posts
		if( get_post( $post_id )->post_type != authority_record()->post_type_name )
		{
			return;
		}//end if

		// Check the nonce
		if( ! authority_record()->admin()->verify_nonce() )
		{
			return;
		}//end if

		// Check the permissions
		if( ! current_user_can( 'edit_post', $post_id ) )
		{
			return;
		}//end if

		$this->update_post_meta( $post_id, $_POST[ $this->id_base ] );
	} // END save_post

	public function update_post_meta( $post_id, $post_meta )
	{
		// filter the meta to set default values and whitelist the returned keys
		$post_meta = (object) array_replace( // set default values for everything, replace the defaults with specific values where present
			$this->post_meta_defaults,
			array_intersect_key( // only return keys defined in the defaults, never any others
				(array) $post_meta,
				$this->post_meta_defaults
			)
		);

		// parsing and sanitization here

		update_post_meta( $post_id, $this->meta_key, (array) $post_meta );
	} // END update_post_meta

	public function get_post_meta( $post_id )
	{
		// filter the meta to set default values and whitelist the returned keys
		return = (object) array_replace( // set default values for everything, replace the defaults with specific values where present
			$this->post_meta_defaults,
			array_intersect_key( // only return keys defined in the defaults, never any others
				(array) get_post_meta( $post_id, $this->meta_key, TRUE ),
				$this->post_meta_defaults
			)
		);
	} // END get_post_meta

	public function get_primary_tax( $post_id )
	{
		$authority_meta = authority_record()->get_post_meta( $post_id );

		if ( isset( $authority_meta['primary_term']->taxonomy ) )
		{
			return $authority_meta['primary_term']->taxonomy;
		} // END if

		return FALSE;
	} // END get_primary_tax

	public function get_field_name( $field_name )
	{
		return $this->id_base . '[' . $field_name . ']';
	} // END get_field_name

	public function get_field_id( $field_name )
	{
		return $this->id_base . '-' . $field_name;
	} // END get_field_id

}// end class

function scriblio_authority_bgeo()
{
	global $scriblio_authority_bgeo;

	if ( ! is_object( $scriblio_authority_bgeo ) )
	{
		$scriblio_authority_bgeo = new Scriblio_Authority_bGeo;
	}// end if

	return $scriblio_authority_bgeo;
} // END scriblio_authority_bgeo