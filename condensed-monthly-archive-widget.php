<?php
/*
Plugin Name: Condensed Monthly Archive Widget
Plugin URI: http://www.brankocollin.nl
Description: Like the Wordpress Archive widget, but switches to years at some point.
Version: 0.1
Author: Branko Collin
Author URI: http://www.brankocollin.nl
Text Domain: cm_archives
*/

/*
	Copyright 2019, Branko Collin (email: collin@xs4all.nl)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301					USA
*/

/**
 * Register the condensed monthly archive widget.
 */
function cmarchivewidget_load_widgets() {
	register_widget( 'CondensedMonthlyArchiveWidget' );
}
add_action( 'widgets_init', 'cmarchivewidget_load_widgets' );
 

/**
 * Core class used to implement the Condensed Monthly Archives widget.
 *
 * @see WP_Widget_Archives
 */
class CondensedMonthlyArchiveWidget extends WP_Widget {

	public $duration = 2; // Number of years to display uncondensed.
	
	/**
	 * Sets up a new Archives widget instance.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'cm_widget_archive',
			'description'                 => __( 'A monthly / yearly archive of your site&#8217;s Posts.', 'cm_archives' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'cm_archives', __( 'Condensed Monthly Archives', 'cm_archives' ), $widget_ops );
		
		add_filter( 'getarchives_where', array( $this, 'limit_months_filter' ), 10, 3 );
	}

	/**
	 * Outputs the content for the current Archives widget instance.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Archives widget instance.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Archives' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		
		$this->duration = ! empty( $instance['years_to_display_months'] ) ? $instance['years_to_display_months'] : 2;

		$c = ! empty( $instance['count'] ) ? '1' : '0';
		$d = ! empty( $instance['dropdown'] ) ? '1' : '0';

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if ( $d ) {
			$dropdown_id = "{$this->id_base}-dropdown-{$this->number}";
			?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $dropdown_id ); ?>"><?php echo $title; ?></label>
		<select id="<?php echo esc_attr( $dropdown_id ); ?>" name="archive-dropdown">
			<?php
			/**
			 * Filters the arguments for the Archives widget drop-down.
			 *
			 * @see wp_get_archives()
			 *
			 * @param array $args     An array of Archives widget drop-down arguments.
			 * @param array $instance Settings for the current Archives widget instance.
			 */
			$dropdown_args = apply_filters(
				'cm_archives_dropdown_args',
				array(
					'format'          => 'option',
					'show_post_count' => $c,
				),
				$instance
			);

			$label = __( 'Select date' );

			$type_attr = current_theme_supports( 'html5', 'script' ) ? '' : ' type="text/javascript"';
			?>

			<option value=""><?php echo esc_attr( $label ); ?></option>
			<?php $this->get_archives( $dropdown_args ); ?>

		</select>

<script<?php echo $type_attr; ?>>
/* <![CDATA[ */
(function() {
	var dropdown = document.getElementById( "<?php echo esc_js( $dropdown_id ); ?>" );
	function onSelectChange() {
		if ( dropdown.options[ dropdown.selectedIndex ].value !== '' ) {
			document.location.href = this.options[ this.selectedIndex ].value;
		}
	}
	dropdown.onchange = onSelectChange;
})();
/* ]]> */
</script>

		<?php } else { ?>
		<ul>
			<?php
			/**
			 * Filters the arguments for the Archives widget.
			 *
			 * @since 2.8.0
			 * @since 4.9.0 Added the `$instance` parameter.
			 *
			 * @see wp_get_archives()
			 *
			 * @param array $args     An array of Archives option arguments.
			 * @param array $instance Array of settings for the current widget.
			 */
			$this->get_archives(
				apply_filters(
					'cm_archives_args',
					array(
						'show_post_count' => $c,
					),
					$instance
				)
			);
			?>
		</ul>
			<?php
		}

		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Archives widget instance.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget_Archives::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$new_instance         = wp_parse_args(
			(array) $new_instance,
			array(
				'title'    => '',
				'years_to_display_months' => '',
				'count'    => 0,
				'dropdown' => '',
			)
		);
		$instance['title']    = sanitize_text_field( $new_instance['title'] );
		$instance['years_to_display_months']    = sanitize_text_field( $new_instance['years_to_display_months'] );
		$instance['count']    = $new_instance['count'] ? 1 : 0;
		$instance['dropdown'] = $new_instance['dropdown'] ? 1 : 0;

		return $instance;
	}

	/**
	 * Outputs the settings form for the Archives widget.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'    => '',
				'years_to_display_months' => '',
				'count'    => 0,
				'dropdown' => '',
			)
		);
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'years_to_display_months' ); ?>"><?php _e( 'Show months for this many years:' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'years_to_display_months' ); ?>" name="<?php echo $this->get_field_name( 'years_to_display_months' ); ?>" type="text" value="<?php echo esc_attr( $instance['years_to_display_months'] ); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked( $instance['dropdown'] ); ?> id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>" /> <label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown' ); ?></label>
			<br/>
			<input class="checkbox" type="checkbox"<?php checked( $instance['count'] ); ?> id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" /> <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts' ); ?></label>
		</p>
		<?php
	}
	
	/** 
	 * Wrapper for wp_get_archives().
	 */
	public function get_archives( $args = '' ) {
		
		$yearly_args = $monthly_args = $args;
		$yearly_args['type'] = 'yearly';
		$monthly_args['type'] = 'monthly';
		
		if ( ! empty( $args['echo'] ) ) {
			echo wp_get_archives($monthly_args);
			echo wp_get_archives($yearly_args);
		} else {
			$output = '';
			$output .= wp_get_archives($monthly_args);
			$output .= wp_get_archives($yearly_args);
			return $output;
		}
	}

	/** 
	 * Ammends the wp_get_archives() query depending on whether
	 * the current archive type is monthly or yearly.
	 */
	function limit_months_filter( $sql_where , $parsed_args = array() ) { 
		// @todo Make it so this function only gets called for archives
		//      generated by $this->get_archives().

		// print '<pre>';
		// print_r( (array) $this );
		// print '</pre>';
		
		if ( $this->id_base !== 'cm_archives' ) {
			// The filter should only be run for this plugin.
			return $sql_where;
		}

		$years = $this->get_years_with_posts();
		
		$duration = $this->duration;
		
		// Sanitize cut-off that is set higher than there are years?
		if ( $duration > count($years) ) {
			$duration = count($years);
		}
		
		if ( 'monthly' === $parsed_args['type'] ) {
			// Cut off at the last day of the previous year.
			$phrase = $sql_where . " AND post_date > '" . ( $years[$duration-1] - 1 ) . "-12-31 23:59:59'";
			return $phrase;
		}
		if ( 'yearly' === $parsed_args['type'] ) {
			// Cut off at the first day of the current year.
			$phrase = $sql_where . " AND post_date < '" . $years[$duration-1] . "-01-01 00:00:00'";
			return $phrase;
		}
	}

	/**
	 * Find out which years have posts.
	 * @todo make it match the wp_get_archives filters.
	 */
	function get_years_with_posts() {
		global $wpdb;

		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts GROUP BY YEAR(post_date) ORDER BY post_date";
		$results = $wpdb->get_results( $query );

		$years = array();
		foreach ( ( array ) $results as $key => $result ) {
			$years[$key] = $result->year;
		}
		rsort($years);
		
		return $years;
	}
}
