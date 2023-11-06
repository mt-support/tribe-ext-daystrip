<?php
/**
 * Plugin Name:       The Events Calendar Extension: Day Strip
 * Plugin URI:        https://theeventscalendar.com/extensions/daystrip/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-daystrip
 * Description:       Adds a day-by-day navigation strip at the top of the Day View.
 * Version:           1.0.1
 * Extension Class:   Tribe\Extensions\Daystrip\Main
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
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
		 * Set up the Extension's properties.
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
			load_plugin_textdomain( 'tribe-ext-daystrip', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			if ( ! $this->is_using_compatible_view_version() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();


			add_filter( 'tribe_the_day_link', [ $this, 'filter_day_link' ] );
			add_action( 'tribe_template_after_include:events/v2/day/top-bar/datepicker', [ $this, 'daystrip' ], 10, 3 );
			add_action('wp_enqueue_scripts', [ $this, 'enqueue_daystrip_styles' ] );
			add_action( 'wp_footer', [ $this, 'footer_styles' ] );

			/**
			 * @TODO Leaving here for a later version
			 */
			//add_filter( 'tribe_events_views_v2_view_repository_args', [ $this, 'jump_to_next_week' ], 10, 3 );
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

			// Show notice, if it should be shown.
			if ( ! $meets_req && is_admin() && current_user_can( 'activate_plugins' ) ) {
				if ( 1 === $view_required_version ) {
					$view_name = _x( 'Legacy Views', 'name of view', 'tribe-ext-daystrip' );
				} else {
					$view_name = _x( 'Updated (V2) Views', 'name of view', 'tribe-ext-daystrip' );
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
		 * Get a specific extension option.
		 *
		 * @param $option
		 * @param string $default
		 *
		 * @return array
		 */
		public function get_option( $option, $default ='' ) {
			$settings = $this->get_settings();

			return $settings->get_option( $option, $default );
		}

		/**
		 * Enqueuing stylesheet
		 */
		public function enqueue_daystrip_styles() {
			wp_enqueue_style( 'tribe-ext-daystrip', plugin_dir_url( __FILE__ ) . 'src/resources/style.css' );
		}

		/**
		 * Compiles the data for the daystrip.
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
				'behavior'            => '',
				'full_width'          => '',
				'todays_date'         => $template->get( 'today' ),
				'selected_date_value' => '',
				'starting_date'       => '',
				'days'                => [],
				'event_dates'         => [],
				'container_classes'   => [
					'tribe-daystrip-container',
					'tribe-common-b2',
				],
				'day_classes'         => [],
				'options'             => $options,
			];

			if ( isset( $options['number_of_days'] ) ) {
				$args['days_to_show'] = (int) $options['number_of_days'];
			}

			// If out of range, then set to default
			if ( $args['days_to_show'] < 3 || $args['days_to_show'] > 31 ) {
				$args['days_to_show'] = 9;
			}

			if ( isset( $options['length_of_day_name'] ) ) {
				$args['day_name_length'] = (int) $options['length_of_day_name'];
			}

			// If full width, add the necessary CSS class
			if ( isset( $options['full_width'] ) && $options['full_width'] ) {
				$args['container_classes'][] = 'full-width';
			}

			// Check the selected date, or today if nothing selected
			$args['selected_date_value'] = $template->get( [ 'bar', 'date' ], $args['todays_date'] );
			if ( empty( $args['selected_date_value'] ) ) {
				$args['selected_date_value'] = $args['todays_date'];
			}

			// Fixed time range from today
			if ( isset( $options['behavior'] ) ) {
				if ( $options['behavior'] == 'fixed_from_today' ) {
					$args['starting_date'] = $args['todays_date'];
				} // Fixed time range from set date
				elseif ( $options['behavior'] == 'fixed_from_date' ) {
					$start_date = $options['start_date'] ?? $args['todays_date'];
					$sd         = explode( '-', $start_date );
					if ( checkdate( $sd[1], $sd[2], $sd[0] ) ) {
						$args['starting_date'] = $start_date;
					} else {
						$args['starting_date'] = $args['todays_date'];
					}
				} // Only show forward
				elseif ( $options['behavior'] == 'forward' ) {
					$args['starting_date'] = $args['selected_date_value'];
				} // Current week
				elseif ( $options['behavior'] == 'current_week' ) {
					$args['starting_date'] = date( 'Y-m-d', strtotime( 'this week' . $this->adjust_week_start() ) );
					$args['days_to_show']  = 7;
				} /**
				 * Next week
				 *
				 * @TODO Needs fixing
				 */
				elseif ( $options['behavior'] == 'next_week' ) {
					$args['starting_date'] = date( 'Y-m-d', strtotime( 'next week' . $this->adjust_week_start() ) );
					$args['days_to_show']  = 7;
				} // Default, selected day in the middle
			} else {
				// Choosing the starting date for the array and formatting it
				$args['starting_date'] = date( 'Y-m-d',
					strtotime( $args['selected_date_value'] . ' -' . intdiv( $args['days_to_show'],
							2 ) . ' days' ) );
			}

			// Creating and filling up the array of days that we show
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

			$this->render_daystrip( $args );
		}

		/**
		 * Filters the URL to make it work with AJAX loading.
		 *
		 * @param $html
		 *
		 * @return string|string[]
		 */
		function filter_day_link( $html ) {
			return str_replace( 'rel="prev"', 'data-js="tribe-events-view-link"', $html );
		}

		/**
		 * Get the dates of events in the timeframe shown on the day strip.
		 *
		 * @param $start_date
		 * @param $end_date
		 *
		 * @return array The events starting within the given timeframe.
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
				$d = tribe_get_start_date( $event->ID, false, 'Y-m-d' );
				if ( ! in_array( $d, $dates ) ) {
					$dates[] = $d;
				}
			}

			return $dates;
		}

		/**
		 * Adjust the start of the week based on the WordPress setting.
		 *
		 * @return string
		 */
		public function adjust_week_start() {
			$first_day_of_week = get_option( 'start_of_week', 1 );
			$str = '';
			// If it's Sunday (0)
			if ( $first_day_of_week == 0 ) {
				$str = ' -1 day';
			}
			// If Tuesday (2) to Saturday (6)
			elseif( $first_day_of_week > 1 ) {
				$str = " +" . ( $first_day_of_week - 1 ) . " days";
			}
			return $str;
		}

		/**
		 * Makes the day view jump to a specific date.
		 *
		 * @TODO Needs work
		 *
		 * @param $repository_args
		 * @param $context
		 * @param $view
		 *
		 * @return mixed
		 */
		function  jump_to_next_week( $repository_args, $context, $view )  {
			$event_date = $context->get( 'event_date' );
			//if ( ! $event_date ) {
			if ( tribe_context()->get( 'view_request' ) === 'day' ) {
				$context = $context->alter( [ 'event_date' => '2020-10-19' ] );
				$view->set_context( $context );
			}

			return $repository_args;
		}

		/**
		 * Add dynamically calculated styles to the footer.
		 */
		public function footer_styles() {
			$divider = $this->get_option( 'number_of_days', 7 );
			$behavior = $this->get_option( 'behavior', 'current_week' );

			if ( $behavior == 'current_week' || $behavior == 'next_week' ) {
				$divider = 7;
			}
			$cellWidth = 100 / $divider;
			?>
			<style id="tribe-ext-daystrip-styles">
                .tribe-events-header .tribe-daystrip-container .tribe-daystrip-day {
				 width: <?php echo $cellWidth; ?>%;
			 }
			</style>
			<?php
		}

		/**
		 * Rendering the daystrip markup.
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
					$args['day_classes'][] = 'tribe-daystrip-current';
				}

				if ( in_array( $day, $args['event_dates'] ) ) {
					$args['day_classes'][] = 'has-event';
				}

				// Opening the day
				$html .= '<div class="' . implode( " ",
				                                   $args['day_classes'] ) . '">';

				// URL
				$html .= '<a href="' . tribe_events_get_url() . $day . '" data-js="tribe-events-view-link" aria-label="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '" title="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '">';

				// Text part of the URL
				// Name of day
				$html .= '<span class="tribe-daystrip-dayname">';
				if ( $args['day_name_length'] == -1 ) {
					$html .= $date->format( 'l' );
				}
				else {
					$html .= substr( $date->format( 'l' ), 0, $args['day_name_length'] );
				}
				$html .= '</span>';

				// Date of day
				$date_format = $args['options']['date_format'] ?? 'j';
				if ( $date_format != '0' ) {
					$html .= '<span class="tribe-daystrip-date">';
					$html .= $date->format( $date_format );
					$html .= '</span>';
				}
				$month_format = $args['options']['month_format'] ?? 'M';
				if ( $month_format != '0' ) {
					$html .= '<span class="tribe-daystrip-month">';
					$html .= $date->format( $month_format );
					$html .= '</span>';
				}

				// Day has event marker
				$hide_event_marker = $args['options']['hide_event_marker'] ?? false;
				if ( ! $hide_event_marker ) {
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
