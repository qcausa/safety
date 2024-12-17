== Description ==

Using this plugin you can add a self contained [download_page] shortcode to your site to list downloads, categories, tags, and show info pages about each of your resources. Requires the new Download Monitor with custom post type support.

= Shortcode Usage =

To start, simply add the [download_page] shortcode to one of your WordPress pages. Ensure that it is not wrapped with any formatting (you can switch the visual editor to HTML mode to check this).

The following arguments can be added to the shortcode to customise the page:

* `format=pa` - The format used to output downloads in the lists. By default this is content-download-pa.php.
* `show_search=true` - Show the search box on the frontpage. Set to true or false.
* `show_featured=true` - Show the featured downloads box on the frontpage. Set to true or false.
* `show_tags=true` - Show the tags box on the frontpage. Set to true or false.
* `featured_limit=4` - How many featured downloads to list. Defaults to 4.
* `featured_format=pa-thumbnail` - The format to use to output featured downloads. By default this is content-download-pa-thumbnail.php.
* `category_limit=4` - How many downloads to show per category on the frontpage. Defaults to 4.
* `front_orderby=download_count` - The order of downloads on the frontpage. Can be set to title, download_count, or date.
* `default_orderby=title` - The order of downloads in lists. Can be set to title, download_count, or date.
* `posts_per_page=20` - The number of downloads to show in lists per page. Defaults to 20.

__Example__ Show the download page with no tags, serach and 10 featured downloads:

`[download_page show_search=false show_tags=false featured_limit=10]`

= Template overrides =

Files in the 'templates' folder included with this plugin can be overridden via your theme. To do this, take a file from `download-monitor-page-addon/templates/` and place it in `yourtheme/download-monitor/`.

= Styles =

This plugin comes with basic styles which work with default WordPress themes. You may need to restyle the page to match other themes in which case you should add the styles to your theme's CSS files.

If you need to turn off Page Addon styles for any reason, including the above, you can do so with a snippet in your theme functions.php file:

`add_action( 'wp_enqueue_scripts', 'dequeue_dlm_page_frontend_scripts' );
function dequeue_dlm_page_frontend_scripts(){
	wp_dequeue_style( 'dlm-page-addon-frontend' );
}`

= Usage =

See https://www.download-monitor.com/documentation/ for documentation.

= Terms & Conditions =

Please read the latest Terms & Conditions here: [https://www.download-monitor.com/terms-conditions/](https://www.download-monitor.com/terms-conditions/)