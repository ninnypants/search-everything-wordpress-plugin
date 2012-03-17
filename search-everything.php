<?php
/*
Plugin Name: Search Everything
Plugin URI: https://github.com/sproutventure/search-everything-wordpress-plugin/
Description: Adds search functionality without modifying any template pages: Activate, Configure and Search. Options Include: search highlight, search pages, excerpts, attachments, drafts, comments, tags and custom fields (metadata). Also offers the ability to exclude specific pages and posts. Does not search password-protected content.
Version: 6.9.3
Author: Dan Cameron of Sprout Venture
Author URI: http://sproutventure.com/
*/

/*
 This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, version 2.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */

if ( !defined('WP_CONTENT_DIR') )
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if (!defined('DIRECTORY_SEPARATOR'))
{
	if (strpos(php_uname('s'), 'Win') !== false )
	define('DIRECTORY_SEPARATOR', '\\');
	else
	define('DIRECTORY_SEPARATOR', '/');
}
define('SE_ABSPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

$SE = new SearchEverything();
//add filters based upon option settings

Class SearchEverything {

	var $logging = false;
	var $options;
	var $wp_ver23;
	var $wp_ver25;
	var $wp_ver28;

	function SearchEverything(){
		global $wp_version;
		$this->wp_ver23 = ($wp_version >= '2.3');
		$this->wp_ver25 = ($wp_version >= '2.5');
		$this->wp_ver28 = ($wp_version >= '2.8');
		$this->options = get_option('se_options');

		if (is_admin()) {
			include ( SE_ABSPATH  . 'views/options.php' );
			$SEAdmin = new se_admin();
		}
		
		/*
		@todo Add all filters and actions
		*/
	}


	// creates the list of search keywords from the 's' parameters.
	function se_get_search_terms()
	{
		global $wp_query, $wpdb;
		$s = isset($wp_query->query_vars['s']) ? $wp_query->query_vars['s'] : '';
		$sentence = isset($wp_query->query_vars['sentence']) ? $wp_query->query_vars['sentence'] : false;
		$search_terms = array();

		if ( !empty($s) )
		{
			// added slashes screw with quote grouping when done early, so done later
			$s = stripslashes($s);
			if ($sentence)
			{
				$search_terms = array($s);
			} else {
				preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
				$search_terms = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
			}
		}
		return $search_terms;
	}

	// add where clause to the search query
	function se_search_where($where, $wp_query){

		if(!$wp_query->is_search())
			return $where;

		global $wpdb;

		$searchQuery = $this->se_search_default();

		//add filters based upon option settings
		if ("Yes" == $this->options['se_use_tag_search'])
		{
			$searchQuery .= $this->se_build_search_tag();
		}
		if ("Yes" == $this->options['se_use_category_search'])
		{
			$searchQuery .= $this->se_build_search_categories();
		}
		if ("Yes" == $this->options['se_use_metadata_search'])
		{
			$searchQuery .= $this->se_build_search_metadata();
		}
		if ("Yes" == $this->options['se_use_excerpt_search'])
		{
			$searchQuery .= $this->se_build_search_excerpt();
		}
		if ("Yes" == $this->options['se_use_comment_search'])
		{
			$searchQuery .= $this->se_build_search_comments();
		}
		if ("Yes" == $this->options['se_use_authors'])
		{
			$searchQuery .= $this->se_search_authors();
		}
		if ($searchQuery != '')
		{
			$where = preg_replace('#\(\(\(.*?\)\)\)#', '(('.$searchQuery.'))', $where);

		}
		if ($this->options['se_exclude_posts_list'] != '')
		{
			$where .= $this->se_build_exclude_posts();
		}
		if ($this->options['se_exclude_categories_list'] != '')
		{
			$where .= $this->se_build_exclude_categories();

		}
		$this->se_log("global where: ".$where);
		return $where;
	}
	
	/*
	@todo add default search function
	*/

	
	/*
	@todo Rework the way logging works or maybe add some sort of output while debugging
	*/
	// Logs search into a file
	function se_log($msg)
	{

		if ($this->logging)
		{
			$fp = fopen( SE_ABSPATH . "logfile.log","a+");
			if ( !$fp )
			{
				echo 'unable to write to log file!';
			}
			$date = date("Y-m-d H:i:s ");
			$source = "search_everything plugin: ";
			fwrite($fp, "\n\n".$date."\n".$source."\n".$msg);
			fclose($fp);
		}
		return true;
	}

	/*
	@todo Could this be replaced with the normal groupby function like what is used in the normal query
	*/
	//Duplicate fix provided by Tiago.Pocinho
	function se_distinct($query)
	{
		global $wp_query, $wpdb;
		if (!empty($wp_query->query_vars['s']))
		{
			if (strstr($query, 'DISTINCT'))
			{}
			else
			{
				$query = str_replace('SELECT', 'SELECT DISTINCT', $query);
			}
		}
		return $query;
	}

	//search pages (except password protected pages provided by loops)
	function se_search_pages($where){
		global $wp_query, $wpdb;
		/*
		@todo add page serach query rules
		*/
	}

	/*
	@todo search excerpts
	*/


	/*
	@todo search drafts
	*/

	/*
	@todo search attachments
	*/

	/*
	@todo search comments
	*/

	/*
	@todo search authors
	*/

	/*
	@todo search meta
	*/

	/*
	@todo more general search taxonomies function maybe with selectable taxonomies
	*/

	/*
	@todo exclude pages or posts
	*/

	// create the Categories exclusion query
	function se_build_exclude_categories()
	{
		global $wp_query, $wpdb;
		
		/*
		@todo Is this handled by the taxonomy function
		*/

		return $excludeQuery;
	}

	/*
	@todo general join function
	*/

	// Highlight the searched terms into Title, excerpt and content
	// in the search result page.
	function se_postfilter($postcontent)
	{
		global $wp_query, $wpdb;
		$s = $wp_query->query_vars['s'];
		// highlighting
		if (is_search() && $s != '')
		{
			$highlight_color = $this->options['se_highlight_color'];
			$highlight_style = $this->options['se_highlight_style'];
			$search_terms = $this->se_get_search_terms();
			foreach ( $search_terms as $term )
			{
				if (preg_match('/\>/', $term))
        			continue; //don't try to highlight this one
					$term = preg_quote($term);

				if ($highlight_color != '')
				$postcontent = preg_replace(
					'"(?<!\<)(?<!\w)(\pL*'.$term.'\pL*)(?!\w|[^<>]*>)"i'
					, '<span class="search-everything-highlight-color" style="background-color:'.$highlight_color.'">$1</span>'
					, $postcontent
					);
				else
				$postcontent = preg_replace(
					'"(?<!\<)(?<!\w)(\pL*'.$term.'\pL*)(?!\w|[^<>]*>)"i'
					, '<span class="search-everything-highlight" style="'.$highlight_style.'">$1</span>'
					, $postcontent
					);
			}
		}
		return $postcontent;
	}
} // END