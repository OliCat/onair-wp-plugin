<?php
/**
 * Plugin Name: TB On-Air displayer
 * Plugin URI: http://cause-commune.fm/
 * Description: Affiche ce qui passe à l'antenne
 * Version: 0.1.3
 * Author: Thomas Bernard
 * Author URI: https://www.linkedin.com/pub/thomas-bernard/1/943/199
 * Requires at least: 3.9
 * Network: true
 * License: GPL2
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// ajoute le shortcode [tb-onair]
add_shortcode('tb-onair', 'tb_show_onair');

// methode pour overwrite le shortcode [qt-onair]
add_action('wp_loaded', 'overwrite_qt_shortcode');
function overwrite_qt_shortcode() {
	remove_shortcode( 'qt-onair' );
	add_shortcode('qt-onair', 'tb_show_onair');
}


function tb_show_onair($attrs) {
	// use set_transient() / get_transient() to cache result
	$data = get_transient( 'tb_onair_data' );
	if ($data === false) {
		$resp = wp_remote_get( 'https://airtime-live-info-V2-url' );
		$body = wp_remote_retrieve_body( $resp );
		$data = json_decode( $body );
		// cache for 60 seconds
		set_transient( 'tb_onair_data', $data, 60);
	}

	$show_name = $data->shows->current->name;
	$show_description = $data->shows->current->description;
	$show_url = $data->shows->current->url;
	$show_time = substr($data->shows->current->starts, 11, 5);
	$show_time_end = substr($data->shows->current->ends, 11, 5);
	$show_id = $data->shows->current->genre;	// genre => id dans le wordpress

	/*
	$show_id = 0;//$event['show_id'][0];
	 */

	$show_time_d = $show_time;
	$show_time_end_d = $show_time_end;
	// 12 hours format
	if(get_theme_mod('QT_timing_settings', '12') == '12'){
		$show_time_d = date("g:i a", strtotime($show_time_d));
		$show_time_end_d = date("g:i a", strtotime($show_time_end_d));
	}

	$date = new DateTime('now', new DateTimeZone('Europe/Paris'));
	//$day_of_week = intval(date( 'w' )); // 0 dimanche, 6 samedi
	$day_of_week = intval($date->format('w'));
	//$hour_of_day = intval(date( 'G' )); // 24h format hour without leading 0
	$hour_of_day = intval($date->format('G'));
	// semaine : 12h=>17h, 21h=>4h
	// vendredi 21h au samedi 16h, dimanche 14h 22h
	$fm = false;
	if ($day_of_week == 0) // dimanche
		$fm = ($hour_of_day >= 14) && ($hour_of_day < 22);
	else if ($day_of_week == 6)  // samedi
		$fm = ($hour_of_day < 16);
	else {
		if ((($hour_of_day >= 12) && ($hour_of_day < 17))
		   || ($hour_of_day >= 21))
		   $fm = true;
		else if ($day_of_week != 1)
		   $fm = ($hour_of_day < 4);
	}

	ob_start();
?>
	<!-- ON AIR SHOW ========================= -->
      <div id="qtonairhero" class="qt-slick-opacity-fx qt-item qt-content-primary">
       <div class="qt-part-archive-item qt-part-schedule-onair-large qt-negative">
	<div class="qt-item-header">
         <div class="qt-header-mid qt-vc">
         <div class="don-btn">
 
    <a  href="https://cause-commune.fm/faire-un-don/">   <i class="dripicons-heart"></i><span class="text-don">Faire un don</span> </a>

</div> 
          <div class="qt-vi">
           <h5 class="qt-caption-med qt-capfont hide-on-small-and-down">
            <span class="onair">
<?php
	//echo esc_attr__("Now On Air","onair2");
	if ($fm) {
		echo "à l'antenne / FM & DAB+";
	} else {
		echo "Streaming / dab+";
	}
 ?>
            </span>
           </h5>
           <hr class="qt-spacer-s">
           <h1 class="qt-title qt-capfont">
            <a href="<?php echo get_the_permalink($show_id); ?>" class="qt-text-shadow"><?php echo $show_name;//get_the_title($show_id); ?></a>
           </h1>
           <h4 class="qt-capfont">
	    <?php echo esc_attr($show_description/*get_post_meta($show_id,"subtitle", true)*/); ?>
           </h4>

<h4><!-- social media ??? --></h4>

<p class="qt-small">
    <?php echo esc_attr($show_time_d); ?> <i class="dripicons-arrow-thin-right"></i> <?php echo esc_attr($show_time_end_d); ?>
</p>

 <hr class="qt-spacer-s hide-on-med-and-down">
           <p class="hide-on-med-and-down"><a href="<?php echo $show_url;//get_the_permalink($show_id); ?>" class="qt-btn qt-btn-l qt-btn-primary " tabindex="0"><i class="dripicons-media-play"></i></a></p>
          </div>
         </div>
         <?php if (has_post_thumbnail($show_id)){ ?>
         <div class="qt-header-bg" data-bgimage="<?php echo get_the_post_thumbnail_url($show_id, 'qantumthemes-xl' ); ?>" <?php if($parallax == 'true') : ?> data-parallax="1" data-speed="3" <?php endif; ?> >
         </div>
         <?php } ?>
        </div>
       </div>
      </div>
      <!-- ON AIR SHOW END ========================= -->

<?php
	//return "maintenant à l'antenne!!!";
	return ob_get_clean();
}


/*
 * Inutile car on utiliser les "transient"

// Register a cron
// This should be registered during activation and cleaned
// during desactivation
register_activation_hook(__FILE__, 'tb_onair_activation');
register_deactivation_hook(__FILE__, 'tb_onair_deactivation');
function tb_onair_activation() {
	if (! wp_next_scheduled( 'tb_onair_hourly' )) {
		wp_schedule_event(time(), 'hourly', 'tb_onair_hourly_event');
	}
}

function tb_onair_deactivation() {
	wp_clear_scheduled_hook('tb_onair_hourly_event');
}

add_action('tb_onair_hourly_event', 'tb_update_onair_data');

// this will be executed houlry
function tb_update_onair_data() {
	// TODO
}
 */
