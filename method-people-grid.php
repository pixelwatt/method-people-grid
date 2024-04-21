<?php
/**
 * Plugin Name: Method People Grid
 * Plugin URI: https://github.com/pixelwatt/method-people-grid
 * Description: This plugin adds a versitile shortcode for displaying grids of people, with AJAX-powered modals.
 * Version: 0.9.2
 * Author: Rob Clark
 * Author URI: https://robclark.io
 */

// Include utility class
require_once('class-method-people-grid-utility.php');


// Register frontend stylesheets and scripts

function method_people_grid_enqueue_frontend_dependencies() {
    wp_enqueue_style( 'method-people-grid', plugin_dir_url( __FILE__ ) . 'assets/css/method-people-grid.css', '', '0.9.1' );
}

add_action( 'wp_enqueue_scripts', 'method_people_grid_enqueue_frontend_dependencies' );


// Register admin stylesheets and scripts

function method_people_grid_enqueue_backend_dependencies() {
	$wp_scripts = wp_scripts();
	wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css', '', '', false );
    wp_enqueue_style( 'method-people-grid-admin', plugin_dir_url( __FILE__ ) . '/assets/css/admin-styles.css', '', '0.9.1' );
}

add_action( 'admin_enqueue_scripts', 'method_people_grid_enqueue_backend_dependencies' );


// Register admin dialog for shortcode options

function method_people_grid_admin_footer_function() {
	echo '
		<script>
		  jQuery( function() {
		    jQuery( "#method-people-grid-tags-dialog" ).dialog({
		      autoOpen: false,
		      width: 600,
		    });
		 
		    jQuery( ".method-people-grid-tags-opener" ).on( "click", function() {
		      jQuery( "#method-people-grid-tags-dialog" ).dialog( "open" );
		    });
		  });
		</script>

		<div style="display: none; visibility: hidden;">
			<div id="method-people-grid-tags-dialog" title="Shortcode Options">
			  <p>The [peoplegrid] shortcode accepts the following options:</p>
			  <hr>
			  <h5><code>id</code></h5>
			  <p>The group ID (or IDs) to pull people from for display. To load more than one group, simply provide multiple IDs, seperated by commas (no spaces). When loading from multiple groups, people that belong to both groups will only displayed once (no need to worry about duplicates).</p>
			  <hr>
			  <h5><code>headline</code></h5>
			  <p>The (optional) headline to display above the grid of people. Don\'t specify or leave empty to omit.</p>
			  <hr>
			  <h5><code>htag</code></h5>
			  <p>If providing a grid headline, the tag to use for the headline. This can be any HTML tag, without brackets or attributes (ex: <code>h1</code>, <code>h2</code>, <code>h3</code>, etc). Defaults to the tag configured in this plugin\'s <a href="/wp-admin/options-general.php?page=method_people_grid_options">options</a>.</p>
              <h5><code>modals</code></h5>
			  <p>Whether or not to load bio overlays when a person in this grid is clicked. "true" to load, "false" to omit. Defaults to the visibility configured in this plugin\'s <a href="/wp-admin/options-general.php?page=method_people_grid_options">options</a>.</p>
	';
	do_action( 'method_people_grid_after_tags_dialog_html' );
	echo '
			</div>
		</div>
	';
}

add_action( 'admin_footer', 'method_people_grid_admin_footer_function', 100 );


// Function for displaying the shortcode options dialog trigger

function method_people_grid_get_tags_badge() {
	return '<span class="method-people-grid-tags-opener">Options</span> ';
}


// Register post type

add_action( 'init', 'method_people_grid_post_type_init' );

function method_people_grid_post_type_init() {
	$labels = array(
		'name'               => _x( 'People', 'post type general name', 'method-people-grid' ),
		'singular_name'      => _x( 'Person', 'post type singular name', 'method-people-grid' ),
		'menu_name'          => _x( 'People', 'admin menu', 'method-people-grid' ),
		'name_admin_bar'     => _x( 'Person', 'add new on admin bar', 'method-people-grid' ),
		'add_new'            => _x( 'Add Person', 'job', 'method-people-grid' ),
		'add_new_item'       => __( 'Add New Person', 'method-people-grid' ),
		'new_item'           => __( 'New Person', 'method-people-grid' ),
		'edit_item'          => __( 'Edit Person', 'method-people-grid' ),
		'view_item'          => __( 'View Person', 'method-people-grid' ),
		'all_items'          => __( 'People', 'method-people-grid' ),
		'search_items'       => __( 'Search People', 'method-people-grid' ),
		'parent_item_colon'  => __( 'Parent Person:', 'method-people-grid' ),
		'not_found'          => __( 'No people found.', 'method-people-grid' ),
		'not_found_in_trash' => __( 'No people found in Trash.', 'method-people-grid' )
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'A description for the post type.', 'method-people-grid' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'query_var'          => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => true,
		'menu_position' 	 => 5,
		'menu_icon'			 => 'dashicons-megaphone',
		'supports'           => array( 'title' , 'editor', 'thumbnail' )
	);

	register_post_type( 'method_people', $args );
}


// Register taxonomy

add_action( 'init', 'method_people_grid_taxonomy_init', 0 );

function method_people_grid_taxonomy_init() {
	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name' => _x( 'People Groups', 'taxonomy general name' ),
		'singular_name' => _x( 'People Group', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search People Groups' ),
		'all_items' => __( 'All People Groups' ),
		'parent_item' => __( 'Parent People Group' ),
		'parent_item_colon' => __( 'Parent People Group:' ),
		'edit_item' => __( 'Edit People Group' ),
		'update_item' => __( 'Update People Group' ),
		'add_new_item' => __( 'Add New People Group' ),
		'new_item_name' => __( 'New People Group Name' ),
		'menu_name' => __( 'People Groups' ),
	);

	register_taxonomy('method_peoplegroups',array('method_people'), array(
		'hierarchical' => true,
		'public' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'archive' => false,
		'show_admin_column' => true
	));
}


// Register shortcode

function method_people_grid_add_shortcode( $atts, $content, $shortcode_tag ) {
	$shou = '';
	$layout = new Method_People_Grid_Utility;
	$randID = rand();
    $cols = intval( $layout->get_option( 'theme_cols', '12' ) );
	$atts = shortcode_atts( array(
		'id' => '',
		'headline' => '',
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'htag' => $layout->get_option( 'shortcode_htag', 'h2' ),
		'modals' => ( 'yes' == $layout->get_option( 'shortcode_modals', 'yes' ) ? true : false ),
		'social' => ( 'yes' == $layout->get_option( 'shortcode_social', 'no' ) ? true : false ),
		'spacing' => $layout->get_option( 'grid_spacing', '3' ),
		'people_per_row' => $layout->get_option( 'people_per_row', '4' ),
	), $atts, 'peoplegroup' );

	if ( ! empty ( $atts['id'] ) ) {
		$pids = explode( ',', $atts['id'] );
		$pargs = array(
			'post_type' => 'method_people',
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
			'numberposts' => -1,
			'fields' => 'ids',
			'tax_query' => array(
				array(
		            'taxonomy' => 'method_peoplegroups',
		            'field'    => 'term_id',
		            'terms'    => ( 1 == count( $pids ) ? $atts['id'] : $pids ),
				),
			),
		);
		$items = get_posts( $pargs );
		if ( $items ) {
			if ( is_array( $items ) ) {
				if ( 0 < count( $items ) ) {
					$col_classes = '';
					switch ( $atts['people_per_row'] ) {
						case '1':
							$col_classes = 'col-' . $cols;
							break;
						case '2':
							$col_classes = 'col-' . $cols . ' col-md-' . ( $cols / 2 ) . ' col-lg-' . ( $cols / 2 );
							break;
						case '3':
							$col_classes = 'col-' . $cols . ' col-md-' . ( $cols / 3 ) . ' col-lg-' . ( $cols / 3 );
							break;
						case '4':
							$col_classes = 'col-' . $cols . ' col-md-' . ( $cols / 3 ) . ' col-lg-' . ( $cols / 4 );
							break;
					}
					$shou = '<div class="method-peoplegroup-grid"><div class="row g-' . $atts['spacing'] . '">';
					if ( ! empty( $atts['headline'] ) ) {
						$shou .= '<div class="method-peoplegroup-grid-headline"><' . $atts['htag'] . '>' . $atts['headline'] . '</' . $atts['htag'] . '></div>';
					}
					foreach ( $items as $item ) {
						$layout->load_meta( $item );
						$shou .= '
							<div class="' . $col_classes . '">
								<div class="method-peoplegroup-item method-peoplegroup-item-group-' . $randID . '"' . ( true === $atts['modals'] ? ' data-bs-toggle="modal" data-bs-target="#methodPersonModal' . $randID . '" data-person="' . $item . '"' : '' ) . '>
									<div class="method-peoplegroup-item-photo method-fit-img-container">
										' . ( has_post_thumbnail( $item ) ? get_the_post_thumbnail( $item, 'large', array( 'class' => 'method-fit-img' ) ) : '' ) . '
									</div>
									<h4>' . get_the_title( $item ) . '</h4>
						';
						if ( ( $layout->get_loaded_meta( '_method_people_title' ) ) || ( $layout->get_loaded_meta( '_method_people_org' ) ) ) {
							$shou .= '<p class="method-peoplegroup-item-org">' . $layout->get_loaded_headline( '_method_people_title', '', '' ) . ( $layout->get_loaded_meta( '_method_people_title' ) ? ( $layout->get_loaded_meta( '_method_people_org' ) ? ', <br>' : '' ) : '' ) . $layout->get_loaded_headline( '_method_people_org', '', '' ) . '</p>';
						}
						if ( true === $atts['social'] ) {
							$shou .= method_people_grid_build_social_icons( 'method-people-group-social', 24, $layout->get_serialized_loaded_meta( '_method_people_social_accounts' ) );
						}
						$shou .= '
								</div>
						';
						if ( true === $atts['modals'] ) {
							$loading = 'Loading bio...';
							$shou .= '
								<script>
									jQuery(function($){
										$( ".method-peoplegroup-item-group-' . $randID . '" ).click(function() {
											var personID = $(this).attr("data-person");
											$(\'#methodPersonBuild' . $randID . ' #method_person_id\').val(personID);
											$(\'#methodPersonModal' . $randID . ' .modal-body\').html(\'\')
											$( "#methodPersonBuild' . $randID . '" ).submit();
										});
										$(\'#methodPersonBuild' . $randID . '\').submit(function(){
											var methodPersonBuild = $(\'#methodPersonBuild' . $randID . '\');
											$.ajax({
												url:methodPersonBuild.attr(\'action\'),
												data:methodPersonBuild.serialize(), // form data
												type:methodPersonBuild.attr(\'method\'), // POST
												beforeSend:function(xhr){
													$(\'#methodPersonModal' . $randID . ' .modal-body\').html(\'' . $loading . '\');
												},
												success:function(data){
													$(\'#methodPersonModal' . $randID . ' .modal-body\').html(data); // insert data
												}
											});
											return false;
										});
									});
								</script>
								<form action="' . site_url() . '/wp-admin/admin-ajax.php" method="POST" id="methodPersonBuild' . $randID . '" class="d-none">
									<input autocomplete="off" type="hidden" id="method_person_id" name="method_person_id" value="">
									<input type="hidden" name="action" value="methodPersonBuild">
									<input type="hidden" name="randID" value="' . $randID . '">
								</form>
								<!-- Modal -->
								<div class="modal methodPersonModal fade" id="methodPersonModal' . $randID . '" tabindex="-1" role="dialog" aria-labelledby="methodPersonModal' . $randID . 'Label" aria-hidden="true">
									<div class="modal-dialog modal-xl" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<div> </div>
												<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
											  </div>
											<div class="modal-body">
	
											</div>
										</div>
									</div>
								</div>
	
							';
						}
						$shou .= '
							</div>
						';
						$layout->unload_meta();
					}
					$shou .= '</div></div>';
				}
			}
		}
	}
	return $shou;
}

add_shortcode( 'peoplegroup', 'method_people_grid_add_shortcode' );


function method_people_grid_build_social_icons( $class = 's-ics', $icon_size = 16, $data ) {
		$output = '';

		$social_links = $data;
		if ( ! empty( $social_links ) ) {
			if ( is_array( $social_links ) ) {
				if ( method_people_grid_check_array( $social_links, 'service' ) ) {
					$output .= '<ul class="' . $class . '">';

					foreach ( $social_links as $link ) {
						$service = $link['service'];

						switch ( $service ) {
							case 'facebook':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/></svg>';
								break;
							case 'twitter':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/></svg>';
								break;
							case 'linkedin':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16"><path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401m-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4"/></svg>';
								break;
							case 'instagram':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16"><path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"/></svg>';
								break;
							case 'pinterest':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-pinterest" viewBox="0 0 16 16"><path d="M8 0a8 8 0 0 0-2.915 15.452c-.07-.633-.134-1.606.027-2.297.146-.625.938-3.977.938-3.977s-.239-.479-.239-1.187c0-1.113.645-1.943 1.448-1.943.682 0 1.012.512 1.012 1.127 0 .686-.437 1.712-.663 2.663-.188.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.664 0-1.915-1.377-3.254-3.342-3.254-2.276 0-3.612 1.707-3.612 3.471 0 .688.265 1.425.595 1.826a.24.24 0 0 1 .056.23c-.061.252-.196.796-.222.907-.035.146-.116.177-.268.107-1-.465-1.624-1.926-1.624-3.1 0-2.523 1.834-4.84 5.286-4.84 2.775 0 4.932 1.977 4.932 4.62 0 2.757-1.739 4.976-4.151 4.976-.811 0-1.573-.421-1.834-.919l-.498 1.902c-.181.695-.669 1.566-.995 2.097A8 8 0 1 0 8 0"/></svg>';
								break;
							case 'youtube':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16"><path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.007 2.007 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31.4 31.4 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408z"/></svg>';
								break;
							case 'twitch':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-twitch" viewBox="0 0 16 16"><path d="M3.857 0 1 2.857v10.286h3.429V16l2.857-2.857H9.57L14.714 8V0H3.857zm9.714 7.429-2.285 2.285H9l-2 2v-2H4.429V1.143h9.142z"/><path d="M11.857 3.143h-1.143V6.57h1.143zm-3.143 0H7.571V6.57h1.143z"/></svg>';
								break;
							case 'tiktok':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-tiktok" viewBox="0 0 16 16"><path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z"/></svg>';
								break;
							case 'threads':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-threads" viewBox="0 0 16 16"><path d="M6.321 6.016c-.27-.18-1.166-.802-1.166-.802.756-1.081 1.753-1.502 3.132-1.502.975 0 1.803.327 2.394.948.591.621.928 1.509 1.005 2.644.328.138.63.299.905.484 1.109.745 1.719 1.86 1.719 3.137 0 2.716-2.226 5.075-6.256 5.075C4.594 16 1 13.987 1 7.994 1 2.034 4.482 0 8.044 0 9.69 0 13.55.243 15 5.036l-1.36.353C12.516 1.974 10.163 1.43 8.006 1.43c-3.565 0-5.582 2.171-5.582 6.79 0 4.143 2.254 6.343 5.63 6.343 2.777 0 4.847-1.443 4.847-3.556 0-1.438-1.208-2.127-1.27-2.127-.236 1.234-.868 3.31-3.644 3.31-1.618 0-3.013-1.118-3.013-2.582 0-2.09 1.984-2.847 3.55-2.847.586 0 1.294.04 1.663.114 0-.637-.54-1.728-1.9-1.728-1.25 0-1.566.405-1.967.868ZM8.716 8.19c-2.04 0-2.304.87-2.304 1.416 0 .878 1.043 1.168 1.6 1.168 1.02 0 2.067-.282 2.232-2.423a6.217 6.217 0 0 0-1.528-.161"/></svg>';
								break;
							case 'bluesky':
								$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $icon_size . '" height="' . $icon_size . '" fill="currentColor" class="bi bi-square-fill" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2z"/></svg>';
								break;
							default:
								$icon = '';
								break;
						}

						$output .= ' <li>' . ( isset( $link['url'] ) ? ( ! empty( $link['url'] ) ? '<a target="_blank" href="' . $link['url'] . '">' : '' ) : '' ) . $icon . '<span class="visually-hidden-focusable"> ' . ucwords( $service ) . '</span>' . ( isset( $link['url'] ) ? ( ! empty( $link['url'] ) ? '</a>' : '' ) : '' ) . '</li>';
					}

					$output .= '</ul>';
				}
			}
		}

		return $output;
	}

// Register AJAX response

function method_people_grid_build_modal() {
	$data = $_POST;
	$output = '';

    $args = array(
        'include' => $data['method_person_id'],
        'posts_per_page'   => 1,
        'post_type'        => 'method_people',
		'fields' => 'ids',
    );

	$people = get_posts($args);
	if ( $people ) {
		if ( is_array( $people ) ) {
			if ( 0 < count( $people ) ) {
				$person = $people[0];
				$layout = new Method_People_Grid_Utility;
                $cols = intval( $layout->get_option( 'theme_cols', '12' ) );
				$layout->load_meta( $person );
				$output = '
						<div class="row justify-content-between">
							<div class="col-' . $cols . ' col-md-' . ( $cols / 4 ) . '">
								<div class="methodPersonModalMeta">
									<div class="method-fit-img-container">
										' . ( has_post_thumbnail( $person ) ? get_the_post_thumbnail( $person, 'large', array( 'class' => 'method-fit-img' ) ) : '' ) . '
									</div>
									<h5 class="modal-title" id="methodPersonModal' . $data['randID'] . 'Label">' . get_the_title( $person ) . '</h5>
				';
				if ( ( $layout->get_loaded_meta( '_method_people_title' ) ) || ( $layout->get_loaded_meta( '_method_people_org' ) ) ) {
					$output .= '<p class="method-peoplegroup-item-org">' . $layout->get_loaded_headline( '_method_people_title', '', '' ) . ( $layout->get_loaded_meta( '_method_people_title' ) ? ( $layout->get_loaded_meta( '_method_people_org' ) ? ', <br>' : '' ) : '' ) . $layout->get_loaded_headline( '_method_people_org', '', '' ) . '</p>';
				}
				$output .= '
									
								</div>
							</div>
							<div class="col-' . $cols . ' col-md-' . ( 12 == $cols ? '8' : '17' ) . '">
								<div class="methodPersonModalContent">
									' . $layout->filter_content( get_the_content( null, false, $person ) ) . '
								</div>
							</div>
						</div>
				';
			}
		}
	}
	echo $output;
	wp_die();
}

add_action('wp_ajax_methodPersonBuild', 'method_people_grid_build_modal'); 
add_action('wp_ajax_nopriv_methodPersonBuild', 'method_people_grid_build_modal');


// Register CMB2 metabox for method_people

add_action( 'cmb2_admin_init', 'method_people_grid_register_people_metabox' );

function method_people_grid_register_people_metabox() {
	$cmb_options = new_cmb2_box(
		array(
			'id'            => '_method_people_grid_metabox_people',
			'title'         => esc_html__( 'Additional Options', 'method-people-grid' ),
			'object_types'  => array( 'method_people' ),
		)
	);
	$cmb_options->add_field(
        array(
            'name'     => __( 'Title', 'method-people-grid' ),
            'desc'     => __( '(Optional) Provide a title for this person.', 'method-people-grid' ),
            'id'   => '_method_people_title',
            'type'     => 'text',
        )
    );
    $cmb_options->add_field(
        array(
            'name'     => __( 'Organization', 'method-people-grid' ),
            'desc'     => __( '(Optional) Provide an organization for this person.', 'method-people-grid' ),
            'id'   => '_method_people_org',
            'type'     => 'text',
        )
    );
	$group_field_social_accounts = $cmb_options->add_field(
		array(
			'id'          => '_method_people_social_accounts',
			'type'        => 'group',
			'description' => __( 'Below, add relevant social accounts if opting to dispay them.', 'method-people-grid' ),
			// 'repeatable'  => false, // use false if you want non-repeatable group
			'options'     => array(
				'group_title'       => __( 'Account {#}', 'method-people-grid' ), // since version 1.1.4, {#} gets replaced by row number
				'add_button'        => __( 'Add Another Account', 'method-people-grid' ),
				'remove_button'     => __( 'Remove Account', 'method-people-grid' ),
				'sortable'          => true,
				'closed'         => true, // true to have the groups closed by default
				// 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'method-people-grid' ), // Performs confirmation before removing group.
			),
		)
	);

	$cmb_options->add_group_field(
		$group_field_social_accounts,
		array(
			'name' => 'Service',
			'id'   => 'service',
			'type' => 'select',
			'show_option_none' => true,
			'default' => '',
			'desc' => __( 'Which service are you adding a link for?', 'method-people-grid' ),
			'options' => array(
				'facebook' => esc_attr__( 'Facebook', 'method-people-grid' ),
				'twitter' => esc_attr__( 'Twitter (X)', 'method-people-grid' ),
				'linkedin' => esc_attr__( 'LinkedIn', 'method-people-grid' ),
				'instagram' => esc_attr__( 'Instagram', 'method-people-grid' ),
				'pinterest' => esc_attr__( 'Pinterest', 'method-people-grid' ),
				'youtube' => esc_attr__( 'YouTube', 'method-people-grid' ),
				'twitch' => esc_attr__( 'Twitch', 'method-people-grid' ),
				'tiktok' => esc_attr__( 'TikTok', 'method-people-grid' ),
				'threads' => esc_attr__( 'Threads', 'method-people-grid' ),
				'bluesky' => esc_attr__( 'Bluesky', 'method-people-grid' ),
			),
		)
	);

	$cmb_options->add_group_field(
		$group_field_social_accounts,
		array(
			'name' => __( 'Profile URL', 'method-people-grid' ),
			'desc' => __( 'Enter the full URL for your profile.', 'method-people-grid' ),
			'id'   => 'url',
			'type' => 'text_url',
		)
	);
}

add_action( 'cmb2_admin_init', 'method_people_grid_register_peoplegroup_metabox' );


// Register CMB2 metabox for method_peoplegroups

function method_people_grid_register_peoplegroup_metabox() {
	$cmb_options = new_cmb2_box(
		array(
			'id'            => '_method_people_grid_metabox_peoplegroup',
			'title'         => esc_html__( 'Group Options', 'method-people-grid' ),
			'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
			'taxonomies'       => array( 'method_peoplegroups' ), // Tells CMB2 which taxonomies should have these fields
			'new_term_section' => false,
		)
	);
	$cmb_options->add_field(
        array(
            'name'     => __( 'Shortcode', 'method-people-grid' ),
            //'desc'     => __( '(Optional) Provide a title for this person.', 'method-people-grid' ),
            'id'   => '_method_peoplegroup_shortcode',
            'type'     => 'text',
            'default_cb' => 'method_people_grid_default_shortcode_in_admin',
            'display_cb' => 'method_people_grid_display_shortcode_in_admin',
            'attributes' => array(
                'disabled' => 'disabled',
            ),
            'column' => array(
                'position' => 2,
                'name'     => 'Grid Shortcode',
            ),
        )
    );
}


// Callback for displaying the shortcode in the admin

function method_people_grid_display_shortcode_in_admin( $field_args, $field ) {
	return '[peoplegroup id="' . $field_args['render_row_cb'][0]->object_id . '"] ' . method_people_grid_get_tags_badge();
}

function method_people_grid_default_shortcode_in_admin( $field_args, $field ) {
	return '[peoplegroup id="' . $field_args['render_row_cb'][0]->object_id . '"]';
}


// Register plugin options

add_action( 'cmb2_admin_init', 'method_people_grid_register_plugin_options' );

function method_people_grid_register_plugin_options() {
	$cmb_options = new_cmb2_box(
		array(
			'id'           => 'method_people_grid_plugin_options_metabox',
			'title'        => esc_html__( 'Method People Grid Options', 'method-people-grid' ),
			'object_types' => array( 'options-page' ),

			/*
			 * The following parameters are specific to the options-page box
			 * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
			 */

			'option_key'      => 'method_people_grid_options', // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'      => esc_html__( 'People Grid', 'method-people-grid' ), // Falls back to 'title' (above).
			'parent_slug'     => 'options-general.php', // Make options page a submenu item of the themes menu.
			// 'capability'      => 'manage_options', // Cap required to view options-page.
			'position'        => 3, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'myprefix' ), // The text for the options-page save button. Defaults to 'Save'.
		)
	);
    $cmb_options->add_field(
		array(
			'name'     => __( '<span style="font-size: 1.25rem; font-weight: 800; line-height: 1; text-transform: none;">Theme Configuration</span>', 'method-people-grid' ),
			'id'       => 'theme_info',
			'type'     => 'title',
		)
	);
    $cmb_options->add_field( array(
        'name'    => 'Grid Columns',
        'id'      => 'theme_cols',
        'type'    => 'radio_inline',
        'desc'    => 'Is this theme configured to use 12 columns (default) or 24 columns?',
        'options' => array(
            '12' => __( '12 Columns', 'method-people-grid' ),
            '24'   => __( '24 Columns', 'method-people-grid' ),
        ),
        'default' => '12',
    ) );
	$cmb_options->add_field( array(
        'name'    => 'People Per Row',
        'id'      => 'people_per_row',
        'type'    => 'radio_inline',
        'desc'    => 'How many people should be displayed per row on desktop?',
        'options' => array(
            '1' => __( '1', 'method-people-grid' ),
            '2'   => __( '2', 'method-people-grid' ),
			'3'   => __( '3', 'method-people-grid' ),
			'4'   => __( '4', 'method-people-grid' ),
        ),
        'default' => '4',
    ) );
	$cmb_options->add_field( array(
        'name'    => 'Grid Spacing',
        'id'      => 'grid_spacing',
        'type'    => 'radio_inline',
        'desc'    => 'Choose the level of grid spacing to use, with 0 being no spacing, and 5 being the most spacing.',
        'options' => array(
			'0' => __( '0', 'method-people-grid' ),
            '1' => __( '1', 'method-people-grid' ),
            '2'   => __( '2', 'method-people-grid' ),
			'3'   => __( '3', 'method-people-grid' ),
			'4'   => __( '4', 'method-people-grid' ),
			'5' => __( '5', 'method-people-grid' ),
        ),
        'default' => '3',
    ) );
    $cmb_options->add_field(
		array(
			'name'     => __( '<span style="font-size: 1.25rem; font-weight: 800; line-height: 1; text-transform: none;">Shortcode Options</span>', 'method-people-grid' ),
			'id'       => 'shortcode_info',
			'type'     => 'title',
		)
	);
    $cmb_options->add_field( array(
        'name'    => 'Default Grid Headline Tag',
        'id'      => 'shortcode_htag',
        'type'    => 'radio_inline',
        'desc'    => 'If a custom tag isn\'t specified in the shortcode, what tag should be used for the grid headline?',
        'options' => array(
            'h1' => __( 'h1', 'method-people-grid' ),
            'h2'   => __( 'h2', 'method-people-grid' ),
            'h3'   => __( 'h3', 'method-people-grid' ),
            'h4'   => __( 'h4', 'method-people-grid' ),
            'h5'   => __( 'h5', 'method-people-grid' ),
            'h6'   => __( 'h6', 'method-people-grid' ),
        ),
        'default' => 'h2',
    ) );
    $cmb_options->add_field( array(
        'name'    => 'Default Modal Inclusion',
        'id'      => 'shortcode_modals',
        'type'    => 'radio',
        'desc'    => 'By default, should a bio overlay load when a person is clicked?',
        'options' => array(
            'yes' => __( 'Yes, load bio modals unless overridden by shortcode arguments.', 'method-people-grid' ),
            'no'   => __( 'No, do not load modals unless overriden by shortcode arguments.', 'method-people-grid' ),
        ),
        'default' => 'yes',
    ) );
	$cmb_options->add_field( array(
        'name'    => 'Default Social Link Inclusion',
        'id'      => 'shortcode_social',
        'type'    => 'radio',
        'desc'    => 'By default, should social links be displayed for each person (if links have been provided)?',
        'options' => array(
            'yes' => __( 'Yes, show social links unless overridden by shortcode arguments.', 'method-people-grid' ),
            'no'   => __( 'No, do not show social links unless overriden by shortcode arguments.', 'method-people-grid' ),
        ),
        'default' => 'no',
    ) );
}

function method_people_grid_check_array_key( $item, $key ) {
	$output = false;
	if ( is_array( $item ) ) {
		if ( array_key_exists( $key, $item ) ) {
			if ( ! empty( $item["{$key}"] ) ) {
				$output = true;
			}
		}
	}
	return $output;
}

	//-----------------------------------------------------
	// Check to see if an array has content.
	//-----------------------------------------------------

function method_people_grid_check_array( $item, $key ) {
	$output = false;
	if ( $item ) {
		if ( is_array( $item ) ) {
			if ( 1 <=count( $item ) ) {
				if ( method_people_grid_check_array_key( $item[0], $key ) ) {
					$output = true;
				}
			}
		}
	}
	return $output;
}