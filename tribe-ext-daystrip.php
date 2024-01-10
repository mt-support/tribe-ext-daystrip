<?php
/**
 * Plugin Name:       The Events Calendar Extension: Day Strip
 * Plugin URI:        https://theeventscalendar.com/extensions/daystrip/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-daystrip
 * Description:       Adds a day-by-day navigation strip at the top of the Day View.
 * Version:           2.0.0
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

// We intentionally want to autoload here.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

// We intentionally want to autoload here, too.
if ( class_exists( Main::class ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Main extends Tribe__Extension {

	/**
	 * The minimum PHP version required to run this extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $php_required_version = '7.4';

	/**
	 * The TEC autoloader instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Tribe__Autoloader
	 */
	private $class_loader;

	/**
	 * The extension settings instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '6.0' );
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

		// Don't run on legacy views
		if (
			! function_exists( 'tribe_events_views_v2_is_enabled' )
			|| empty( tribe_events_views_v2_is_enabled() )
		) {
			return;
		}

		$this->class_loader();

		$this->get_settings();


		add_filter( 'tribe_the_day_link', [ $this, 'filter_day_link' ] );
		add_action( 'tribe_template_after_include:events/v2/day/top-bar/datepicker', [ $this, 'daystrip' ], 10, 3 );
		add_action('wp_enqueue_scripts', [ $this, 'enqueue_daystrip_styles'] );
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
		if ( version_compare( PHP_VERSION, $this->php_required_version, '<' ) ) {
			$this->send_php_version_notice();

			return false;
		}

		return true;
	}

	private function send_php_version_notice() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			$message = '<p>';
			$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.',
										'tribe-ext-daystrip' ),
									$this->get_name(),
									$this->php_required_version );
			$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
			$message .= '</p>';
			tribe_notice( 'tribe-ext-daystrip-php-version', $message, [ 'type' => 'error' ] );
		}
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
			$this->class_loader->register_prefix( __NAMESPACE__ . '\\', __DIR__ . DIRECTORY_SEPARATOR . 'src' );
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
	 * Enqueuing stylesheet.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Fix typo in method name.
	 */
	public function enquque_daystrip_styles() {
		_deprecated_function( __METHOD__, '2.0.0', 'enqueue_daystrip_styles' );

		return $this->enqueue_daystrip_styles();
	}

	/**
	 * Enqueuing stylesheet.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_daystrip_styles() {
		wp_enqueue_style(
			'tribe-ext-daystrip',
			plugin_dir_url( __FILE__ ) . 'src/resources/style.css'
		);
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
			'behavior'            => 'default',
			'container_classes'   => [
				'tribe-daystrip-container',
				'tribe-common-b2',
			],
			'date_format'         => 'j',
			'day_classes'         => [],
			'days'                => [],
			'event_dates'         => [],
			'full_width'          => false,
			'hide_event_marker'   => false,
			'length_of_day_name'  => 2,
			'month_format'        => 'M',
			'number_of_days'      => 9,
			'selected_date_value' => '',
			'start_date'          => '',
			'todays_date'         => $template->get( 'today' ),
		];

		// Some but not all are covered by the options. Let's merge them.
		$args = shortcode_atts( $args, $options );

		// If out of range set to default.
		if ( (int) $args['number_of_days'] < 3 || (int) $args['number_of_days'] > 31 ) {
			$args['number_of_days'] = 9;
		} else {
			$args['number_of_days'] = (int) $args['number_of_days'];
		}

		// If full width add the necessary CSS class.
		if ( (bool) $args['full_width'] ) {
			$args['container_classes'][] = 'full-width';
		}

		// Check the selected date, or today if nothing selected.
		$args['selected_date_value'] = $template->get( [ 'bar', 'date' ], $args['todays_date'] );

		if ( empty( $args['selected_date_value'] ) ) {
			$args['selected_date_value'] = 'today';
		}

		switch( $args['behavior'] ) {
			case 'fixed_from_today':
				// Fixed time range from today.
				$args['start_date'] = $args['todays_date'];

				break;
			case 'fixed_from_date':
				// Fixed time range from set date.
				$sd = explode( '-', $options['start_date'] );

				if ( checkdate( $sd[1], $sd[2], $sd[0] ) ) {
					$args['start_date'] = $options['start_date'];
				} else {
					$args['start_date'] = $args['todays_date'];
				}

				break;
			case 'forward':
				// Only show forward.
				$args['start_date'] = $args['selected_date_value'];

				break;
			case 'current_week':
				// Current week.
				$args['start_date'] = date('Y-m-d', strtotime('this week' . $this->adjust_week_start() ) );
				$args['number_of_days'] = 7;

				break;
			case 'next_week':
				/**
				 * Next week.
				 *
				 * @TODO Needs fixing
				 */
				$args['start_date'] = date('Y-m-d', strtotime('next week' . $this->adjust_week_start() ) );
				$args['number_of_days'] = 7;

				break;
			default:
				$args['start_date'] = date(
					'Y-m-d',
					strtotime(
						$args['selected_date_value'] . ' - ' . intdiv( $args['number_of_days'], 2 ) . ' days'
					)
				);

				break;
		}

		// Creating and filling the array of days that we show
		for ( $i = 0; $i < (int) $args['number_of_days']; $i++ ) {
			$args['days'][] = date( 'Y-m-d', strtotime( $args['start_date'] . ' +' . $i . ' days' ) );
		}

		// Dates on which we have events.
		// The end date is excluded, so we need to add one day to the end.
		$args['event_dates'] = $this->get_events_for_timeframe(
			$args['days'][0],
			date( 'Y-m-d', strtotime( end( $args['days'] ) . '+1 day' ) )
		);

		$this->render_daystrip( $args );
	}

	/**
	 * Filters the URL to make it work with AJAX loading.
	 *
	 * @since 1.0.0
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
	 * Get the dates of events in the timeframe shown on the day strip.
	 *
	 * @since 1.0.0
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
	 * Adjust the start of the week based on the WordPress setting.
	 *
	 * @since 1.0.0
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
			$str = " +" . $first_day_of_week - 1 . " days";
		}
		return $str;
	}

	/**
	 * Makes the day view jump to a specific date.
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
	 */
	public function footer_styles() {
		$divider  = $this->get_option( 'number_of_days', 9 );
		$behavior = $this->get_option( 'behavior', 'default' );

		// Force divider to 7 if behavior is current or next week.
		if ( $behavior == 'current_week' || $behavior == 'next_week' ) {
			$divider = 7;
		}

		$cell_width = 100 / absint( $divider );

		sprintf(
			'%1$s .tribe-events-header .tribe-daystrip-container .tribe-daystrip-day { width: %2$d%%; } %3$s',
			'<style id="tribe-ext-daystrip-styles">',
			$cell_width,
			'</style>'
		);
	}

	/**
	 * Rendering the daystrip markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 */
	private function render_daystrip( array $args ) {
		// Opening the strip.
		$html = '<div class="' . implode( " ", $args['container_classes'] ) . '">';

		// Going through the array and setting up the strip.
		foreach ( $args['days'] as $day ) {
			// Making a date object
			$date = date_create( $day );

			$day_classes = [];
			$day_classes[] = 'tribe-daystrip-day';

			// Setting class for past, today, and future events
			if ( strtotime( $day ) < strtotime( $args['todays_date'] ) ) {
				$day_classes[] = 'tribe-daystrip-past';
			} elseif ( strtotime( $day ) == strtotime( $args['todays_date'] ) ) {
				$day_classes[] = 'tribe-daystrip-today';
			} elseif ( strtotime( $day ) > strtotime( $args['todays_date'] ) ) {
				$day_classes[] = 'tribe-daystrip-future';
			}
			// Setting class for selected day.
			if ( strtotime( $day ) == strtotime( $args['selected_date_value'] ) ) {
				$day_classes[] = 'tribe-daystrip-current';
			}

			if ( in_array( $day, $args['event_dates'] ) ) {
				$day_classes[] = 'has-event';
			}

			$day_classes = implode( ' ', $day_classes );

			// Opening the day
			$html .= '<div class="' . esc_attr( $day_classes ) . '">';

			// URL
			$html .= '<a href="' . tribe_events_get_url() . $day . '" data-js="tribe-events-view-link" aria-label="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '" title="' . date( tribe_get_date_format( true ), strtotime( $day ) ) . '">';

			// Text part of the URL
			$html .= '<span class="tribe-daystrip-dayname">';

			// Name of day.
			if ( (int) $args['length_of_day_name'] < 0 ) {
				$html .= $date->format( 'l' );
			} else {
				$html .= substr( $date->format( 'l' ), 0, (int) $args['length_of_day_name'] );
			}

			$html .= '</span>';

			// Date of day
			if ( ! empty( $args['date_format'] ) ) {
				$html .= '<span class="tribe-daystrip-date">';
				$html .= $date->format( $args['date_format'] );
				$html .= '</span>';
			}

			if ( ! empty( $args['month_format'] ) ) {
				$html .= '<span class="tribe-daystrip-month">';
				$html .= $date->format( $args['month_format'] );
				$html .= '</span>';
			}

			// Day has event marker
			if ( ! (bool) $args['hide_event_marker'] ) {
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
