<?php
/*
 * Plugin Name:       Featured Events
 * Description:       Utilizes the LibCal JSON feed to pull events based on URL parameters defined in the shortcode.
 * Version:           1.0.0
 * Author:            Brian Habib
 * Directory:         wp-content/plugins/featured-events/featured-events.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'featured_events' ) ) :

    class featured_events {

        public static function init() {
            define( 'FE_URL',  plugins_url() . '/featured-events/' );
            define( 'FE_PATH', plugin_dir_path( __FILE__ ) );

            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );

            add_shortcode( 'featured-events', array( __CLASS__, 'shortcode' ) );

            add_action( 'wp_ajax_get_featured_events',        array( __CLASS__, 'ajax_get_featured_events' ) );
            add_action( 'wp_ajax_nopriv_get_featured_events', array( __CLASS__, 'ajax_get_featured_events' ) );
        }

        public static function wp_enqueue_scripts() {
            wp_enqueue_style(
                'featured-events-style',
                FE_URL . 'assets/css/styles.css',
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'featured-events-script',
                FE_URL . 'assets/js/script.js',
                array( 'jquery' ),
                '1.0.0',
                true
            );

            wp_localize_script( 'featured-events-script', 'featuredEventsAjax', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'featured_events_nonce' ),
            ) );
        }
        public static function shortcode( $atts ) {
            $atts = shortcode_atts( array(
                'title'    => '',
                'start'    => '',
                'end'      => '',
                'type'     => '',
                'age'      => '',
                'branch'   => '',
                'quantity' => 36,
            ), $atts, 'featured-events' );

            $title    = wp_kses_post( $atts['title'] );
            $start    = sanitize_text_field( $atts['start'] );
            $end      = sanitize_text_field( $atts['end'] );
            $type     = sanitize_text_field( $atts['type'] );
            $age      = sanitize_text_field( $atts['age'] );
            $branch   = sanitize_text_field( $atts['branch'] );
            $quantity = absint( $atts['quantity'] ) ?: 36;

            $uid = 'fe-' . substr( md5( $title . $start . $end . $type . $age . $branch . $quantity ), 0, 8 );

            $first_branch = '';
            if ( ! empty( $branch ) ) {
                $parts        = explode( ',', $branch );
                $first_branch = trim( $parts[0] );
            }

            ob_start();
            ?>
            <div class="featured-branch-events--background">
                <div class="block-container--xlarge">
                    <div class="block__inner">

                        <div class="heading has-categories">
                            <h2><?php echo $title; ?></h2>
                        </div>

                        <div class="category-filters">
                            <?php if ( ! empty( $first_branch ) ) : ?>
                            <a href="https://howardcounty.librarycalendar.com/events/upcoming?branches=<?php echo esc_attr( $first_branch ); ?>"
                               class="button--secondary view-all-events"
                               target="_blank"
                               rel="noopener noreferrer">
                                <span>View Calendar</span>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div id="<?php echo esc_attr( $uid ); ?>-cards"
                             class="featured-branch-events--cards"
                             data-start="<?php echo esc_attr( $start ); ?>"
                             data-end="<?php echo esc_attr( $end ); ?>"
                             data-type="<?php echo esc_attr( $type ); ?>"
                             data-age="<?php echo esc_attr( $age ); ?>"
                             data-branch="<?php echo esc_attr( $branch ); ?>"
                             data-quantity="<?php echo esc_attr( $quantity ); ?>">

                            <div class="event-spinner">
                                <div class="lds-ring-event">
                                    <div></div><div></div><div></div><div></div>
                                </div>
                            </div>

                        </div>

                        <div id="<?php echo esc_attr( $uid ); ?>-pagination"
                             class="featured-branch-events--pagination">
                        </div>

                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        public static function ajax_get_featured_events() {

            if ( ! check_ajax_referer( 'featured_events_nonce', 'nonce', false ) ) {
                wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
            }

            $start    = isset( $_GET['start'] )    ? sanitize_text_field( $_GET['start'] )    : date( 'Y-m-d' );
            $end      = isset( $_GET['end'] )      ? sanitize_text_field( $_GET['end'] )      : '';
            $type     = isset( $_GET['type'] )     ? sanitize_text_field( $_GET['type'] )     : '';
            $age      = isset( $_GET['age'] )      ? sanitize_text_field( $_GET['age'] )      : '';
            $branch   = isset( $_GET['branch'] )   ? sanitize_text_field( $_GET['branch'] )   : '';
            $quantity = isset( $_GET['quantity'] ) ? absint( $_GET['quantity'] )               : 36;

            if ( empty( $start ) )    { $start    = date( 'Y-m-d' ); }
            if ( empty( $quantity ) ) { $quantity = 36; }

            $type_arr   = self::csv_to_int_array( $type );
            $age_arr    = self::csv_to_int_array( $age );
            $branch_arr = self::csv_to_int_array( $branch );

            $cache_key = 'fe_' . md5( $start . $end . $type . $age . $branch . $quantity );
            $cached    = get_transient( $cache_key );

            if ( $cached !== false ) {
                wp_send_json_success( $cached );
                return;
            }

            $raw = self::eventGet( $start, $end, $type_arr, $age_arr, $branch_arr, $quantity );

            if ( is_wp_error( $raw ) ) {
                wp_send_json_error( array( 'message' => $raw->get_error_message() ), 502 );
                return;
            }

            $events = json_decode( $raw, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                wp_send_json_error( array( 'message' => 'JSON decode error: ' . json_last_error_msg() ), 502 );
                return;
            }

            $results = array();

            foreach ( $events as $event ) {
                $id         = $event['id'];
                $start_date = $event['start_date'];
                $today      = date( 'Y-m-d' );

                $start_date_formatted = ( date( 'Y-m-d', strtotime( $start_date ) ) === $today )
                    ? 'Today'
                    : date( 'D, M j', strtotime( $start_date ) );

                $start_time = date( 'g:i a', strtotime( $start_date ) );
                $end_time   = date( 'g:i a', strtotime( $event['end_date'] ) );

                $branch_values   = array_values( $event['branch'] );
                $age_group_raw   = array_values( $event['age_group'] );
                $age_group_label = $age_group_raw[0] ?? '';
                $age_group_class = strtolower( $age_group_label );

                $results[ $id ] = array(
                    'title'           => $event['title'],
                    'url'             => $event['url'],
                    'start_date'      => $start_date_formatted,
                    'start_time'      => $start_time,
                    'end_time'        => $end_time,
                    'branch'          => $branch_values,
                    'program_type'    => array_values( $event['program_type'] ),
                    'age_group_class' => $age_group_class,
                    'age_group_label' => $age_group_label,
                );
            }

            $result = array_values( $results );

            if ( ! empty( $result ) ) {
                set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
            }

            wp_send_json_success( $result );
        }

        public static function eventGet( $start, $end, $type, $age, $branch, $quantity ) {
            $params = array();

            if ( ! empty( $start ) )    { $params['start']         = $start; }
            if ( ! empty( $end ) )      { $params['end']           = $end; }
            if ( ! empty( $type ) )     { $params['program_types'] = implode( ',', $type ); }
            if ( ! empty( $age ) )      { $params['age_groups']    = implode( ',', $age ); }
            if ( ! empty( $branch ) )   { $params['branches']      = implode( ',', $branch ); }
            if ( ! empty( $quantity ) ) { $params['quantity']      = $quantity; }

            $url = 'https://howardcounty.librarycalendar.com/events/feed/json'
                   . ( ! empty( $params ) ? '?' . http_build_query( $params ) : '' );

            $response = wp_remote_get( $url, array(
                'timeout' => 15,
                'headers' => array(
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                return new WP_Error( 'http_error', "LibCal returned HTTP {$code}" );
            }

            return wp_remote_retrieve_body( $response );
        }

        private static function csv_to_int_array( $csv ) {
            if ( empty( $csv ) ) {
                return array();
            }
            return array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $csv ) ) ) );
        }
    }

    featured_events::init();

endif;
