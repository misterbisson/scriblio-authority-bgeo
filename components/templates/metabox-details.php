<?php
/**
 * html for the Details metabox.
 * The context is the bDefinite_Admin class, where $post is set.
 */

$geo = bgeo()->get_geo( $tag->term_id, $tag->taxonomy );

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

?>
<table class="form-table">
<?php

if ( isset( $geo->point, $geo->point_lat, $geo->point_lon, $geo->bounds ) ) 
{
?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="<?php echo $this->get_field_id( 'map' ); ?>">Map</label>
		</th>
		<td>
			<div id="<?php echo $this->get_field_id( 'map' ); ?>">
				<a href="<?php echo get_edit_term_link( $tag->term_id, $tag->taxonomy ); ?>">edit map</a>
			</div>
		</td>
	</tr>
<?php
}
?>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="<?php echo $this->get_field_id( 'woeid' ); ?>">Woeid</label>
	</th>
	<td>
		<input type="text" id="<?php echo $this->get_field_id( 'woeid' ); ?>" name="<?php echo $this->get_field_name( 'woeid' ); ?>" value="<?php echo( esc_attr( $meta->woeid ) ); ?>" size="10" />
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="<?php echo $this->get_field_id( 'wikipedia' ); ?>">Wikipedia page</label>
	</th>
	<td>
		<input type="text" id="<?php echo $this->get_field_id( 'wikipedia' ); ?>" name="<?php echo $this->get_field_name( 'wikipedia' ); ?>" value="<?php echo( esc_attr( $meta->wikipedia ) ); ?>" size="10" />
	</td>
</tr>
</table>
