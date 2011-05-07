<?php
/*
Plugin Name: Open Graph WP Implementation
Plugin URI: http://www.piyushmishra.com/plugins/open-graph.html
Description: Implements the Open Graph Protocol on a WordPress installation. Can be used by other plugins as a dependency.
Author: Piyush Mishra
Author URI: http://www.piyushmishra.com/
Version: 1.2
Text Domain: open-graph
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/*  Copyright 2010  Piyush Mishra  (email : me@piyushmishra.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/** Call piyushmishra_open_graph_head on end of wp head to add Open Graph meta tags */
add_action( 'wp_head', "piyushmishra_open_graph_head" );

require_once('admin-menu.php');

add_action( 'fb_merge_home', 'open_graph_home');

function open_graph_home($array)
{
?>
<div id="poststuff" class="metabox-holder">
	<div class="stuffbox">
		<h3>WordPress Facebook Comment Integration </h3>
		<div class="inside">	
			<p>
			The Open Graph WP plugin converts your WordPress installation into a social object. It implemets the <a href="http://ogp.me" target="_blank">Open Graph Protocol</a>. </p>
			<p>
			To add a like box to your theme, open your theme editor and paste the following code
			</p>
			<code style="display: block; padding:10px; text-align:center;">
				<textarea style="width:100%; margin:0 auto; height=150px;  padding:10px;" onclick="select()" readonly="readonly" selected="selected">if( function_exists( 'open_graph_like' ) ):
	open_graph_like();
endif;</textarea>
			</code>
			<p>
			<strong>To customize your like button, please install "<a class="thickbox" title="More info" href="<?php echo admin_url( 'plugin-install.php?tab=plugin-information&plugin=fb-merge&TB_iframe=true&width=640' )?>">FB Merge</a>" plugin.</strong>
			</p>
		</div>
	</div>
</div>
<?php
}

/**
 * Fetches an instance of piyushmishra_open_graph
 * And runs it. echoes Meta information
 */
function piyushmishra_open_graph_head() 
{
	$og = open_graph_instance();
	do_action('open_graph_head_pre');
	$og->setup();
	do_action('open_graph_head_post');
	$og->echo_meta($og->get_og_data());
}

/**
 * Stores and returns a single instance of piyushmishra_open_graph making it an effective singleton
 * @return piyushmishra_open_graph instance
 */
function open_graph_instance() 
{
	return piyushmishra_open_graph::instance();
}
/**
 *  Insert's the Like button where you want
 */
function open_graph_like()
{
	// if there is a customizable like available use that!
	if( function_exists( 'fb_merge_like' ) )
	{
		fb_merge_like();
		return;
	}
	else
	{
		$og = open_graph_instance();
		$url=rawurlencode( $og->get_og_data( "og:url" ) );?> 
		<div style="padding: 2px;">
			<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo $url; ?>&amp;layout=standard&amp;show_faces=false&amp;width=450&amp;action=like&amp;colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:450px; height:24px">
			</iframe>
		</div>
	<?php
	}
}
/*
 * Class wrapper to keep wp clean
 * For more info on open graph protocols visit http://ogp.me
 */
final class piyushmishra_open_graph {
	private $hometype			= 'blog';
	private $posttype			= 'article';
	private $default_img		= null;
	private $ogdata			= array();
	private static $instance	= null;
	
	/**
	 * Private Constructor for a singleton
	 */
	private function __construct()
	{
		/* Load default image path from options  */
		$this->default_img = plugins_url('open_graph_protocol_logo.png',__FILE__);
	} 
	/**
	 * Allows changing of default image
	 */
	public function set_default_img( $url )
	{
		$this->default_img = $url;
	}
	/**
	 * Allows changing of hometype via action
	 */
	public function set_home_type( $type )
	{
		$this->hometype = $type;
	}
	/**
	 * Spits out meta information to browser as we ask it to
	 */
	public function echo_meta( $meta_array )
	{
		foreach( $meta_array as $key=> $value )
			echo '<meta property="',$key,'" content="',$value,'">',"\n";
	}
	
	/**
	 * Checks for the key and return's a single value / full array
	 * @return mixed array for 'all' and string for particular opengraph data
	 */
	public function get_og_data( $which_one = 'all' )
	{
		if( $which_one === 'all' )
			return $this->ogdata;
		elseif( array_key_exists( $which_one, $this->ogdata ) )
			return $this->ogdata[$which_one];
	}
	/**
	 * Sets / Updates variables for echoing meta data
	 * Note: it doesn't warn on over-writes
	 */
	public function set_og_data( $key, $value )
	{
		$this->ogdata[$key] = $value;
	}
	/**
	 * Fetches the image URL associated with a post or gives out the default image url
	 */
	public function get_image_url()
	{
		global $post;
		$childargs = array
		(
			'post_type'			=> 'attachment',
			'post_mime_type'	=> 'image',
			'post_parent'		=> $post->ID
		);

		if( $images = get_children( $childargs ) ) 
		{
			foreach( $images as $image )
				return array_shift( wp_get_attachment_image_src( $image->ID, 'medium' ) );
		}
		return $this->default_image_url();
	}
	/**
	 * Fetches the default image url 
	 */
	public function default_image_url()
	{
		return $this->default_img;
	}
	/**
	 * Sets up all meta data
	 */
	public function setup()
	{
	/* Set all basic required parameters */
		$this->ogdata['og:title']	= get_bloginfo( 'name' ); //We will edit this as we get to know more about the current page
		$this->ogdata['og:image']	= $this->get_image_url();
		$this->ogdata['og:type']	= $this->posttype;
		$this->ogdata['og:url']		= get_bloginfo( 'url' );
		
		/* Set readily available recommended additional Parameter */
		$this->ogdata['og:description']	= get_bloginfo( 'description' );
		$this->ogdata['og:site_name']	= $this->ogdata['og:title']; //As title currently is the blog's name assign that
		
		/* Set default addition for page title */
		$addition=null;
		
		/* Set og:type to 'blog' if its home page */
		if( is_home() )
		{
			$this->ogdata['og:type'] = $this->hometype;
		}
		
		/* Set url as the page/post permalink and add addition for the title */
		if( is_single() || is_page() )
		{
			$addition = get_the_title();
			$this->ogdata['og:url'] = get_permalink();
		}
		
		/* Set url, description (if present) and provide addition for category page title */
		if( is_category() )
		{
			$this->ogdata['og:url'] = get_category_link( get_query_var( 'cat' ) );
			
			/* Load category (term) object into a variable */
			$cat = get_category( get_query_var( 'cat' ), false );
			if( strlen( $cat->description ) )
				$this->ogdata['og:description'] = $cat->description;
			$addition = ucwords( $cat->cat_name );
		}
		/* Set url, description (if present) and provide addition for tag page title */
		if( is_tag() )
		{
			$this->ogdata['og:url']=get_tag_link( get_query_var( 'tag_id' ) );
			
			/* Load tag (term) object into a variable */
			$tag = get_tag( get_query_var( 'tag_id' ), false );
			if( strlen( $tag->description ) )
				$this->ogdata['og:description'] = $tag->description;
			$addition = ucwords( $tag->name );
		}
		/* If addition to title has been set by now, add it */
		if( ! is_null( $addition ) )
			$this->ogdata['og:title'] = $addition ." | ". $this->ogdata['og:site_name'];
	}
	/**
	 * Gives the singleton instance.
	 */
	public static function instance()
	{
		if ( ! isset ( self::$instance ) )
		{
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
	}
	/* Prevent cloning */
	private function __clone(){	}
}

