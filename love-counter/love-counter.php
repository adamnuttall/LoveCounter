<?php
/*
Plugin Name: Love Counter
Plugin URI:
Description: This plugin to add loves in posts
Author: Adam Nuttall
Version: 0.1
Author URI:
*/

define('LOVECOUNTERSURL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
define('LOVECOUNTERPATH', WP_PLUGIN_DIR."/".dirname( plugin_basename( __FILE__ ) ) );

//Enqueue scripts
function lovecounter_enqueuescripts()
{
	wp_enqueue_script('lovecounter', LOVECOUNTERSURL.'/js/love-counter.js', array('jquery'));
	wp_localize_script( 'lovecounter', 'lovecounterajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
function lovecounter_enqueuestyles()
{
    wp_enqueue_style('lovecounter', LOVECOUNTERSURL.'/css/counter.css');
}
add_action('wp_enqueue_scripts', lovecounter_enqueuescripts);
add_action('wp_head', lovecounter_enqueuestyles);

//Function to render the 'Love' link
function lovecounter_getlovelink()
{
  $lovecounterlink = "";
  //Check whether all users get counter or just logged in
  if( get_option('lovecounterlogincompulsory') != 'yes' || is_user_logged_in() )
	{
    $post_ID = get_the_ID();
    $lovecountercount = get_post_meta($post_ID, '_lovecountercount', true) != '' ? get_post_meta($post_ID, '_lovecountercount', true) : '0';
    
    //if(!$_COOKIE["Loved-".$post_ID]){
    $link = $lovecountercount.' <a id="lovecounteraddvote-'.$post_ID.'" class="lovecounter">'.'Love'.'</a>';
    //}
    $lovecounterlink = '<div id="lovecounter-'.$post_ID.'">';
    $lovecounterlink .= '<span>'.$link.'</span>';
    $lovecounterlink .= '</div>';
  } else
  {
	  $register_link = site_url('wp-login.php', 'login') ;
	  $lovecounterlink = '<div class="lovelink" >'." <a href=".$register_link.">"."Love"."</a>".'</div>';
  }
  return $lovecounterlink;
}

//Only display 'Love' link when on 'Style' custom post type
function lovecounter_printlovelink($content)
{
  global $post;
  
  if ($post->post_type == 'style') {
    return $content.lovecounter_getlovelink();
  } else {
    return $content;
  }
}

add_filter('the_content', lovecounter_printlovelink);

function lovecounter_addvote()
{
	$results = '';
	global $wpdb;
	$post_ID = $_POST['postid'];
	$lovecountercount = get_post_meta($post_ID, '_lovecountercount', true) != '' ? get_post_meta($post_ID, '_lovecountercount', true) : '0';
	$lovecountercountNew = $lovecountercount + 1;
    //setcookie("Loved-" . $post_ID, $post_ID, time() + (86400 * 7), '~/wp-content/');
	update_post_meta($post_ID, '_lovecountercount', $lovecountercountNew);
	$results .= '<div class="lovescore" >'.$lovecountercountNew.'</div>';
    $results .= '<a class="lovecounter">'.'Love'.'</a>';
	// Return the String
	die($results);
}
// creating Ajax call for WordPress
add_action( 'wp_ajax_nopriv_lovecounter_addvote', 'lovecounter_addvote' );
add_action( 'wp_ajax_lovecounter_addvote', 'lovecounter_addvote' );

add_filter( 'manage_edit-style_columns', 'lovecounter_extra_style_columns' );

function lovecounter_extra_style_columns( $columns ) {
  $columns[ 'lovecountercount' ] = __( 'Loves' );
  return $columns;
}

function lovecounter_style_column_row( $column ) {
	if ( $column != 'lovecountercount' )
	return;
	global $post;
	$post_id = $post->ID;
	$lovecountercount = get_post_meta($post_id, '_lovecountercount', true) != '' ? get_post_meta($post_id, '_lovecountercount', true) : '0';
	echo $lovecountercount;
}
add_action( 'manage_posts_custom_column', 'lovecounter_style_column_row', 10, 2 );

add_filter( 'manage_edit-style_sortable_columns', 'lovecounter_style_sortable_columns' );
function lovecounter_style_sortable_columns( $columns )
{
	$columns[ 'lovecountercount' ] = lovecountercount;
	return $columns;
}

add_action( 'load-edit.php', 'lovecounter_style_edit' );
function lovecounter_style_edit()
{
	add_filter( 'request', 'lovecounter_sort_styles' );
}

function lovecounter_sort_styles( $vars )
{
	if ( isset( $vars['post_type'] ) && 'style' == $vars['post_type'] )
	{
		if ( isset( $vars['orderby'] ) && 'lovecountercount' == $vars['orderby'] )
		{
			$vars = array_merge(
			$vars,
			array(
			'meta_key' => '_lovecountercount',
			'orderby' => 'meta_value_num'
			)
			);
		}
	}

    return $vars;
}

// Settings
add_action('admin_menu', 'lovecounter_create_menu');

function lovecounter_create_menu() {
  add_submenu_page('options-general.php','Love Counter','Love Counter','manage_options', __FILE__.'lovecounter_settings_page','lovecounter_settings_page');
}

function lovecounter_settings_page() {
  ?>
  <div class="wrap">
    <?php
    global $blog_id;
    //If form is posted and option has been submitted, update settings
    if( isset( $_POST['lovecounteroptionssubmit'] ) )
    {
      update_option( 'lovecounterlogincompulsory' , $_POST[ 'lovecounterlogincompulsory' ] );
    }
    ?>
    <div class="wrap">
      <form id='lovecountersettingform' method="post" action="">
        <h2><?php echo 'Love Counter Settings'; ?></h2>
        <p>
          <input type='radio' name='lovecounterlogincompulsory' value='yes' <?php if( get_option('lovecounterlogincompulsory') == 'yes' ) echo 'checked';?> > User must be logged in to 'Love'
        </p>
        <p>
          <input type='radio' name='lovecounterlogincompulsory' value='no' <?php if( get_option('lovecounterlogincompulsory') != 'yes' ) echo 'checked';?> > User can 'Love' anonymously
        </p>
        <p class="submit">
          <input type="submit" id="lovecounteroptionssubmit" name="lovecounteroptionssubmit" class="button-primary" value="<?php echo 'Save'; ?>" />
        </p>
      </form>
    </div>
  </div>
<?php }

function lovecounter_get_highest_voted_posts($numberofpost)
{
	$output = '';
	$the_query = new WP_Query( 'meta_key=_lovecountercount&post_type=style&orderby=meta_value_num&order=DESC&posts_per_page='.$numberofpost );
	// The Loop
	while ( $the_query->have_posts() ) : $the_query->the_post();
	  $output .= '<li>';
	  $output .= '<a href="'.get_permalink(). '" rel="bookmark">'.get_the_title().' ('.get_post_meta(get_the_ID(), '_lovecountercount', true).' loves)'.'</a> ';
	  $output .= '</li>';
	endwhile;
	wp_reset_postdata();
	return $output;
}

class LoveCounterTopVotedWidget extends WP_Widget {
	function LoveCounterTopVotedWidget() {
	  // widget actual processes
	  $widget_ops = array('classname' => 'LoveCounterTopVotedWidget', 'description' => 'Widget for top voted Posts.' );
	  $this->WP_Widget('LoveCounterTopVotedWidget','LoveCounterTopVotedWidget', $widget_ops);
  }
  function form($instance) {
	  // outputs the options form on admin
	  $defaults = array( 'title' => 'Top Loved Styles', 'numberofposts' => '5' );
	  $instance = wp_parse_args( (array) $instance, $defaults );
	  ?>
	  <p>
	    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo 'Title:'; ?></label>
	    <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
	  </p>
	  <p>
	    <label for="<?php echo $this->get_field_id( 'numberofposts' ); ?>"><?php echo 'Number of Posts'; ?></label>
	    <input id="<?php echo $this->get_field_id( 'numberofposts' ); ?>" name="<?php echo $this->get_field_name( 'numberofposts' ); ?>" value="<?php echo $instance['numberofposts']; ?>" class="widefat" />
	  </p>
	  <?php
  }
  function update($new_instance, $old_instance) {
	// processes widget options to be saved
	$instance = $old_instance;
	$instance['title'] = strip_tags( $new_instance['title'] );
	$instance['numberofposts'] = $new_instance['numberofposts'];
	  
    return $instance;
  }
  function widget($args, $instance) {
	// outputs the content of the widget
	extract($args);

	$title = apply_filters('widget_title', $instance['title'] );
	echo $before_widget;
	if ( $title )
	echo $before_title . $title . $after_title;
	echo '<ul>';
	echo lovecounter_get_highest_voted_posts($instance['numberofposts']);
	echo '</ul>';
	echo $after_widget;
  }
}

function lovecounter_widget_init() {
    // Check for the required API functions
    if ( !function_exists('register_widget') )
    return;

    register_widget('LoveCounterTopVotedWidget');
}
add_action('widgets_init', 'lovecounter_widget_init');