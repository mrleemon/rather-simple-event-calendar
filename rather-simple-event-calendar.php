<?php
/*
Plugin Name: Rather Simple Event Calendar
Plugin URI: 
Description: A really simple event calendar
Version: 1.0
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code
*/

class RatherSimpleEventCalendar {  

    var $plugin_url;  
  
    /**
     * Plugin instance.
     *
     * @since 1.0
     *
     */
    protected static $instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @since 1.0
     *
     */
    public static function get_instance() {
        
        if ( !self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }
    
    /**
     * Used for regular plugin work.
     *
     * @since 1.0
     *
     */
    public function plugin_setup() {

        $this->plugin_url = plugin_dir_url( __FILE__ );  

          $this->includes();

        add_action( 'init', array( $this, 'load_language' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );  
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_scripts' ) );  
          
        add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );  
        add_action( 'save_post', array( $this, 'save_data' ) );  

        add_action( 'wp_ajax_rsec-fullcal', array( $this, 'fullcalendar' ) ); 
        add_action( 'wp_ajax_nopriv_rsec-fullcal', array( $this, 'fullcalendar' ) ); 

        add_filter( 'query_vars', array( $this, 'query_vars' ) );
        add_action( 'parse_request', array( $this, 'parse_request' ) );

        add_shortcode( 'fullcalendar', array( $this, 'shortcode' ) );
    
    }
    
    /**
     * Constructor. Intentionally left empty and public.
     *
     * @since 1.0
     *
     */
    public function __construct() {}
    
    /**
     * Includes required core files used in admin and on the frontend.
     *
     * @since 1.0
     *
     */
    protected function includes() {
        require_once( 'include/ical.php' );
    }

    /**
     * Loads language
     *
     * @since 1.0
     *
     */
    function load_language() {
        load_plugin_textdomain( 'rsec', '', plugin_basename( dirname( __FILE__ ) . '/languages/' ) );
    }

    /* Function: admin_scripts
     *
     * @since 1.0
     *
     */
    function admin_scripts( $hook ){
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            $screen = get_current_screen();
            if ( is_object( $screen ) && 'post' == $screen->post_type ) {
                wp_enqueue_style( 'jqueryui-css', $this->plugin_url . 'assets/css/jquery-ui.min.css' );
                wp_enqueue_style( 'datepicker-css', $this->plugin_url . 'assets/css/datepicker.css', array( 'jqueryui-css' ) );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_script( 'events-backend', $this->plugin_url . 'assets/js/backend.js', array ( 'jquery-ui-datepicker' ), null, true );
            }
        }
    }  

    /* Function: wp_scripts
     *
     * @since 1.0
     *
     */
    function wp_scripts(){  
        global $wp_locale;

        // Load styles
        wp_enqueue_style( 'fullcalendar-style', $this->plugin_url . 'assets/css/fullcalendar/main.min.css' );  
        wp_enqueue_style( 'events-css', $this->plugin_url . 'style.css' );  
        
        // Load scripts
        wp_enqueue_script( 'fullcalendar-script', $this->plugin_url . 'assets/js/fullcalendar/main.min.js', array(), null, true );
        wp_enqueue_script( 'fullcalendar-es', $this->plugin_url . 'assets/js/fullcalendar/es.js', array(), null, true );
        wp_enqueue_script( 'fullcalendar-ca', $this->plugin_url . 'assets/js/fullcalendar/ca.js', array(), null, true );
        wp_enqueue_script( 'rsec-frontend', $this->plugin_url . 'assets/js/frontend.js', array ( 'fullcalendar-script' ), null, true );
         
        wp_localize_script( 
            'rsec-frontend', 
            'RSECAjax', 
            array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'locale' => array(
                    'lang'  => substr( get_locale(), 0, 2 ),
                )    
            )
        );

        
    }  

    /* Function: meta_box
     *
     * @since 1.0
     *
     */
    function meta_box() {  
        add_meta_box(  
             'rsec-events',
             __( 'Event Data', 'rsec' ),  
             array( $this, 'meta_box_content' ),  
             'post',  
             'normal',  
             'high'  
        );  
    }  
     
    /* Function: meta_box_content
     *
     * @since 1.0
     *
     */
    function meta_box_content() {  
        global $post;  
        // Use nonce for verification  
        wp_nonce_field( plugin_basename( __FILE__ ), 'rsec_events_nonce' );  
  
        // The actual fields for data entry  
        echo '<table class="form-table">
                <tr>';

        $event_startdate_field = get_post_meta( $post->ID, 'event-startdate', true );
        if ( !empty( $event_startdate_field ) ) {
            $event_startdate_field = date( 'd-m-Y', strtotime( $event_startdate_field ) );
        }
        
        $event_enddate_field = get_post_meta( $post->ID, 'event-enddate', true );
        if ( !empty( $event_enddate_field ) ) {
            $event_enddate_field = date( 'd-m-Y', strtotime( $event_enddate_field ) );
        }
                    
        echo '<th scope="row"><label for="event-startdate-field">' . __( 'Start Date/Time', 'rsec' ) . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="event-startdate-field" name="event-startdate-field" value="' . $event_startdate_field . '" maxlength="10" /></td>';
        
        echo '</tr><tr>';

        echo '<th scope="row"><label for="event-enddate-field">' . __( 'End Date/Time', 'rsec' ) . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="event-enddate-field" name="event-enddate-field" value="' . $event_enddate_field . '" maxlength="10" /></td>';
        
        echo '</tr>
            </table>';

    } 

    /* Function: save_data
     *
     * @since 1.0
     *
     */
    function save_data( $post_id ) {  
        // Verify nonce
        if ( !isset( $_POST['rsec_events_nonce'] ) || !wp_verify_nonce( $_POST['rsec_events_nonce'], plugin_basename( __FILE__ ) ) ) {
            return $post_id;
        }

        // Is autosave?
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
    
        // Check permissions
        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) ) { 
                return $post_id; 
            }
        } else {  
            if ( !current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }  
  
        $event_startdate_data = $_POST['event-startdate-field'];  
        $event_enddate_data = $_POST['event-enddate-field'];  
  
        if ( !empty( $event_startdate_data ) && empty( $event_enddate_data ) ) {
            $event_startdate_data = date( 'Y-m-d', strtotime( $event_startdate_data ) );  
            update_post_meta( $post_id, 'event-startdate', $event_startdate_data, get_post_meta( $post_id, 'event-startdate', true ) );  
            update_post_meta( $post_id, 'event-enddate', $event_startdate_data, get_post_meta( $post_id, 'event-enddate', true ) );  
        } elseif ( !empty( $event_startdate_data ) && !empty( $event_enddate_data ) ) {
            $event_startdate_data = date( 'Y-m-d', strtotime( $event_startdate_data ) );  
            $event_enddate_data = date( 'Y-m-d', strtotime( $event_enddate_data ) );  
            update_post_meta( $post_id, 'event-startdate', $event_startdate_data, get_post_meta( $post_id, 'event-startdate', true ) );  
            update_post_meta( $post_id, 'event-enddate', $event_enddate_data, get_post_meta( $post_id, 'event-enddate', true ) );
        } else {
            delete_post_meta( $post_id, 'event-startdate' );  
            delete_post_meta( $post_id, 'event-enddate' );
        }
  
    }
     
    /* Function: shortcode
     *
     * @since 1.0
     *
     */
    function shortcode( $attr ) {
        $html = $this->show_calendar( $attr );
        return $html;
    }

    /* Function: show_calendar
     *
     * @since 1.0
     *
     */
    function show_calendar( $attr ) {
    
        $atts = shortcode_atts( array(
            'firstDay' => intval( get_option( 'start_of_week' ) )
        ), $attr, 'fullcalendar' );

        $includes = includes_url();
        
        $html = '<div id="fullcalendar"></div>';
        $html .= '<div id="fullcalendar_loading" style="display:none; text-align: center;" >';
        $html .= '<img src="' . $includes . 'images/spinner.gif' . '" style="vertical-align:middle; padding: 0px 5px 5px 0px;" />' . __( 'Loading&#8230;', 'rsec' ) . '</div>';

        $url = urlencode( trailingslashit( str_replace( 'https://', 'webcal://', get_bloginfo( 'wpurl' ) ) ) . '?rsec_export=yes' );
        $gcal = 'http://www.google.com/calendar/render?cid=' . $url;
        $ical = trailingslashit( get_bloginfo( 'wpurl' ) ) . '?rsec_export=yes';
        $html .= '<div id="calendar-footer"><a target="_blank" href="' . $gcal . '">' . __( 'Export to Google Calendar', 'rsec' ) . '</a> | <a target="_blank" href="' . $ical . '">' . __( 'Download iCal file', 'rsec' ) . '</a></div>';
        
        return $html;
    }

    /* Function: fullcalendar
     *
     * @since 1.0
     *
     */
    function fullcalendar() {
        global $post;

        $request = array(
            'event_start_before' => $_GET['end'],
            'event_end_after' => $_GET['start']
        );
        $presets = array();

        // Retrieve events        
        $query = array_merge( $request, $presets );
        $events = $this->get_events( $query );
        $eventsarray = array();

        // Loop through events
        if ( $events ) { 
            foreach ( $events as $post ) {
                $event = array();

                // Title and url
                $event['title'] = html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, 'UTF-8' );
                $event['url'] = esc_js(get_permalink( $post->ID ) );

                $startdate = get_post_meta( $post->ID, 'event-startdate', true );
                $enddate = get_post_meta( $post->ID, 'event-enddate', true );
                $starttime = get_post_meta( $post->ID, 'event-starttime', true );
                $endtime = get_post_meta( $post->ID, 'event-endtime', true );
        
                // Get Event Start and End date, set timezone to the blog's timezone
                $event_start = new DateTime( $startdate . ' ' . $starttime, new DateTimeZone( 'Europe/Madrid' ) );
                $event_end = new DateTime( $enddate . ' ' . $endtime, new DateTimeZone( 'Europe/Madrid' ) );
                $event_end = $event_end->add(new DateInterval( 'P1D' ) ); // add one day
                $event['start']= $event_start->format( 'Y-m-d\TH:i:s\Z' );
                $event['end']= $event_end->format( 'Y-m-d\TH:i:s\Z' );    

                // Colour events
                $now = new DateTime( null, new DateTimeZone( 'Europe/Madrid' ) );
                if ( $event_start <= $now ) {
                    $event['color'] = '#372b2b';
                } else {
                    $event['color'] = '#a0a3a3';
                }
                  
                // Add event to array
                $eventsarray[] = $event;
            }
        }

        // Echo result and exit
        echo json_encode( $eventsarray );
        exit;
    }

    /* Function: get_events
     *
     * @since 1.0
     *
     */
    function get_events( $args = array() ) {

        // In case an empty string is passed
        if ( empty( $args ) ) {
            $args = array();
        }

        // These are preset to ensure the plugin functions properly
        $required = array(
            'post_type'   => 'post',
            'post_status' => 'publish',
            'suppress_filters' => 0
        );

        $now = date( 'Y-m-d' );
       
        // These are the defaults
        $defaults = array(
            'numberposts' => -1,
            'meta_key'    => 'event-startdate',
            'orderby'     => 'meta_value',
            'order'       => 'asc',
            'meta_query'  => array(
                array(
                    'key'     => 'event-enddate',
                    'value'   => $now,
                    'type'    => 'date',
                    'compare' => '>='
                )
            ),
        );
    
        // Construct the query array    
        $query_array = array_merge( $defaults, $args, $required );

        if ( $query_array ) {
            $events = get_posts( $query_array );
            return $events;
        }

        return false;
  
    }

    /* Function: query_vars
     *
     * @since 1.0
     *
     */
    function query_vars( $vars ) {
        array_push( $vars, 'rsec_export' );
        return $vars;
    }

    /* Function: parse_request
     *
     * @since 1.0
     *
     */
    function parse_request( $query ) {
        if ( !empty( $query->query_vars['rsec_export'] ) ) {
            $this->generate_ics();
            exit();
        }
    }

    /* Function: generate_ics
     *
     * @since 1.0
     *
     */
    function generate_ics() {
        global $post;

        $calendar_name = get_option( 'blogname' );
        $calendar_description = sprintf(__( '%s Calendar', 'rsec' ), $calendar_name );
        $timezone = get_option( 'timezone_string' );

        $ics = ical_header( $calendar_name, $calendar_description, $timezone );
        
        $events = $this->get_events( $query );
        $eventsarray = array();

        //Loop through events
        if ( $events ) { 
            foreach ( $events as $post ) {
                $created = new DateTime( $post->post_date_gmt );
                $created_date = $created->format( 'Ymd\THis\Z' );

                $modified = new DateTime( $post->post_modified_gmt );
                $modified_date = $modified->format( 'Ymd\THis\Z' );

                $event_start = new DateTime(get_post_meta( $post->ID, 'event-startdate', true ) );
                $event_end = new DateTime(get_post_meta( $post->ID, 'event-enddate', true ) );

                //Set up start and end date times
                if ( $event_start == $event_end ) {
                    $start_date = $event_start->format( 'Ymd' );
                    $end_date = $event_end->format( 'Ymd' );                
                } else {
                    $start_date = $event_start->format( 'Ymd' );
                    $event_end->modify( '+1 day' );
                    $end_date = $event_end->format( 'Ymd' );
                }
                      
                $url = get_permalink( $post->ID );
                $summary = $post->post_title;
                $description = mb_strimwidth( strip_tags( $post->post_content ), 0, 200, '...' ). ' ' . $url;
                
                $ics .= ical_event( $post->ID, $created_date, $modified_date, $start_date, $end_date, $summary, $description, $url );
            }
        }

        $ics .= ical_footer();
            
        header( 'Content-type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=calendar.ics' );
        header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); //date in the past
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); //tell it we just updated
        header( 'Cache-Control: no-store, no-cache, must-revalidate' ); //force revalidation
        header( 'Cache-Control: post-check=0, pre-check=0', false );
        header( 'Pragma: no-cache' ); 

        echo $ics;
    }

}

add_action( 'plugins_loaded', array ( RatherSimpleEventCalendar::get_instance(), 'plugin_setup' ) );
