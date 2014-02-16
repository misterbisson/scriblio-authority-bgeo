<?php

class Scriblio_Authority_bGeo
{
	public $dbpedia = FALSE;
	public $id_base = 'scriblio-authority-bgeo';
	public $meta_key = 'scriblio-authority-bgeo';
	public $post_meta_defaults = array(
		'type' => FALSE,
		'woeid' => FALSE,
		'woeid_r' => FALSE,
		'wikipedia' => FALSE,
		'wikipedia_r' => FALSE,
	);
	public $version = 1;
	public $wikipedia = FALSE;

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
	} // END __construct

	public function init()
	{

//echo '<pre>';
//print_r( $this->dbpedia()->get( 'Midwestern_United_States' ) );
//print_r( $this->dbpedia()->errors );
//print_r( $this->wikipedia()->get( 'Los_Angeles_(disambiguation)' ) );
//print_r( $this->wikipedia()->search( 'Sierra Leone, Northern, Sierra Leone' ) );
//print_r( $this->wikipedia()->search( 'Saint Barthelemy' ) );
//print_r( $this->wikipedia()->get( 'Mississippi' ) );
//print_r( $this->wikipedia()->errors );
//echo '</pre>';

		// do not continue if the required plugins are not active
		if (
			! function_exists( 'authority_record' ) ||
			! function_exists( 'bgeo' )
		)
		{
			return;
		}

		// we depend on both the custom taxonomy registed in bGeo as well as
		// a custom taxonomy describing the type of geography
		register_taxonomy(
			'bgeo_types',
			array( authority_record()->post_type_name ),
			array(
				'label' => 'Geographies',
				'labels' => array(
					'singular_name' => 'Geography type',
					'menu_name' => 'Geography types',
					'all_items' => 'All geography types',
					'edit_item' => 'Edit geography type',
					'view_item' => 'View geography type',
					'update_item' => 'Update geography type',
					'add_new_item' => 'Add geography type',
					'new_item_name' => 'New geography type',
					'search_items' => 'Search geography types',
					'popular_items' => 'Popular geography types',
					'separate_items_with_commas' => 'Separate geography types with commas',
					'add_or_remove_items' => 'Add or remove geography types',
					'choose_from_most_used' => 'Choose from most used geography types',
					'not_found' => 'No geography types found',
				),
				// 'hierarchical' => TRUE,
				'show_ui' => TRUE,
				'show_admin_column' => TRUE,
				'query_var' => TRUE,
				'rewrite' => array(
					'slug' => 'geography-type',
					'with_front' => FALSE,
				),
			)
		);

		// conditionally register the bgeo custom taxonomy
		if ( ! is_tax( bgeo()->geo_taxonomy_name ) )
		{
			bgeo()->register_taxonomy();
		}

		// make sure both taxonomies are registered with the Scriblio Authority plugin
		authority_record()->add_taxonomy( bgeo()->geo_taxonomy_name );
		authority_record()->add_taxonomy( 'bgeo_types' );

		if ( is_admin() )
		{
			wp_enqueue_style( $this->id_base, plugins_url( '/css/scriblio-authority-bgeo.css', __FILE__ ), array(), $this->version );
			wp_enqueue_script( $this->id_base, plugins_url( '/js/scriblio-authority-bgeo.js', __FILE__ ), array( 'jquery' ), $this->version, TRUE );
		}

	} // END init

	// a singleton for the dbpedia object
	public function dbpedia()
	{
		if ( ! $this->dbpedia )
		{
			require_once __DIR__ . '/class-scriblio-authority-bgeo-dbpedia.php';
			$this->dbpedia = new Scriblio_Authority_bGeo_DBpedia();
		}

		return $this->dbpedia;
	} // END dbpedia

	// a singleton for the wikipedia object
	public function wikipedia()
	{
		if ( ! $this->wikipedia )
		{
			require_once __DIR__ . '/class-scriblio-authority-bgeo-wikipedia.php';
			$this->wikipedia = new Scriblio_Authority_bGeo_Wikipedia();
		}

		return $this->wikipedia;
	} // END wikipedia

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type != authority_record()->post_type_name )
		{
			return;
		}

		if (
			! ( $primary_term = authority_record()->get_primary_term( $post->ID ) ) ||
			$primary_term->taxonomy != bgeo()->geo_taxonomy_name
		)
		{
			return;
		}

		add_meta_box( $this->id_base, 'Geography', array( $this, 'meta_box' ), authority_record()->post_type_name, 'normal', 'high' );

	} // END add_meta_boxes

	public function meta_box( $post )
	{

		if (
			! ( $primary_term = authority_record()->get_primary_term( $post->ID ) ) ||
			$primary_term->taxonomy != bgeo()->geo_taxonomy_name
		)
		{
			return;
		}

		// get our meta
		$meta = $this->get_post_meta( $post->ID );

		// get the primary term's geo object
		$geo = bgeo()->get_geo( $primary_term->term_id, $primary_term->taxonomy );
		// create a default geo obgect if the above failed
		if( ! is_object( $geo ))
		{
			$geo = (object) array(
				'point' => NULL,
				'point_lat' => NULL,
				'point_lon' => NULL,
				'bounds' => NULL,
			);
		}
		wp_localize_script( $this->id_base . '-admin', $this->id_base . '_term', (array) $geo );

		include_once __DIR__ . '/templates/metabox-details.php';

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
		$post_meta = array_replace( // set default values for everything, replace the defaults with specific values where present
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
		return (object) array_replace( // set default values for everything, replace the defaults with specific values where present
			$this->post_meta_defaults,
			array_intersect_key( // only return keys defined in the defaults, never any others
				(array) get_post_meta( $post_id, $this->meta_key, TRUE ),
				$this->post_meta_defaults
			)
		);
	} // END get_post_meta

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