<?php

class Scriblio_Authority_bGeo_DBpedia
{

	public $cache_ttl_fail = 1013; // prime numbers make good TTLs
	public $cache_ttl_success = 0; // indefinitely
	public $errors = array();
//	public $id_base = 'scriblio-authority-bgeo-dbpedia';
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
		// docs: http://dbpedia.org/About
		// sample url: http://dbpedia.org/data/Washington,_D.C..json

		if ( ! $api_result = wp_cache_get( $this->cache_key( 'get', $page ), $this->id_base ) )
		{
			$url = sprintf( 
				'http://dbpedia.org/data/%1$s.json',
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

return $api_result;

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

		return $page;
	}
}
