<?php
/**
 * Plugin Name:       The Events Calendar Extension: Daystrip
 * Plugin URI:        https://theeventscalendar.com/extensions/daystrip/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-daystrip
 * Description:       Adds a day strip at the top of the Day View.
 * Version:           1.0.0
 * Extension Class:   Tribe\Extensions\Daystrip\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-daystrip
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

namespace Tribe\Extensions\Daystrip;

use Tribe__Autoloader;
use Tribe__Extension;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if ( class_exists( 'Tribe__Extension' ) && ! class_exists( Main::class ) ) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * @var Settings
		 */
		private $settings;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main', '5.0' );
		}

		/**
		 * Get this plugin's options prefix.
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * @return string
		 * @see \Tribe\Extensions\Daystrip\Settings::set_options_prefix()
		 *
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', 'tribe-ext-daystrip' );
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-daystrip.pot' file
			load_plugin_textdomain( 'tribe-ext-daystrip', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			if ( ! $this->is_using_compatible_view_version() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			wp_enqueue_style( 'tribe-ext-daystrip', plugin_dir_url( __FILE__ ) . 'src/resources/style.css' );
			add_filter( 'tribe_the_day_link', [ $this, 'filter_day_link' ] );
			add_action( 'tribe_template_after_include:events/day/top-bar/datepicker', [ $this, 'daystrip' ], 10, 3 );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '7.0';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
					$message = '<p>';
					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.',
					                         'tribe-ext-daystrip' ),
					                     $this->get_name(),
					                     $php_required_version );
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'tribe-ext-daystrip-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Check if we have the required TEC view. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function is_using_compatible_view_version() {
			$view_required_version = 2;

			$meets_req = true;

			// Is V2 enabled?
			if ( function_exists( 'tribe_events_views_v2_is_enabled' ) && ! empty( tribe_events_views_v2_is_enabled() ) ) {
				$is_v2 = true;
			} else {
				$is_v2 = false;
			}

			// V1 compatibility check.
			if ( 1 === $view_required_version && $is_v2 ) {
				$meets_req = false;
			}

			// V2 compatibility check.
			if ( 2 === $view_required_version && ! $is_v2 ) {
				$meets_req = false;
			}

			// Notice, if should be shown.
			if ( ! $meets_req && is_admin() && current_user_can( 'activate_plugins' ) ) {
				if ( 1 === $view_required_version ) {
					$view_name = _x( 'Legacy Views', 'name of view', 'tribe-ext-daystrip' );
				} else {
					$view_name = _x( 'New (V2) Views', 'name of view', 'tribe-ext-daystrip' );
				}

				$view_name = sprintf( '<a href="%s">%s</a>',
				                      esc_url( admin_url( 'edit.php?page=tribe-common&tab=display&post_type=tribe_events' ) ),
				                      $view_name );

				// Translators: 1: Extension plugin name, 2: Name of required view, linked to Display tab.
				$message = sprintf( __( '%1$s requires the "%2$s" so this extension\'s code will not run until this requirement is met. You may want to deactivate this extension or visit its homepage to see if there are any updates available.',
				                        'tribe-ext-daystrip' ),
				                    $this->get_name(),
				                    $view_name );

				tribe_notice( 'tribe-ext-daystrip-view-mismatch',
				              '<p>' . $message . '</p>',
				              [ 'type' => 'error' ] );
			}

			return $meets_req;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix( __NAMESPACE__ . '\\',
				                                      __DIR__ . DIRECTORY_SEPARATOR . 'src' );
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * Compiles the data for the daystrip
		 *
		 * @param $file
		 * @param $name
		 * @param $template
		 */
		public function daystrip( $file, $name, $template ) {
			$options = $this->get_all_options();

			// Some default values
			$args = [
				'days_to_show'        => 9,
				'day_name_length'     => 2,
				'full_width'          => '',
				'todays_date'         => $template->get( 'today' ),
				'selected_date_value' => '',
				'starting_date'       => '',
				'days'                => [],
				'event_dates'         => [],
				'dayWidth'            => '',
				'container_classes'   => [
					'tribe-daystrip-container',
					'tribe-common-b2',
				],
				'day_classes'         => [],
				'options'             => $options,
			];

			$args['days_to_show'] = (int) $options['number_of_days'];
			// If out of range, then set to default.
			if ( $args['days_to_show'] < 3 || $args['days_to_show'] > 31 ) {
				$args['days_to_show'] = 9;
			}

			$args['day_name_length'] = (int) $options['length_of_day_name'];

			// If full width, add the necessary CSS class
			if ( $options['full_width'] ) {
				$args['container_classes'][] = 'full-width';
			}

			// Check the selected date, or today if nothing selected
			$args['selected_date_value'] = $template->get( [ 'bar', 'date' ], $args['todays_date'] );
			if ( empty( $args['selected_date_value'] ) ) {
				$args['selected_date_value'] = $args['todays_date'];
			}

			// Fixed time range from today
			if ( $options['functionality'] == 'fixed_from_today' ) {
				$args['starting_date'] = $args['todays_date'];
			}
			// Fixed time range from set date
			elseif ( $options['functionality'] == 'fixed_from_date' ) {
				$sd = explode( '-', $options['start_date'] );
				if ( checkdate( $sd[1], $sd[2], $sd[0] ) ) {
					$args['starting_date'] = $options['start_date'];
				}
				else {
					$args['starting_date'] = $args['todays_date'];
				}
			}
			// Only show forward
			elseif ( $options['functionality'] == 'forward' ) {
				$args['starting_date'] = $args['selected_date_value'];
			}
			// Default, selected day in the middle
			else {
				// Choosing the starting date for the array and formatting it
				$args['starting_date'] = date( 'Y-m-d',
				                               strtotime( $args['selected_date_value'] . ' -' . intdiv( $args['days_to_show'],
				                                                                                        2 ) . ' days' ) );
			}

			// Creating and filling the array of days that we show
			$args['days'] = [];
			for ( $i = 0; $i < $args['days_to_show']; $i++ ) {
				$args['days'][] = date( 'Y-m-d', strtotime( $args['starting_date'] . ' +' . $i . ' days' ) );
			}

			// Dates on which we have events
			// The end date is excluded, so we need to add one day to the end
			$args['event_dates'] = $this->get_events_for_timeframe(
				$args['days'][0],
				date( 'Y-m-d', strtotime( end( $args['days'] ) . '+1 day' ) )
			);

			// Setting up the width for the boxes
			$args['dayWidth'] = 100 / count( $args['days'] );

			$this->render_daystrip( $args );
		}

		/**
		 * Filters the URL to make it work with AJAX loading
		 *
		 * @param $html
		 *
		 * @return string|string[]
		 */
		function filter_day_link( $html ) {
			$html = str_replace( 'rel="prev"', 'data-js="tribe-events-view-link"', $html );

			return $html;
		}

		/**
		 * Get the dates of events in the timeframe shown on the daystrip
		 *
		 * @param $start_date
		 * @param $end_date
		 *
		 * @return mixed
		 */
		function get_events_for_timeframe( $start_date, $end_date ) {
			$args   = [
				'start_date'   => $start_date,
				'end_date'     => $end_date,
				'posts_per_page'  => -1,
			];
			$dates =  [];

			// This only brings 'Post per page' number of events
			$events = tribe_get_events( $args );

			foreach ( $events as $event ) {
				$d = date( 'Y-m-d', strtotime( $event->event_date ) );
				if ( ! in_array( $d, $dates ) ) {
					$dates[] = $d;
				}
			}

			return $dates;
		}

		/**
		 * Rendering the daystrip markup
		 *
		 * @param array $args
		 */
		private function render_daystrip( array $args ) {

			// Opening the strip
			$html = '<div class="' . implode( " ", $args['container_classes'] ) . '">';

			// Going through the array and setting up the strip
			foreach ( $args['days'] as $day ) {
				// Making a date object
				$date = date_create( $day );

				unset( $args['day_classes'] );
				$args['day_classes'][] = 'tribe-daystrip-day';
				// Setting class for past, today, and future events
				if ( strtotime( $day ) < strtotime( $args['todays_date'] ) ) {
					$args['day_classes'][] = 'tribe-daystrip-past';
				} elseif ( strtotime( $day ) == strtotime( $args['todays_date'] ) ) {
					$args['day_classes'][] = 'tribe-daystrip-today';
				} elseif ( strtotime( $day ) > strtotime( $args['todays_date'] ) ) {
					$args['day_classes'][] = 'tribe-daystrip-future';
				}
				// Setting class for selected day
				if ( strtotime( $day ) == strtotime( $args['selected_date_value'] ) ) {
					$args['day_classes'][] = 'current';
				}

				if ( in_array( $day, $args['event_dates'] ) ) {
					$args['day_classes'][] = 'has-event';
				}

				// Opening the day
				$html .= '<div class="' . implode( " ",
				                                   $args['day_classes'] ) . '" style="width:' . $args['dayWidth'] . '%;">';

				// URL
				$html .= '<a href="' . tribe_events_get_url() . $day . '" data-js="tribe-events-view-link" aria-label="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '" title="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '">';

				// Text part of the URL
				// Name of day
				$html .= '<span class="tribe-daystrip-dayname">';
				if ( $args['day_name_length'] == -1 ) {
					$html .= date_format( $date, 'l' );
				}
				else {
					$html .= substr( date_format( $date, 'l' ), 0, $args['day_name_length'] );
				}
				$html .= '</span>';

				// Date of day
				if ( ! $args['options']['hide_date'] ) {
					$html .= '<span class="tribe-daystrip-date">';
					$html .= date_format( $date, 'd' );
					$html .= '</span>';
				}

				// Day has event marker
				if ( ! $args['options']['hide_event_marker'] ) {
					if ( in_array( $day, $args['event_dates'] ) ) {
						$html .= '<em
								class="tribe-events-calendar-day__daystrip-events-icon--event"
								aria-label="Has event" title="Has event"></em>';
					}
				}

				// Closing the URL
				$html .= '</a>';

				// Closing the day
				$html .= '</div>';
			}

			// Closing the strip
			$html .= '</div>';

			// Rendering the HTML
			echo $html;
		}
	} // end class
} // end if class_exists check
