<?php
/**
 * html for the Details metabox.
 * The context is the bDefinite_Admin class, where $post is set.
 */
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
		<?php
		if ( isset( $meta->woeid_r ) )
		{
			echo '<p><pre>' . print_r( $meta->woeid_r, TRUE ) . '</pre></p>';
		}
		?>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="<?php echo $this->get_field_id( 'wikipedia' ); ?>">Wikipedia page</label>
	</th>
	<td>
		<input type="text" id="<?php echo $this->get_field_id( 'wikipedia' ); ?>" name="<?php echo $this->get_field_name( 'wikipedia' ); ?>" value="<?php echo( esc_attr( $meta->wikipedia ) ); ?>" size="10" />
		<?php
		if ( isset( $meta->wikipedia_r ) )
		{
			echo '<p><pre>' . print_r( $meta->wikipedia_r, TRUE ) . '</pre></p>';
		}
		?>
	</td>
</tr>
</table>
