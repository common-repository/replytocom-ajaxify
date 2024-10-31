<?php
/*
Plugin Name: ReplyToCom Ajaxify
Plugin URI: http://www.seocom.es
Description: Removes the ReplyToCom parameter from the comments querystring. This action favor the SEO optimizations.
Author: David Garcia
Version: 1.0.3
*/

class replytocom
{
	var $_slug;
	var $_slugfile;

	var $plugin_url;
	var $marker;

	function replytocom()
	{
		$this->__construct();
	}	
	
	function __construct()
	{
		$this->_slug = basename(dirname(__FILE__));
		$this->_slugfile = basename(__FILE__);

		$this->plugin_url = plugins_url( basename( dirname(__FILE__) ) );
		$this->marker = 'ReplyToCom';


		if ( is_admin() )
		{
			add_action('admin_menu', array(&$this, 'admin_menu') );
			add_filter('plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
			add_action('admin_notices', array( $this, 'show_messages' ) );
			register_activation_hook( __FILE__, array( $this, 'install' ) );
			register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));
			return;
		}

		if ( defined('DOING_AJAX') )
		{
			return;
		}
		
		add_filter('comment_reply_link', array(&$this,'comment_reply_link'), 10, 4 );
		add_filter('init', array(&$this,'init'));
	}

	function init()
	{	
		wp_register_script('replytocom_js', $this->plugin_url . '/replytocom.js', array('jquery'), '0.0.1' );
		wp_enqueue_script('replytocom_js');
	}

	function install()
	{
		$option = get_option('replytocom_ajaxify_config');
		if ( !empty($option['redirect']) )
		{
			$this->save_redirection(false);
		}
	}

	function uninstall()
	{
		$this->save_redirection(true);
		delete_option( 'replytocom_ajaxify_config' );
	}

	function show_messages()
	{
		if ( isset($_POST['replytocom_ajaxify']) )
		{
			if ( !$this->is_htaccess_writable() )
			{
				$msg = __( 'Your .htaccess file is not writable. You should solve this to change the plugin options.</a>.' );
				echo '<div class="error"><p>' . $msg . '</p></div>';
			}
		}
	}

	function plugin_action_links( $links, $file )
	{
		if ( $file == plugin_basename( dirname(__FILE__). '/' . $this->_slugfile ) )
		{
			$links[] = '<a href="' . admin_url( 'admin.php?page=' . $this->_slugfile ) . '">'.__( 'Settings' ).'</a>';
		}

		return $links;
	}

	function admin_menu()
	{
		add_submenu_page( 'options-general.php', 'ReplyToCom Ajaxify', 'ReplyToCom Ajaxify', 10, $this->_slugfile, array(&$this, 'options_page') );
	}
	
	function options_page()
	{	
		if ( isset($_POST['replytocom_ajaxify']) )
		{
			unset($_POST['replytocom_ajaxify']['submit']);
			
			update_option( 'replytocom_ajaxify_config', $_POST['replytocom_ajaxify'] );
			print '<div id="message" class="updated fade"><p><strong>'.__('Options updated.', $this->_slug ).'</strong> <a href="'.get_bloginfo('url').'">'.__('View site', $this->_slug ) . ' &raquo;</a></p></div>';

			$option = get_option('replytocom_ajaxify_config');
			if ( empty($option['redirect']) )
			{
				$this->save_redirection(true);
			} else {
				$this->save_redirection(false);
			}
			
		} else {
			$option = get_option('replytocom_ajaxify_config');
		}

		$redirect_checked = '';

		if ( !empty($option['redirect']) )
		{
			$redirect_checked = ' checked="checked"';
		}

		print '
		<div class="wrap">
		<h2>ReplyToCom Ajaxify Settings</h2>

		<form method="post" action="http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'">
		<table class="form-table">
		<tr valign="top">
			<th scope="row">'.__('Do a 301 redirect when not safe replytocom querystrings are used', $this->_slug ).'</th>
			<td>
			<input type="checkbox" id="replytocom_ajaxify_redirect" name="replytocom_ajaxify[redirect]" value = "1" '.$redirect_checked.' />
			</td>
		</tr>
		</table>
		<p class="submit"><input type="submit" value="Submit &raquo;" class="button button-primary" name="replytocom_ajaxify[submit]" /></p>
		</form>

		</div>
		';
	}

	function comment_reply_link( $link, $args, $comment, $post )
	{
		if ( stripos( $link, 'replytocom=') !== false )
		{
			if (preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $link, $regs))
			{
				$parts = parse_url($regs[2]);
				$parts['query'] .= '&_safereplytocom=yes';
				$regs[2]=$this->unparse_url($parts);
			
				$encoded = base64_encode($regs[2]);
				$link = str_replace( $regs[0], 'href="#" data-replytocom="'.$encoded.'"', $link);
			}
		}

		return $link;
	}

	function unparse_url($parsed_url)
	{
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
		$pass     = ($user || $pass) ? "$pass@" : ''; 
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
		
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}
	
	function is_htaccess_writable()
	{
		$home_path = $this->get_home_path();
		$htaccess_file = $home_path.'.htaccess';
	
		if ((!file_exists($htaccess_file) && is_writable($home_path)) || is_writable($htaccess_file))
		{
			return true;
		}
		return false;
	}

	function save_redirection( $clear = false )
	{
		$home_path = $this->get_home_path();
		$htaccess_file = $home_path.'.htaccess';
	
		// If the file doesn't already exist check for write access to the directory and whether we have some rules.
		// else check for write access to the file.
		if ($this->is_htaccess_writable())
		{
			if ( apache_mod_loaded('mod_rewrite', true) )
			{
				$wprules = $this->extract_from_markers( $htaccess_file, 'WordPress' );
				
				$rules = '
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{QUERY_STRING} !_safereplytocom=yes
RewriteCond %{QUERY_STRING} ^(.*)&?replytocom=[0-9]*&?(.*)
RewriteRule (.*) http://%{HTTP_HOST}/$1?%1%2 [R=301,L]
</IfModule>
';
				$rules = explode( "\n", $rules );
				
				if ( $clear )
				{
					$this->remove_marker( $htaccess_file, $this->marker );
				} else {
					$this->remove_marker( $htaccess_file, 'WordPress' );
					$this->insert_with_markers( $htaccess_file, $this->marker, $rules );
					$this->insert_with_markers( $htaccess_file, 'WordPress', $wprules );
				}
			}
		}	
	}
	
	function get_home_path()
	{
		$home = get_option( 'home' );
		$siteurl = get_option( 'siteurl' );
		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
			$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
			$home_path = trailingslashit( $home_path );
		} else {
			$home_path = ABSPATH;
		}
	
		return str_replace( '\\', '/', $home_path );
	}
	
	function insert_with_markers( $filename, $marker, $insertion )
	{
		if (!file_exists( $filename ) || is_writeable( $filename ) )
		{
			if (!file_exists( $filename ) ) {
				$markerdata = '';
			} else {
				$markerdata = explode( "\n", implode( '', file( $filename ) ) );
			}
	
			if ( !$f = @fopen( $filename, 'w' ) )
				return false;
	
			$foundit = false;
			if ( $markerdata )
			{
				$state = true;
				foreach ( $markerdata as $n => $markerline ) {
					if (strpos($markerline, '# BEGIN ' . $marker) !== false)
						$state = false;
					if ( $state ) {
						if ( $n + 1 < count( $markerdata ) )
							fwrite( $f, "{$markerline}\n" );
						else
							fwrite( $f, "{$markerline}" );
					}
					if (strpos($markerline, '# END ' . $marker) !== false) {
						fwrite( $f, "# BEGIN {$marker}\n" );
						if ( is_array( $insertion ))
							foreach ( $insertion as $insertline )
								fwrite( $f, "{$insertline}\n" );
						fwrite( $f, "# END {$marker}\n" );
						$state = true;
						$foundit = true;
					}
				}
			}
			if (!$foundit)
			{
				fwrite( $f, "\n# BEGIN {$marker}\n" );
				foreach ( $insertion as $insertline )
					fwrite( $f, "{$insertline}\n" );
				fwrite( $f, "# END {$marker}\n" );
			}
			fclose( $f );
			return true;
		} else {
			return false;
		}
	}
	function remove_marker( $filename, $marker )
	{
        if (!file_exists( $filename ) || is_writeable( $filename ) )
        {
            if (!file_exists( $filename ) ) {
				return '';
            } else {
				$markerdata = explode( "\n", implode( '', file( $filename ) ) );
            }

            $f = fopen( $filename, 'w' );
            $foundit = false;
            if ( $markerdata )
            {
                $state = true;
                foreach ( $markerdata as $n => $markerline )
                {
                    if (strpos($markerline, '# BEGIN ' . $marker) !== false)
                            $state = false;
                    if ( $state )
                    {
                            if ( $n + 1 < count( $markerdata ) )
                                    fwrite( $f, "{$markerline}\n" );
                            else
                                    fwrite( $f, "{$markerline}" );
                    }
                    if (strpos($markerline, '# END ' . $marker) !== false) {
                            $state = true;
                    }
                }
            }
            return true;
        } else {
			return false;
        }
	}
	
	function extract_from_markers( $filename, $marker )
	{
		$result = array ();
	
		if (!file_exists( $filename ) ) {
			return $result;
		}
	
		if ( $markerdata = explode( "\n", implode( '', file( $filename ) ) ));
		{
			$state = false;
			foreach ( $markerdata as $markerline ) {
				if (strpos($markerline, '# END ' . $marker) !== false)
					$state = false;
				if ( $state )
					$result[] = $markerline;
				if (strpos($markerline, '# BEGIN ' . $marker) !== false)
					$state = true;
			}
		}
	
		return $result;
	}	
	
}

$replytocom = new replytocom();
