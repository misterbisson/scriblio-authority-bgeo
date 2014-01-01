<?php

class Scriblio_Authority_bGeo_DBpedia
{

	public $cache_ttl_fail = 1013; // prime numbers make good TTLs
	public $cache_ttl_success = 0; // indefinitely
	public $errors = array();
	public $id_base = 'scriblio-authority-bgeo-dbpedia';
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
//				'http://%1$s.dbpedia.org/data/%2$s.json',
				'http://dbpedia.org/data/%2$s.json',
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

		if ( ! is_object( $api_result ) )
		{
			$this->errors[] = new WP_Error( 'api_response_error', 'The endpoint didn\'t return a meaningful result. This response may have been cached.', $api_result );
			return FALSE;
		}

		// extract the page info and return
		return $this->parse_page( $api_result, $page );
	}

	public function parse_page( $relations, $page )
	{

		$page = 'http://dbpedia.org/resource/' . $page;

		$relations = $relations->$page;

		$output = array();
		foreach ( (array) $relations as $k => $v )
		{
			$output[ $k ][] = $v;
		}

		ksort( $output );

		$whitelist = array(
			'http://dbpedia.org/property/capital' => FALSE,
			'http://dbpedia.org/property/city' => FALSE,
			'http://dbpedia.org/ontology/country' => FALSE,
			'http://dbpedia.org/ontology/county' => FALSE,
			'http://dbpedia.org/ontology/isPartOf' => FALSE,
			'http://dbpedia.org/property/settlementType' => FALSE,
			'http://dbpedia.org/property/state' => FALSE,
			'http://dbpedia.org/property/subdivisionType' => FALSE,
			'http://xmlns.com/foaf/0.1/name' => FALSE,
			'http://xmlns.com/foaf/0.1/nick' => FALSE,
		);

		return array_intersect_key( $output, $whitelist );
	}
}
