=== LogCloud ===
Contributors: kingrat
Tags: tags, cloud, shortcode, widget
Requires at least: 2.3
Tested up to: 2.8.5
Stable tag: trunk

LogCloud provides a shortcode that generates a tag cloud with logarithm scaled font sizes.

== Description ==

LogCloud provides a shortcode that generates a tag cloud with logarithm scaled font sizes.

LogCloud displays a tag cloud much like the built-in Wordpress tag cloud, except that instead 
of scaling the font sizes linearly, the sizes are scaled using a logarithm scale.

If you use your tags with approximately equal frequency, the normal linear scale works just 
fine. However, if you tag your posts such that just a few tags predominate, the linear scale 
results in one or two very large looking tags in your cloud, and everything else will use a 
couple of font sizes at the lower end of the scale. A log scale cloud will result in a more 
even distribution for this situation. 

== Installation ==

Unpack logcloud to your plugins directory (preferably using handy-dandy built-in install tools), then activate it.

To use logcloud, insert the [logcloud] shortcode anywhere you want a tag cloud to be.

The following attributes may be added to [logcloud]:

* smallest - smallest font size to use (default 8)
* largest - largest font size to use (default 22)
* unit - CSS unit for sizing fonts (default 'pt')
* number - number of tags to display (default 45). use 0 to display all tags.
* format - 'flat' or 'list' (default 'flat')
* orderby - 'name' or 'count' (default 'name')
* order - 'ASC', 'DESC' or 'RAND' (default 'ASC')
* exclude, include - tag slugs to exclude or include
* link - 'view' or 'edit' (default 'view')
* min_usage - minimum count for a tag to be displayed (default 0)
* usecolors - whether to scale colors or not (default 0)
* mincolor - hex color code for 'minimum' color (default '#000000')
* maxcolor - hex color code for 'maximum' color (default '#FFFFFF')

LogCloud also adds a widget that can be used in your sidebar if you have widgets enabled.
Options are the same as above, except the link option is not available.

== Changelog ==

= 1.0 =
initial version; cut-and-paste of wp_tag_cloud with log scaling replacing linear scaling and using a shortcode

= 2.0 =
added min_usage argument

= 3.0 =
added widget

= 4.0 =
added scaling by color

= 4.1 =
fixed a divide by zero bug

== Screenshots ==

1. A linear scale tag cloud where 'fiction' has over twice as many uses as the second most used tag.
2. The same set of tags scaled using logarithms.
