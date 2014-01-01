<?php
/*
// docs: http://en.wikipedia.org/w/api.php

http://en.wikipedia.org/w/api.php?action=query&format=json&prop=info|extracts|coordinates|pageimages|categories&inprop=displaytitle|url&explaintext&exintro&pithumbsize=2000&cllimit=500&clshow=!hidden&titles=Washington,_D.C.

// DBpedia http://dbpedia.org/About

http://dbpedia.org/data/Washington,_D.C..json
*/

class Scriblio_Authority_bGeo_Wikipedia
{

	public $cache_ttl_fail = 1013; // prime numbers make good TTLs
	public $cache_ttl_success = 0; // indefinitely
	public $errors = array();
	public $language = 'en';

	public function __construct()
	{
	}

	public function cache_key( $component, $query )
	{
		return md5( $language . $component . $query );
	}

	public function cache_ttl( $code )
	{

		if ( 200 != $code )
		{
			return $cache_ttl_fail;
		}

		return $cache_ttl_success;
	}

	public function get( $page )
	{
		// docs: http://en.wikipedia.org/w/api.php
		// sample url: http://en.wikipedia.org/w/api.php?action=query&redirects&format=json&prop=info|extracts|coordinates|pageimages|categories&inprop=displaytitle|url&explaintext&exintro&pithumbsize=3000&cllimit=500&clshow=!hidden&titles=Washington,_D.C.

		if ( ! $api_result = wp_cache_get( $this->cache_key( 'get', $page ), $this->id_base ) )
		{
			$url = sprintf( 
				'http://%1$s.wikipedia.org/w/api.php?action=query&redirects&format=json&prop=info|extracts|coordinates|pageimages|categories&inprop=displaytitle|url&explaintext&exintro&pithumbsize=3000&cllimit=500&clshow=!hidden&titles=%2$s',
				$this->language,
				urlencode( $page )
			);
	
			$api_result = wp_remote_get( $url );

			wp_cache_set( $this->cache_key( 'get', $page ), $api_result, $this->id_base, $this->cache_ttl( wp_remote_retrieve_response_code( $api_result ) ) );
		}

		// did the API return a valid response code?
		if ( 200 != wp_remote_retrieve_response_code( $api_result ) )
		{
			$this->errors[] = new WP_Error(
				'api_response_error', 'The endpoint returned a non-200 response. This response may have been cached.',
				wp_remote_retrieve_response_code( $api_result ) . wp_remote_retrieve_response_message( $api_result )
			);
			return FALSE;
		}

		// did we get a result that makes sense?
		$api_result = json_decode( wp_remote_retrieve_body( $api_result ) );
		if ( ! isset( $api_result->query->pages ) )
		{
			$this->errors[] = new WP_Error( 'api_response_error', 'The endpoint didn\'t return a meaningful result. This response may have been cached.', $api_result );
			return FALSE;
		}

		// extract the page info and return
		$api_result = (array) $api_result->query->pages;
		return $this->parse_page( reset( $api_result ) );
	}

	public function parse_page( $page )
	{
		// capture the URL encoded title
		$page->encodedtitle = preg_replace( '#http.*/wiki/#', '', $page->fullurl );

		// clean up the categories
		$page->parsedcategories = array();
		foreach ( $page->categories as $category )
		{
			if ( 14 != $category->ns )
			{
				continue;
			}

			// strip the "Category:" prefix
			$category->title = substr( $category->title, 9 );

			// the unfiltered category name
			$page->parsedcategories[] = $category->title;

			// removing preposition clauses that make for overly-specific categories
			if ( 
				( $less_specific = preg_replace( '/\s(established|in|on|of)\s.*/', '', $category->title ) ) &&
				$less_specific != $category->title
			)
			{
				$page->parsedcategories[] = $less_specific;			

				// and what the hell, capture the detail as a category as well
				$detail = preg_replace( '/.*\s(in|on|of)\s/', '', $category->title );
				if ( ! is_numeric( $detail ) )
				{
					$page->parsedcategories[] = $detail;
				}

			}

			// make sure we capture all "Populated places"
			if ( preg_match( '/^Populated /', $category->title ) )
			{
				$page->parsedcategories[] = 'Populated places';			
			}
		}

		$page->parsedcategories = array_unique( $page->parsedcategories );

		return $page;
	}

	public function search( $search )
	{
		// docs: http://en.wikipedia.org/w/api.php
		// sample url: http://en.wikipedia.org/w/api.php?action=opensearch&format=json&limit=100&search=north%20america

		if ( ! $api_result = wp_cache_get( $this->cache_key( 'search', $search ), $this->id_base ) )
		{
			$url = sprintf( 
				'http://%1$s.wikipedia.org/w/api.php?action=opensearch&format=json&limit=100&search=%2$s',
				$this->language,
				urlencode( $search )
			);

			$api_result = wp_remote_get( $url );

			wp_cache_set( $this->cache_key( 'search', $search ), $api_result, $this->id_base, $this->cache_ttl( wp_remote_retrieve_response_code( $api_result ) ) );
		}

		if ( 200 != wp_remote_retrieve_response_code( $api_result ) )
		{
			$this->errors[] = new WP_Error(
				'api_response_error', 'The endpoint returned a non-200 response. This response may have been cached.', 
				wp_remote_retrieve_response_code( $api_result ) . wp_remote_retrieve_response_message( $api_result )
			);
			return FALSE;
		}

		// did we get a result that makes sense?
		$api_result = json_decode( wp_remote_retrieve_body( $api_result ) );

		if ( ! is_array( $api_result[1] ) )
		{
			$this->errors[] = new WP_Error( 'api_response_error', 'The endpoint didn\'t return a meaningful result. This response may have been cached.', $api_result );
			return FALSE;
		}

		// extract and return the result
		return $api_result[1];
	}
}
