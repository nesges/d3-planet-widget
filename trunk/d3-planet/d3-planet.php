<?php
/*
 * Plugin Name: Planet D3
 * Description: Widget mit den letzten Artikeln des D&D-Feeds von Planet D3
 * Plugin URI: https://planet.dnddeutsch.de
 * Version: 0.2
 * Author: Thomas Nesges
 * Author URI: https://www.dnddeutsch.de
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text domain: d3_planet_widget
 * Domain Path: /i18n
*/

function d3_planet_load_widget() {
    register_widget( 'd3_planet_widget' );
}
add_action( 'widgets_init', 'd3_planet_load_widget' );

class d3_planet_widget extends WP_Widget {
    
    var $rss_url = 'https://planet.dnddeutsch.de/p/i/?a=rss';
    var $cachetime = 15; // min
    var $default_title = '';
    var $default_count = 10;
    
    var $banners = [
            'dark' => 'Dunkel',
            'light' => 'Hell',
            'none' => 'kein Banner',
        ];
    
    var $feeds = [
            'dnd' => 'D&amp;D-Feeds',
            'events' => 'Events',
            'info' => 'Info',
            'shops' => 'Shops',
            'nondnd' => 'Non-D&amp;D-Feeds',
        ];
    
    function __construct() {
        parent::__construct(
            'd3_planet_widget', 
            __('D3 Planet Widget', 'd3_planet_widget'), 
            array(
                'description' => __( 'Letzte Artikel aus Planet D3', 'd3_planet_widget' ), 
            ) 
        );
    }
  
    // Creating widget front-end
    public function widget( $args, $instance ) {
        global $wpdb;
        
        $title   = apply_filters( 'widget_title', $instance['title'] );
        $count   = $instance['count'];
        $srcname = $instance['srcname'];
        $banner  = $instance['banner'];
        $feed    = $instance['feed'];
        
        echo '<link rel="stylesheet" href="'.plugin_dir_url( __FILE__ ).'css/d3-planet.css">';
        
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        
        if($banner == 'dark' || $banner == 'light') {
            echo '<a href="https://planet.dnddeutsch.de"><img class="d3-planet-title-image" src="'.plugin_dir_url( __FILE__ ) .'images/planet_d3_banner_'.$banner.'.png" alt="Planet D3"></a>';
        }
        
        if ( ! empty( $title ) )
            echo $args['before_title'] . '<span class="d3-planet-title-text">' . $title . '</span>' . $args['after_title'];
        
        
        echo '<ul class="d3_planet_items">';
        $cache = plugin_dir_path( __FILE__ )."cache/planet_".$feed."_".$count."_".$srcname.".json";
        if(file_exists($cache)) {
            // print cached content; ajax.php may find new content which overwrites this later
            $cached = json_decode(file_get_contents($cache));
            print $cached->html;
        }
        echo '</ul>';
        echo '<ul><li class="d3-planet-item d3-planet-home-image"><a href="https://planet.dnddeutsch.de">'.$this->feeds[$feed].' von <span class="d3-planet-home-text">PLANET D3</span></a></li></ul>';


	    // ajax.php checks for new content. ignore if it's crc is unchanged
        // loading via ajax is necessary to bypass wordpress caching
        ?>
        <script type="text/javascript" >
	        jQuery(document).ready(function($) {
	        	jQuery.getJSON('<?php print plugin_dir_url( __FILE__ )."ajax.php?p=$feed&count=$count&srcname=$srcname" ?>')
	        	    .done(function(data) {
	        		    // console.log('d');
	        		    // if(data.crc && data.crc > 0 && data.html && data.crc != '<?= $cached->crc ?>') {
	        		    //     console.log('a', data.crc, '<?= $cached->crc ?>');
	        		    //     jQuery('#d3_planet_items').html(data.html);
	        		    // } else {
	        		    //     console.log('c', data.crc, '<?= $cached->crc ?>');
	        		    // }
	        		    jQuery('#<?php echo $this->id ?> .d3_planet_items').html(data.html);
	        	    })
	        	    .error(function(err) {
	        	        console.log(err);
                    });
	        });
	    </script>
	    <?php
        
        echo $args['after_widget'];
    }
         
    // Widget Backend 
    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        } else {
            $title = '';
        }
        $count = $instance[ 'count' ];
        $srcname = $instance[ 'srcname' ];
        $banner = $instance[ 'banner' ];
        $feed = $instance[ 'feed' ];
        
        // Widget admin form
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'feed' ); ?>"><?php _e( 'Feed:' ); ?></label> 
        <select id="<?php echo $this->get_field_id( 'feed' ); ?>" name="<?php echo $this->get_field_name('feed'); ?>">
            <?php
                foreach($this->feeds as $feed_key => $feed_name) {
                    print '<option value="'.$feed_key.'" '.($feed==$feed_key ? 'selected="selected"' : '').'>'.$feed_name.'</option>';
                }
            ?>
        </select><br><br>
        <label for="<?php echo $this->get_field_id( 'banner' ); ?>"><?php _e( 'Planet D3-Banner:' ); ?></label> 
        <select id="<?php echo $this->get_field_id( 'banner' ); ?>" name="<?php echo $this->get_field_name('banner'); ?>">
            <?php
                foreach($this->banners as $banner_key => $banner_name) {
                    print '<option value="'.$banner_key.'" '.($banner==$banner_key ? 'selected="selected"' : '').'>'.$banner_name.'</option>';
                }
            ?>
        </select><br><br>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Titel (optional):' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ) ? esc_attr( $title ) : $this->default_title; ?>" />
        <br><br>
        <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Anzahl Beitr&auml;ge:' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" value="<?php echo esc_attr( $count ) ? esc_attr( $count ) : $this->default_count ; ?>" />
        <br><br>
        <label for="<?php echo $this->get_field_id( 'srcname' ); ?>"><?php _e( 'Feednamen:' ); ?></label><br>
        <label for="d3planet_srcname_1"><input type="radio" name="<?php echo $this->get_field_name( 'srcname' ); ?>" id="d3planet_srcname_1" value="1" <?php echo esc_attr($srcname) ? 'checked="checked"' : "" ?>/> anzeigen</label>
        <label for="d3planet_srcname_0" style="margin-left:1em"><input type="radio" name="<?php echo $this->get_field_name( 'srcname' ); ?>" id="d3planet_srcname_0" value="0" <?php echo !esc_attr($srcname) ? 'checked="checked"' : "" ?>/> ausblenden</label>
        </p>
        <!--br>
        <hr>
        <small><strong>CSS-Klassen:</strong><ul>
        <li>.d3-planet-title-image: Planet D3-Banner</li>
        <li>.d3-planet-title-image-light: Planet D3-Banner (Hell)</li>
        <li>.d3-planet-title-image-dark: Planet D3-Banner (Dunkel)</li>
        <li>.d3-planet-title-image-none: Planet D3-Banner (kein Banner)</li>
        <li>.d3-planet-title-text: Titel</li>
        <li>.d3-planet-item: Listen-Element</li>
        <li>.d3-planet-home-image: Planet D3-Link unten</li>
        <li>.d3-planet-home-text: Planet D3-Link unten</li>
        </ul></small-->
        <?php 
    }
     
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['feed']       = ( ! empty( $new_instance['feed'] ) ) ? strip_tags( $new_instance['feed'] ) : '';
        $instance['banner']     = ( ! empty( $new_instance['banner'] ) ) ? strip_tags( $new_instance['banner'] ) : '';
        $instance['title']      = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['count']      = ( ! empty( $new_instance['count'] ) ) ? strip_tags( $new_instance['count'] ) : '';
        $instance['srcname']    = ( ! empty( $new_instance['srcname'] ) ) ? strip_tags( $new_instance['srcname'] ) : '';
        return $instance;
    }
}

?>