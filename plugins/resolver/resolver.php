<?php
/**
 * Plugin Name: Resolver Wordpress Test Integration
 * Plugin URI: https://resolver.clotheslineonline.ca/
 * Description: Inegration of CVILY API for Candidate and Job Order functions in WordPress
 * Version: 1
 * Text Domain: resolver
 * Author: James Maguire
 * Author URI: www.clotheslineonline.ca
 */

	function myplugin_scripts() {
	    wp_register_style( 'styles',  plugin_dir_url( __FILE__ ) . 'css/style.css', null, rand(1,9999)); // Rand version for now cache bust.
	    wp_enqueue_style( 'styles' );
	}

	add_action( 'wp_enqueue_scripts', 'myplugin_scripts' );

	add_filter( 'query_vars', 'add_custom_query_vars' );
	
	function add_custom_query_vars( $vars )
	{
    	array_push($vars, "source_page");
    	return $vars;
	}

    function resolver_shortcode_demo ($atts) {
	
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'resolver_interview';

		$endpoint_url = shortcode_atts( array(
			'source' => 'https://gorest.co.in/public-api/posts/?page=1'
	 	), $atts );

		$source = $endpoint_url["source"];
	
		// If isset source then use that. Should move all that to Ajax in the end so not reloading page.
		
		if ( get_query_var('source_page') ) {
			$source = 'https://gorest.co.in/public-api/posts/?page=' . get_query_var('source_page');
		}
		
		//$next_page_url = 'https://gorest.co.in/public-api/posts/?page=2';
		
		$response = wp_remote_get( $source, array(
		'method'      => 'GET',
		'timeout'     => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => array(),
		'cookies'     => array()
		)
	);
	 
	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		echo "Something went wrong: $error_message";
		
	} else {
		
		$importData = json_decode($response["body"], true);

		checkJSONErrors();
			
		foreach ($importData['data'] as $data) {
		
			// Add to database

			$item=array(
				'id'=> $data['id'],
				'user_id' => $data['user_id'],
				'title' => $data['title'],
				'body' => $data['body'],
				'created_at' => $data['created_at'],
				'updated_at' => $data['updated_at']

			);

			$datastr .= '<div class="bodycontainer"><h3>' . $data['title'] . '</h3>

				<div class="containerbody">
					<p> ' . $data['body'] . ' </p>
				</div>		
			
				<div class="userbody">
					<p> User ID: ' . $data['user_id'] . ' </p>
					<p> Created: ' . $data['created_at'] . ' </p>
					<p> Updated: ' . $data['updated_at'] . ' </p>
				</div>		
			
			
			</div>';
			

			// Add records to DB
			$result = $wpdb->insert($table_name, $item);
			
		};

		// Should change this to Ajax rather than self reload the page.
		$datastr .= '<div id="loadbutton"><a href="/resolver/?source_page=2"><button>CLICK TO LOAD MORE</button></a></div>';
				
		$datastr .= '</div>';

		}


		return $datastr;
	}

	
	function checkJSONErrors () {
		
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					//echo ' - No errors';
				break;
				case JSON_ERROR_DEPTH:
					echo ' - Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					echo ' - Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					echo ' - Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					echo ' - Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				default:
					echo ' - Unknown error';
				break;
			}
	}
	
	add_shortcode('resolver_interview', 'resolver_shortcode_demo');

	