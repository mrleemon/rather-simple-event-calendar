<?php
/*
Plugin Name: Really Simple Event Calendar
Plugin URI: 
Description: A really simple event calendar
Version: 1.0
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code
*/

define('RSEC_VERSION', '1.0');

require_once('include/ical.php');

class ReallySimpleEventCalendar {  

    var $plugin_dir;  
    var $plugin_url;  
  
    function __construct() {  
  
        $this->plugin_dir = plugin_dir_path(__FILE__);  
        $this->plugin_url = plugin_dir_url(__FILE__);  

        add_action('init', array($this, 'rsec_init'));
        
        add_action('admin_enqueue_scripts', array($this, 'rsec_admin_scripts'));  
        add_action('wp_enqueue_scripts', array($this, 'rsec_wp_scripts'));  
          
        add_action('admin_head', array($this, 'rsec_call_js'));  
        add_action('add_meta_boxes', array( $this, 'rsec_meta_box' ));  
        add_action('save_post', array($this, 'rsec_save_data'));  

        add_action('wp_ajax_rsec-fullcal', array($this, 'rsec_fullcalendar')); 
	    add_action('wp_ajax_nopriv_rsec-fullcal', array($this, 'rsec_fullcalendar')); 

        add_filter('query_vars', array($this, 'rsec_query_vars') );
        add_action('parse_request', array($this, 'rsec_parse_request') );

        add_shortcode('fullcalendar', array($this, 'rsec_shortcode') );

    }  


    /* Function: rsec_init
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_init() {  
        load_plugin_textdomain('rsec', '', plugin_basename( dirname( __FILE__ ) . '/languages/'));
    }  

    /* Function: rsec_admin_scripts
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_admin_scripts(){  
        wp_register_style('datepicker-css', $this->plugin_url . 'css/jquery-ui.min.css');
        wp_enqueue_style('datepicker-css');
        
        wp_enqueue_script('jquery-ui-datepicker');
    }  
     

    /* Function: rsec_wp_scripts
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_wp_scripts(){  
        global $wp_locale;

        wp_enqueue_script('jquery');

        wp_register_script('moment-js', $this->plugin_url . 'js/moment.min.js');  
        wp_enqueue_script('moment-js');  

        wp_register_script('calendar-js', $this->plugin_url . 'js/fullcalendar.min.js');
        wp_enqueue_script('calendar-js');  

        wp_register_script('frontend-js', $this->plugin_url . 'js/frontend.js');
        wp_enqueue_script('frontend-js');
            
        wp_localize_script( 
			'frontend-js', 
			'RSECAjax', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'locale' => array(
					'monthNames' => array_values($wp_locale->month),
					'monthAbbrev' => array_values($wp_locale->month_abbrev),
					'dayNames' => array_values($wp_locale->weekday),
					'dayAbbrev' => array_values($wp_locale->weekday_abbrev),
					'today' => __('today', 'rsec'),
					'day' => __('day', 'rsec'),
					'week' => __('week', 'rsec'),
					'month' => __('month', 'rsec')
				)	
			)
		);
		
		wp_register_style('calendar-css', $this->plugin_url . 'css/fullcalendar.css');  
		wp_enqueue_style('calendar-css');  

		wp_register_style('events-css', $this->plugin_url . 'css/styles.css');  
		wp_enqueue_style('events-css');  
    }  
     

    /* Function: rsec_call_js
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_call_js(){  
		global $post;
		if ($post) {
			if ($post->post_type == 'post') {
				echo    '<script> 
						jQuery(document).ready(function($) { 
							$( "#event-startdate-field" ).datepicker({ firstDay: 1, dateFormat: "dd-mm-yy" }); 
							$( "#event-enddate-field" ).datepicker({ firstDay: 1, dateFormat: "dd-mm-yy" }); 
						}); 
						</script>';  
			}  
		}   
	}


    /* Function: rsec_meta_box
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_meta_box(){  
        add_meta_box(  
             'rsec-events',
             __('Event Data', 'rsec'),  
             array( &$this, 'rsec_meta_box_content' ),  
             'post',  
             'normal',  
             'high'  
        );  
    }  
     
     
    /* Function: rsec_meta_box_content
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_meta_box_content(){  
		global $post;  
		// Use nonce for verification  
		wp_nonce_field( plugin_basename( __FILE__ ), 'rsec-events_nounce' );  
  
		// The actual fields for data entry  
        echo '<table>
				<tr>';

		$event_startdate_field = get_post_meta($post->ID, 'event-startdate', TRUE);
		if (!empty($event_startdate_field)) {
			$event_startdate_field = date('d-m-Y', strtotime($event_startdate_field));
		}
        
		$event_enddate_field = get_post_meta($post->ID, 'event-enddate', TRUE);
		if (!empty($event_enddate_field)) {
			$event_enddate_field = date('d-m-Y', strtotime($event_enddate_field));
		}
			        
		echo '<td><label for="event-startdate-field">';  
		_e('Start Date/Time', 'rsec' );  
		echo '</label></td>';  
		echo '<td><input type="text" id="event-startdate-field" name="event-startdate-field" value="' . $event_startdate_field . '" size="10" maxlength="10" /></td>';
        
        echo '</tr><tr>';

        echo '<td><label for="event-enddate-field">';  
		_e('End Date/Time', 'rsec' );  
		echo '</label></td>';  
		echo '<td><input type="text" id="event-enddate-field" name="event-enddate-field" value="' . $event_enddate_field . '" size="10" maxlength="10" /></td>';
        
		echo '</tr>
			</table>';

    } 

    /* Function: rsec_save_data
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_save_data($post_id){  
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
		}
  
		if ( !isset( $_POST['rsec-events_nounce'] ) ) {
			return;
		}
		
        if ( !wp_verify_nonce( $_POST['rsec-events_nounce'], plugin_basename( __FILE__ ) ) ) {
            return;
		}
  
        // Check permissions  
        if ( 'page' == $_POST['post_type'] ){  
            if ( !current_user_can( 'edit_page', $post_id ) ) { 
                return;  
			}
        } else {  
            if ( !current_user_can( 'edit_post', $post_id ) ) {
                return;
			}
        }  
  
        $event_startdate_data = $_POST['event-startdate-field'];  
        $event_enddate_data = $_POST['event-enddate-field'];  
  
        if ( !empty($event_startdate_data) && empty($event_enddate_data) ) {
            $event_startdate_data = date('Y-m-d', strtotime($event_startdate_data));  
            update_post_meta($post_id, 'event-startdate', $event_startdate_data, get_post_meta($post_id, 'event-startdate', TRUE));  
            update_post_meta($post_id, 'event-enddate', $event_startdate_data, get_post_meta($post_id, 'event-enddate', TRUE));  
        } elseif ( !empty($event_startdate_data) && !empty($event_enddate_data) ) {
            $event_startdate_data = date('Y-m-d', strtotime($event_startdate_data));  
            $event_enddate_data = date('Y-m-d', strtotime($event_enddate_data));  
            update_post_meta($post_id, 'event-startdate', $event_startdate_data, get_post_meta($post_id, 'event-startdate', TRUE));  
            update_post_meta($post_id, 'event-enddate', $event_enddate_data, get_post_meta($post_id, 'event-enddate', TRUE));
        } else {
            delete_post_meta($post_id, 'event-startdate');  
            delete_post_meta($post_id, 'event-enddate');
        }
  
    }
    
     
    /* Function: rsec_shortcode
     ** this function processes the shortcode
     ** args: string 
     ** returns: string
     */
	function rsec_shortcode($atts) {
		$html = $this->rsec_show_calendar($atts);
		return $html;
	}
  

    /* Function: show_calendar
     ** this function 
     ** args: none 
     ** returns: none
     */
    function rsec_show_calendar($atts) {
    
		$defaults = array(
			'firstDay' => intval(get_option('start_of_week')),
		);
		$atts = shortcode_atts( $defaults, $atts );

		$includes = includes_url();
		
		$html = '<div id="fullcalendar"></div>';
		$html .= '<div id="fullcalendar_loading" style="display:none; text-align: center;" >';
		$html .= '<img src="' . $includes . 'images/spinner.gif' . '" style="vertical-align:middle; padding: 0px 5px 5px 0px;" />' . __('Loading&#8230;', 'rsec') . '</div>';

		$url = urlencode(trailingslashit(str_replace('https://', 'webcal://', get_bloginfo('wpurl'))) . '?rsec_export=yes');
		$gcal = 'http://www.google.com/calendar/render?cid=' . $url;
		$ical = trailingslashit(get_bloginfo('wpurl')) . '?rsec_export=yes';
		$html .= '<div id="calendar-footer"><a target="_blank" href="' . $gcal . '">' . __('Export to Google Calendar', 'rsec') . '</a> | <a target="_blank" href="' . $ical . '">' . __('Download iCal file', 'rsec') . '</a></div>';
        
		return $html;
	}

	/* Function: rsec_fullcalendar
     ** this function 
     ** args: string 
     ** returns: string
     */
	function rsec_fullcalendar() {
		  global $post;

  		$request = array(
			'event_start_before' => $_GET['end'],
			'event_end_after' => $_GET['start']
		);
		$presets = array();

		// Retrieve events		
		$query = array_merge($request,$presets);
		$events = $this->rsec_get_events($query);
		$eventsarray = array();

		// Loop through events
		if ($events) { 
			foreach ($events as $post) {
				$event = array();

				// Title and url
				$event['title'] = html_entity_decode(get_the_title($post->ID), ENT_QUOTES, 'UTF-8');
				$event['url'] = esc_js(get_permalink($post->ID));

				$startdate = get_post_meta($post->ID, 'event-startdate', TRUE);
				$enddate = get_post_meta($post->ID, 'event-enddate', TRUE);
				$starttime = get_post_meta($post->ID, 'event-starttime', TRUE);
				$endtime = get_post_meta($post->ID, 'event-endtime', TRUE);
        
				// Get Event Start and End date, set timezone to the blog's timezone
				$event_start = new DateTime($startdate . ' ' . $starttime, new DateTimeZone('Europe/Madrid'));
				$event_end = new DateTime($enddate . ' ' . $endtime, new DateTimeZone('Europe/Madrid'));
				$event_end = $event_end->add(new DateInterval('P1D')); // add one day
				$event['start']= $event_start->format('Y-m-d\TH:i:s\Z');
				$event['end']= $event_end->format('Y-m-d\TH:i:s\Z');	

				// Colour events
				$now = new DateTime(null, new DateTimeZone('Europe/Madrid'));
				if ($event_start <= $now) {
					$event['color'] = '#372b2b';
				} else {
					$event['color'] = '#a0a3a3';
				}
				  
				// Add event to array
				$eventsarray[]=$event;
			}
		}

		// Echo result and exit
		echo json_encode($eventsarray);
		exit;
	}


	/* Function: rsec_get_events
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_get_events($args=array()) {

		//In case an empty string is passed
		if(empty($args))
			$args = array();

		//These are preset to ensure the plugin functions properly
		$required = array('post_type' => 'post', 'post_status' => 'publish', 'suppress_filters' => 0);

		$now = date('Y-m-d');
       
		//These are the defaults
		$defaults = array(
			'numberposts' => -1,
			'meta_key'    => 'event-startdate',
			'meta_query'  => array(
							array(
								'key'     => 'event-enddate',
								'value'   => $now,
								'type'    => 'date',
								'compare' => '>='
				)
			),
        	'orderby'     => 'meta_value',
        	'order'       => 'asc'
		);
	
		//Construct the query array	
		$query_array = array_merge($defaults,$args,$required);

		if($query_array) {
			$events=get_posts($query_array);
			return $events;
		}

		return false;
  
	}

    /* Function: rsec_query_vars
     ** this function 
     ** args: 
     ** returns: 
     */
    function rsec_query_vars($vars) {
        array_push($vars, 'rsec_export');
        return $vars;
    }

	/* Function: rsec_parse_request
     ** this function 
     ** args: string 
     ** returns: string
     */
    function rsec_parse_request($query) {
		if (!empty($query->query_vars['rsec_export'])) {
			$this->rsec_generate_ics();
			exit();
		}
	}

	/* Function: rsec_generate_ics
     ** this function 
     ** args: string 
     ** returns: string
     */
	function rsec_generate_ics() {
		global $post;

		$calendar_name = get_option('blogname');
		$calendar_description = sprintf(__('%s Calendar', 'rsec'), $calendar_name);
		$timezone = get_option('timezone_string');

		$ics = ical_header($calendar_name, $calendar_description, $timezone);
        
		$events = $this->rsec_get_events($query);
		$eventsarray = array();

		//Loop through events
		if ($events) { 
			foreach ($events as $post) {
				$created = new DateTime($post->post_date_gmt);
				$created_date = $created->format('Ymd\THis\Z');

				$modified = new DateTime($post->post_modified_gmt);
				$modified_date = $modified->format('Ymd\THis\Z');

				$event_start = new DateTime(get_post_meta($post->ID, 'event-startdate', TRUE));
				$event_end = new DateTime(get_post_meta($post->ID, 'event-enddate', TRUE));

				//Set up start and end date times
				if ($event_start == $event_end){
					$start_date = $event_start->format('Ymd');
					$end_date = $event_end->format('Ymd');				
				} else {
					$start_date = $event_start->format('Ymd');
					$event_end->modify('+1 day');
					$end_date = $event_end->format('Ymd');
				}
			          
				$url = get_permalink($post->ID);
				$summary = $post->post_title;
				$description = mb_strimwidth(strip_tags($post->post_content), 0, 200, '...'). ' ' . $url;
                
				$ics .= ical_event($post->ID, $created_date, $modified_date, $start_date, $end_date, $summary, $description, $url);
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

$really_simple_event_calendar = new ReallySimpleEventCalendar;  

?>