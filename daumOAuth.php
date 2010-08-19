<?php
/*
 * Azki (azki@azki.org) http://azki.org/
 *
 * Basic lib to work with Daum OAuth.
 *
 * http://github.com/azki/Daum-OAuth
 * Version: 1.0.0
 * Last Update: 2010-08-04
 * 
 * Daum OAuth page: https://apis.daum.net/oauth/main/welcome
 *
 * Code based on:
 * Fire Eagle code - http://github.com/myelin/fireeagle-php-lib
 * twitterlibphp - http://github.com/poseurtech/twitterlibphp
 * twitterOAuth.php - http://abrah.am/
 * 
 */

/* Load OAuth lib. You can find it at http://oauth.net */
require_once('OAuth.php');

/**
 * Daum OAuth class
 */
class DaumOAuth {/*{{{*/
	/* Contains the last HTTP status code returned */
	private $http_status;

	/* Contains the last API call */
	private $last_api_call;

	/* Set up the API root URL */
	public static $TO_API_ROOT = "https://apis.daum.net";

	/**
	 * Set API URLS
	 */
	function requestTokenURL() { return self::$TO_API_ROOT.'/oauth/requestToken'; }
	function authorizeURL() { return self::$TO_API_ROOT.'/oauth/authorize'; }
	function accessTokenURL() { return self::$TO_API_ROOT.'/oauth/accessToken'; }

	/**
	 * Debug helpers
	 */
	function lastStatusCode() { return $this->http_status; }
	function lastAPICall() { return $this->last_api_call; }

	/**
	 * construct DaumOAuth object
	 * 
	 * @example
	 * new DaumOAuth(CONSUMER_KEY, CONSUMER_SECRET, CALLBACK_URL);
	 * new DaumOAuth(CONSUMER_KEY, CONSUMER_SECRET, CALLBACK_URL, OAUTH_TOKEN, OAUTH_TOKEN_SECRET); 
	 */
	function __construct($consumer_key, $consumer_secret, $callback_url, $oauth_token = NULL, $oauth_token_secret = NULL) {/*{{{*/
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, $callback_url);
		if (!empty($oauth_token) && !empty($oauth_token_secret)) {
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret, $callback_url);
		} else {
			$this->token = NULL;
		}
	}/*}}}*/

	/**
	 * Get a request_token from Daum
	 */
	function getRequestToken() {/*{{{*/
		$r = $this->oAuthRequest($this->requestTokenURL(), array('oauth_callback'=>$this->consumer->callback_url), 'GET');
		$token = $this->oAuthParseResponse($r);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret'], $this->consumer->callback_url);
		return $token;
	}/*}}}*/

	/**
	 * Parse a URL-encoded OAuth response
	 */
	function oAuthParseResponse($responseString) {/*{{{*/
		$r = array();
		foreach (explode('&', $responseString) as $param) {
			$pair = explode('=', $param, 2);
			if (count($pair) != 2) continue;
			$r[urldecode($pair[0])] = urldecode($pair[1]);
		}
		return $r;
	}/*}}}*/

	/**
	 * Get the authorize URL
	 */
	function getAuthorizeURL($token) {/*{{{*/
		if (is_array($token)) $token = $token['oauth_token'];
		return $this->authorizeURL() . '?oauth_token=' . $token;
	}/*}}}*/

	/**
	 * Exchange the request token and secret and verifier for an access token and secret, to sign API calls.
	 */
	function getAccessToken($verifier) {/*{{{*/
		//$r = $this->oAuthRequest($this->accessTokenURL());
		$r = $this->oAuthRequest($this->accessTokenURL(), array('oauth_callback'=>$this->consumer->callback_url, 'oauth_verifier'=>$verifier), 'GET');
		$token = $this->oAuthParseResponse($r);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret'], $this->consumer->callback_url);
		return $token;
	}/*}}}*/

	/**
	 * Format and sign an OAuth / API request
	 */
	function oAuthRequest($url, $args = array(), $method = NULL) {/*{{{*/
		if (empty($method)) {
			$method = 'GET';
		}
		$req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
		$req->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
		case 'GET':
			return $this->http($req->to_url());
		case 'POST':
			return $this->http($req->get_normalized_http_url(), $req->to_postdata());
		}
	}/*}}}*/

	/**
	 * Make an HTTP request
	 */
	function http($url, $post_data = null) {/*{{{*/
		$ch = curl_init();
		if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		if (isset($post_data)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		$response = curl_exec($ch);
		$this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->last_api_call = $url;
		curl_close ($ch);
		return $response;
	}/*}}}*/
}/*}}}*/