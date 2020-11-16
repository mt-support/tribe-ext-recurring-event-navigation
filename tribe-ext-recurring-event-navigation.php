<?php
/**
 * Plugin Name:       The Events Calendar Pro Extension: Recurring Event Navigation
 * Plugin URI:        https://theeventscalendar.com/extensions/recurring-event-navigation/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-recurring-event-navigation
 * Description:       Adds previous / next in the series navigation on the single event page of a recurring event.
 * Version:           0.9.1
 * Extension Class:   Tribe\Extensions\RecurringEventNavigation\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-recurring-event-navigation
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\RecurringEventNavigation;

use Tribe__Extension;
use Tribe__Date_Utils;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if ( class_exists( 'Tribe__Extension' ) && ! class_exists( Main::class ) ) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Pro__Main', '5.0' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			load_plugin_textdomain( 'tribe-ext-recurring-event-navigation', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			add_action( 'tribe_template_after_include:events/single-event/recurring-description', [ $this, 'render_recurring_event_navigation' ], 10, 3 );
			add_action( 'tribe_events_single_event_before_the_content', [ $this, 'render_recurring_event_navigation' ] );
		}

		/**
		 * Rendering the markup.
		 *
		 * @param $file
		 * @param $name
		 * @param $template
		 */
		public function render_recurring_event_navigation( $file = null, $name = null, $template = null ) {
			?>
			<div class="tribe-events-single-event-recurrence-recurrence-navigation">
				<p>
					<?php
					// Get the current event start date.
					$current_event_start_date = get_post_meta( get_post()->ID, '_EventStartDate', true );

					// Get the start dates of all events in the series.
					$event_dates = tribe_get_recurrence_start_dates();

					// Get the position of the current event in the array.
					$position = array_search( $current_event_start_date, $event_dates );

					// Get the position of the previous event in the array / series.
					$previous = 0;
					if ( $position > $this->array_key_first_polyfill( $event_dates ) ) {
						$previous = (int) $position - 1;
					}

					// Get the position of the next event in the array / series.
					$next = count( $event_dates ) - 1;
					if ( $position < $this->array_key_last_polyfill( $event_dates ) ) {
						$next = (int) $position + 1;
					}

					echo '<span class="tribe-events-single-event-recurrence-recurrence-navigation__previous">';
					if ( $position > $this->array_key_first_polyfill( $event_dates ) ) {
						printf(
						/* translators: %1$s: Singular Event label; %2$s: Opening anchor tag; %3$s Closing anchor tag. */
							esc_html__( '%2$sPrevious %1$s in series%3$s', 'tribe-ext-recurring-event-navigation' ),
							tribe_get_event_label_singular_lowercase(),
							'<a href="' . $this->get_nav_url( $previous ) . '">',
							'</a>'
						);
					} else {
						printf(
						/* translators: %s: Singular Event label. */
							esc_html__( 'This is the first %s in the series', 'tribe-ext-recurring-event-navigation' ),
							tribe_get_event_label_singular_lowercase()
						);
					}
					echo "</span>";

					echo ' <span class="tribe-events-single-event-recurrence-recurrence-navigation__separator">|</span> ';

					echo '<span class="tribe-events-single-event-recurrence-recurrence-navigation__next">';
					if ( $position < $this->array_key_last_polyfill( $event_dates ) ) {
						printf(
						/* translators: %1$s: Singular Event label; %2$s: Opening anchor tag; %3$s Closing anchor tag. */
							esc_html__( '%2$sNext %1$s in series%3$s', 'tribe-ext-recurring-event-navigation' ),
							tribe_get_event_label_singular_lowercase(),
							'<a href="' . $this->get_nav_url( $next ) . '">',
							'</a>'
						);
					} else {
						printf(
						/* translators: %s: Singular Event label. */
							esc_html__( 'This is the last %s in the series', 'tribe-ext-recurring-event-navigation' ),
							tribe_get_event_label_singular_lowercase()
						);
					}
					echo "</span>";
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Polyfill for array_key_first() function added in PHP 7.3.
		 *
		 * Get the first key of the given array without affecting
		 * the internal array pointer.
		 *
		 * @param array $array An array
		 *
		 * @return mixed The first key of array if the array is not empty; NULL otherwise.
		 */
		function array_key_first_polyfill( array $array ) {

			if ( function_exists( 'array_key_first' ) ) {
				return array_key_first( $array );
			}

			foreach ( $array as $key => $unused ) {
				return $key;
			}

			return null;
		}

		/**
		 * Polyfill for array_key_last() function added in PHP 7.3.
		 *
		 * Get the last key of the given array without affecting
		 * the internal array pointer.
		 *
		 * @param array $array An array
		 *
		 * @return mixed The last key of array if the array is not empty; NULL otherwise.
		 */
		function array_key_last_polyfill( array $array ) {

			if ( function_exists( 'array_key_last' ) ) {
				return array_key_last( $array );
			}

			$key = null;

			if ( is_array( $array ) ) {

				end( $array );
				$key = key( $array );
			}

			return $key;
		}

		/**
		 * Building the navigation URL.
		 *
		 * @param int  $key     Key of the date that needs to be retrieved.
		 * @param null $post_id The post ID.
		 *
		 * @return string
		 */
		private function get_nav_url( $key, $post_id = null ) {

			$event_dates = tribe_get_recurrence_start_dates();

			// Get the 'all' URL.
			$url = tribe_all_occurences_link( $post_id, false );

			// Get the 'all/' string. Returns the translated version.
			$all_slug = trailingslashit( tribe( 'events-pro.main' )->all_slug );

			// Cut off 'all/'
			$url = str_replace( $all_slug, '', $url );

			// Add date
			$url .= Tribe__Date_Utils::date_only( $event_dates[ $key ] );

			return $url;
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
					$message = '<p>';
					$message .= sprintf(
						__(
							'%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.',
							'tribe-ext-recurring-event-navigation'
						),
						$this->get_name(),
						$php_required_version
					);
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'tribe-ext-recurring-event-navigation-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}
	}

	//} // end class
} // end if class_exists check
