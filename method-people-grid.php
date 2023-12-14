<?php
/**
 * Plugin Name: Method People Grid
 * Plugin URI: https://github.com/pixelwatt/method-people-grid
 * Description: This plugin adds a versitile shortcode for displaying grids of people, with AJAX-powered modals.
 * Version: 0.9.1
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
		'hierarchical'       => false,
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
		'htag' => $layout->get_option( 'shortcode_htag', 'h2' ),
		'modals' => ( 'yes' == $layout->get_option( 'shortcode_modals', 'yes' ) ? true : false ),
		'spacing' => $layout->get_option( 'grid_spacing', '3' ),
		'people_per_row' => $layout->get_option( 'people_per_row', '4' ),
	), $atts, 'peoplegroup' );

	if ( ! empty ( $atts['id'] ) ) {
		$pids = explode( ',', $atts['id'] );
		$pargs = array(
			'post_type' => 'method_people',
			'order' => 'menu_order',
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
}