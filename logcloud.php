<?php
/*
Plugin Name: Log Cloud
Plugin URI: http://www.kingrat.us/plugins/logcloud
Description: Provides functions and widgets for a log scale tag cloud
Author: Philip Weiss
Author URI: http://www.kingrat.us
Version: 4.1
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
*/

/*
This plugin is a modified version of code licensed under the GPL version 2.0 by wordpress.org.
Code by Philip Weiss may be used under either the GPL version 2.0 or at your option, the GPL version 3.0.
*/

/* History:
 * 1.0 initial version; cut-and-paste of wp_tag_cloud with log scaling replacing linear scaling and using a shortcode
 * 2.0 added min_usage argument
 * 3.0 added widget
 * 4.0 added scaling by color to scaling by size
 *    4.1 added fix for a divide by zero error (thanks to Bryan Blaisdell for the fix suggestion)
*/

/**
 * Display tag cloud.
 *
 * The text size is set by the 'smallest' and 'largest' arguments, which will
 * use the 'unit' argument value for the CSS text size unit. The 'format'
 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
 * 'format' argument will separate tags with spaces. The list value for the
 * 'format' argument will format the tags in a UL HTML list. The array value for
 * the 'format' argument will return in PHP array type format.
 *
 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC'.
 *
 * The 'number' argument is how many tags to return. By default, the limit will
 * be to return the top 45 tags in the tag cloud list.
 *
* The 'topic_count_text_callback' argument is a function, which, given the count
 * of the posts  with that tag, returns a text for the tooltip of the tag link.
 * @see default_topic_count_text
 *
 * The 'exclude' and 'include' arguments are used for the {@link get_tags()}
 * function. Only one should be used, because only one will be used and the
 * other ignored, if they are both set.
 *
 * @since 2.3.0
 *
 * @param array|string $args Optional. Override default arguments.
 * @return array Generated tag cloud, only if no failures and 'array' is set for the 'format' argument.
 */

 /* function name renamed from wp_tag_cloud to logcloud_tag_cloud to avoid name collisions */

function logcloud_tag_cloud( $args = '' ) {
    $defaults = array(
        'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
        'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
        'exclude' => '', 'include' => '', 'link' => 'view', 'min_usage' => '0'
    );

    $args = wp_parse_args( $args, $defaults );

    $tags = get_tags( array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

    if ( empty( $tags ) ) {
        return;
    }
    $min_usage = intval($args['min_usage']);

    foreach ( $tags as $key => $tag ) {
        if ($tag->count >= $min_usage) {
            $_tags[$key] = $tag;

            $link = ( 'edit' == $args['link'] ) ? get_edit_tag_link( $tag->term_id ) : get_tag_link( $tag->term_id );
            if ( is_wp_error( $link ) ) {
                return false;
            }

            $_tags[ $key ]->link = $link;
            $_tags[ $key ]->id = $tag->term_id;
        }
    }

    $tags = $_tags;

/* calls function logcloud_generate_tag_cloud rather than wp_generate_tag_cloud */
    $return = logcloud_generate_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args

    $return = apply_filters( 'wp_tag_cloud', $return, $args );

    return $return;

}

/**
 * Generates a tag cloud (heatmap) from provided data.
 *
 * The text size is set by the 'smallest' and 'largest' arguments, which will
 * use the 'unit' argument value for the CSS text size unit. The 'format'
 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
 * 'format' argument will separate tags with spaces. The list value for the
 * 'format' argument will format the tags in a UL HTML list. The array value for
 * the 'format' argument will return in PHP array type format.
 *
 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC' or
 * 'RAND'.
 *
 * The 'number' argument is how many tags to return. By default, the limit will
 * be to return the entire tag cloud list.
 *
 * The 'topic_count_text_callback' argument is a function, which given the count
 * of the posts  with that tag returns a text for the tooltip of the tag link.
 * @see default_topic_count_text
 *
 *
 * @todo Complete functionality.
 * @since 2.3.0
 *
 * @param array $tags List of tags.
 * @param string|array $args Optional, override default arguments.
 * @return string
 */

 /* function renamed from wp_generate_tag_cloud to avoid name collisions */
function logcloud_generate_tag_cloud( $tags, $args = '' ) {
    global $wp_rewrite;
    $defaults = array(
        'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
        'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
        'exclude' => '', 'include' => '', 'link' => 'view', 'min_usage' => '0',
        'usecolors' => false, 'mincolor' => '#000000', 'maxcolor' => '#FFFFFF',
        'topic_count_text_callback' => 'default_topic_count_text'
    );

    if ( !isset( $args['topic_count_text_callback'] ) && isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
        $body = 'return sprintf (
            __ngettext('.var_export($args['single_text'], true).', '.var_export($args['multiple_text'], true).', $count),
            number_format_i18n( $count ));';
        $args['topic_count_text_callback'] = create_function('$count', $body);
    }

    $args = wp_parse_args( $args, $defaults );

    extract( $args );

    if ( empty( $tags ) ) {
        return;
    }

    // SQL cannot save you; this is a second (potentially different) sort on a subset of data.
    if ( 'name' == $orderby ) {
        uasort( $tags, create_function('$a, $b', 'return strnatcasecmp($a->name, $b->name);') );
    } else {
        uasort( $tags, create_function('$a, $b', 'return ($a->count < $b->count);') );
    }

    if ( 'DESC' == $order ) {
        $tags = array_reverse( $tags, true );
    }   elseif ( 'RAND' == $order ) {
        $keys = array_rand( $tags, count( $tags ) );
        foreach ( $keys as $key ) {
            $temp[$key] = $tags[$key];
        }
        $tags = $temp;
        unset( $temp );
    }

    if ( $number > 0 ) {
        $tags = array_slice($tags, 0, $number);
    }

    $counts = array();
    foreach ( (array) $tags as $key => $tag ) {
        $counts[ $key ] = $tag->count;
    }

    $min_count = min( $counts );
    $max_count = max( $counts );
    $min_max_difference = $max_count - $min_count;

    $minlog = log($min_count);
    $maxlog = log($max_count);

    $logscale = $maxlog - $minlog;

    $font_spread = $largest - $smallest;
    if ( $font_spread < 0 ) {
        $font_spread = 1;
    }

    $a = array();

    $rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

    foreach ( $tags as $key => $tag ) {
        $count = $counts[ $key ];
        $tag_link = '#' != $tag->link ? clean_url( $tag->link ) : '#';
        $tag_id = isset($tags[ $key ]->id) ? $tags[ $key ]->id : $key;
        $tag_name = $tags[ $key ]->name;

        $scale_result = $min_max_difference
          ? (log($count)-$minlog) / $logscale
          : 0.5;
        $font_scale_result = round($font_spread * $scale_result);

        $tagout = "<a href='$tag_link' class='tag-link-$tag_id' title='"
            . attribute_escape( $topic_count_text_callback( $count ) )
            . "'$rel style='font-size: "
            . ( $smallest + $font_scale_result )
            . "$unit;";
        if ( $usecolors ) {
            $tagout .= "color:"
            . logcloud_getColorByScale ($scale_result, $mincolor, $maxcolor)
            . ";";
        }
        $tagout .= "'>$tag_name</a>";
        $a[] = $tagout;
    }

    switch ( $format ) :
    /* no array version for this plugin */
    case 'list' :
        $return = "<ul class='wp-tag-cloud'>\n\t<li>";
        $return .= join( "</li>\n\t<li>", $a );
        $return .= "</li>\n</ul>\n";
        break;
    default :
        $return = join( "\n", $a );
        break;
    endswitch;

    return apply_filters( 'wp_generate_tag_cloud', $return, $tags, $args );
}

// [logcloud]
function logcloud_shortcode($atts) {

    return logcloud_tag_cloud($atts);
}


/**
 * Display log cloud widget.
 *
 * @param array $args Widget arguments.
 */
function logcloud_widget_logcloud($args) {
    extract($args);
    $options = get_option('widget_logcloud');
    $title = empty($options['title'])
        ? __('Tags')
        : apply_filters('widget_title', $options['title']);

    echo $before_widget;
    echo $before_title . $title . $after_title;
    echo logcloud_tag_cloud($options);
    echo $after_widget;
}

/**
 * Manage Log Cloud widget options
 *
 */
function logcloud_widget_logcloud_control() {

    // Options that can be set in the widget
    $fields = array(
        'smallest' , 'largest' , 'unit' , 'number' ,
        'format' , 'orderby' , 'order' , 'exclude',
        'include' , 'min_usage', 'title',
        'usecolors', 'mincolor', 'maxcolor'
    );

    // Get actual options
    $options = $newoptions = get_option('widget_logcloud');
    if ( !is_array($options) ) {
        $options = $newoptions = array();
    }

    // Post to new options array
    if ( isset($_POST['log-cloud-submit']) ) {
        foreach ( (array) $fields as $field ) {
            $value = strip_tags(stripslashes($_POST['log-cloud-'.$field]));
            if (empty($value)) {
                unset($newoptions[$field]);
            } else {
                $newoptions[$field] = $value;
            }
        }
    }

    // Update if new options
    if ( $options != $newoptions ) {
        $options = $newoptions;
        update_option('widget_logcloud', $options);
    }

    // Prepare data for display
    foreach ( (array) $fields as $field ) {
        ${$field} = attribute_escape($options[$field]);
    }

    ?>
<p>
    <label for="log-cloud-title"><?php _e('Title:'); ?> <input type="text" class="widefat" id="log-cloud-title" name="log-cloud-title" value="<?php echo $title; ?>" /></label>
    <label for="log-cloud-smallest"><?php _e('Smallest font:'); ?> <input type="text" class="widefat" id="log-cloud-smallest" name="log-cloud-smallest" value="<?php echo $smallest; ?>" /></label>
    <label for="log-cloud-largest"><?php _e('Largest font:'); ?> <input type="text" class="widefat" id="log-cloud-largest" name="log-cloud-largest" value="<?php echo $largest; ?>" /></label>
    <label for="log-cloud-unit"><?php _e('Font units:'); ?> <input type="text" class="widefat" id="log-cloud-unit" name="log-cloud-unit" value="<?php echo $unit; ?>" /></label>
    <label for="log-cloud-usecolors"><?php _e('Scale colors?:'); ?> <input type="checkbox" class="widefat" id="log-cloud-usecolors" name="log-cloud-usecolors" <?php if ($usecolors) echo "checked='checked'"; ?> /></label>
    <label for="log-cloud-mincolor"><?php _e('Min color (#RGB):'); ?> <input type="text" class="widefat" id="log-cloud-mincolor" name="log-cloud-mincolor" value="<?php echo $mincolor; ?>" /></label>
    <label for="log-cloud-maxcolor"><?php _e('Max color (#RGB):'); ?> <input type="text" class="widefat" id="log-cloud-maxcolor" name="log-cloud-maxcolor" value="<?php echo $maxcolor; ?>" /></label>
    <label for="log-cloud-number"><?php _e('Num. tags:'); ?> <input type="text" class="widefat" id="log-cloud-number" name="log-cloud-number" value="<?php echo $number; ?>" /></label>
    <label for="log-cloud-format"><?php _e('Format:'); ?>
        <select class="widefat" id="log-cloud-format" name="log-cloud-format">
            <option value="flat" <?php if ($format == "flat") { echo "selected='selected'" ; } ?> >flat</option>
            <option value="list" <?php if ($format == "list") { echo "selected='selected'" ; } ?> >list</option>
        </select>
    </label>
    <label for="log-cloud-orderby"><?php _e('Order by:'); ?>
        <select class="widefat" id="log-cloud-orderby" name="log-cloud-orderby">
            <option value="name"  <?php if ($orderby == "name")  { echo "selected='selected'" ; } ?> >name</option>
            <option value="count" <?php if ($orderby == "count") { echo "selected='selected'" ; } ?> >count</option>
        </select>
    </label>
    <label for="log-cloud-order"><?php _e('Sort:'); ?>
        <select class="widefat" id="log-cloud-order" name="log-cloud-order">
            <option value="ASC"  <?php if ($order == "ASC")  { echo "selected='selected'" ; } ?> >ASC</option>
            <option value="DESC" <?php if ($order == "DESC") { echo "selected='selected'" ; } ?> >DESC</option>
            <option value="RAND" <?php if ($order == "RAND") { echo "selected='selected'" ; } ?> >RAND</option>
        </select>
    </label>
    <label for="log-cloud-exclude"><?php _e('Exclude slugs:'); ?> <input type="text" class="widefat" id="log-cloud-exclude" name="log-cloud-exclude" value="<?php echo $exclude; ?>" /></label>
    <label for="log-cloud-include"><?php _e('Include slugs:'); ?> <input type="text" class="widefat" id="log-cloud-include" name="log-cloud-include" value="<?php echo $include; ?>" /></label>
    <label for="log-cloud-min_usage"><?php _e('Min. count to include:'); ?> <input type="text" class="widefat" id="log-cloud-min_usage" name="log-cloud-min_usage" value="<?php echo $min_usage; ?>" /></label>
    <input type="hidden" name="log-cloud-submit" id="log-cloud-submit" value="1" />
</p>
<?php
}

function logcloud_init() {

    add_shortcode('logcloud', 'logcloud_shortcode');

    $widget_ops = array('classname' => 'widget_logcloud', 'description' => __( "Your most used tags in cloud format (log scaled)") );
    wp_register_sidebar_widget('logcloud', __('Log Cloud'), 'logcloud_widget_logcloud', $widget_ops);
    wp_register_widget_control('logcloud', __('Log Cloud'), 'logcloud_widget_logcloud_control' );
}

add_action('init', 'logcloud_init', 1);

    /**
     * This is pretty filthy. Doing math in hex is much too weird. It's more likely to work, this way!
     * Provided from UTW. Thanks.
     *
     * @param integer $scale_color
     * @param string $min_color
     * @param string $max_color
     * @return string
     */
function logcloud_getColorByScale($scale_color, $min_color, $max_color) {
    // $scale_color = $scale_color / 100;

    $minr = hexdec(substr($min_color, 1, 2));
    $ming = hexdec(substr($min_color, 3, 2));
    $minb = hexdec(substr($min_color, 5, 2));

    $maxr = hexdec(substr($max_color, 1, 2));
    $maxg = hexdec(substr($max_color, 3, 2));
    $maxb = hexdec(substr($max_color, 5, 2));

    $r = dechex(intval((($maxr - $minr) * $scale_color) + $minr));
    $g = dechex(intval((($maxg - $ming) * $scale_color) + $ming));
    $b = dechex(intval((($maxb - $minb) * $scale_color) + $minb));

    if (strlen($r) == 1) $r = '0'.$r;
    if (strlen($g) == 1) $g = '0'.$g;
    if (strlen($b) == 1) $b = '0'.$b;

    return '#'.$r.$g.$b;
}