<?php
include_once('OAuthClientException.php');

if (!function_exists('curl_init'))
{
  throw new OAuthClientException('CURL PHP extension is required.');
}

/**
 * OAuthClient class
 *
 * Requirement:
 * 1. curl, http://php.net/manual/en/book.curl.php
 * 2. OAuth, if using OAuth 1.0, http://php.net/manual/en/book.oauth.php
 */
abstract class OAuthClient
{
  /**
   * Constructor
   *
   * @param array $oauth_config
   */
  public function __construct($oauth_config)
  {
    foreach ($this->oauthConfigKeys as $config)
    {
      if (empty($oauth_config[$config]))
      {
        $message = sprintf('%s is required.', $config);
        $this->log($message);
        throw new OAuthClientException($message);
      }
    }
  }

  ////////////////////////////////////////////////////////////////////////
  // Abstract Methods
  ////////////////////////////////////////////////////////////////////////

  /**
   * Get the authorization url
   *
   * For OAuth 1.0, the return value is an array, e.g. array(TOKEN_SECRET, AUTHORIZATION_URL),
   * where TOKEN_SECRET is a string for later authentication, usually, it will be saved in session.
   * and AUTHORIZATION_URL is a string for user to be redirected to.
   *
   * For OAuth 2.0, the return value is a string, AUTHORIZATION_URL
   *
   * @param array $params For OAuth 2.0, e.g. array('scope' => '', 'state' => '', $redirect => '')
   * @return array|string
   */
  abstract public function getAuthorizationUrl($params = NULL);

  /**
   * Exchange for the access token
   *
   * For OAuth 1.0, the $token parameter is the oauth token, usually from the url,
   * and the $secret_or_redirect_url parameter is the oauth token secret returned in getAuthorizationUrl(),
   * usually saved in the session
   * The return value is an array, e.g. array('oauth_token' => 'TOKEN', 'oauth_token_secret' => 'SECRET')
   *
   * For OAuth 2.0, the $code parameter is the authorization code, usually from the url,
   * and the $secret_or_redirect_url parameter is the redirect url used in getAuthorizationUrl()
   * The return value is a string.
   *
   * @param string $token
   * @param string $secret_or_redirect_url
   * @return array|string
   */
  abstract public function exchangeAccessToken($token, $secret_or_redirect_url = '');

  /**
   * Set the token
   *
   * @param string $token
   * @param string $secret For OAuth 2.0, you may not set this parameter
   */
  abstract public function setToken($token, $secret = '');

  /**
   * Fetch the resource
   *
   * @param string $api
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return mixed
   */
  abstract public function fetch($api, $params = array(), $method = 'POST', $headers = array());

  /**
   * Get the http info in the last response
   *
   * @return array
   */
  abstract public function getLastResponseInfo();

  /**
   * Get the last response
   *
   * @return string
   */
  abstract public function getLastResponse();

  /**
   * Get the last response headers
   *
   * @return string
   */
  abstract public function getLastResponseHeaders();

  ////////////////////////////////////////////////////////////////////////
  // Common Methods
  ////////////////////////////////////////////////////////////////////////

  /**
   * Set the Logger
   * 
   * @param callable $logger The logger should look like function ($message, $level)
   */
  public function setLogger($logger)
  {
    if (is_callable($logger))
    {
      $this->logger = $logger;
    }
  }

  /**
   * Log the message
   *
   * @param string $message
   * @param string $level
   */
  protected function log($message, $level = self::LOG_LEVEL_ERROR)
  {
    if ($this->logger)
    {
      call_user_func($this->logger, $message, $level);
    }
  }

  /**
   * Get an array from a json string or query string
   *
   * @param string $str
   * @return array|string
   */
  protected function decodeJSONOrQueryString($str)
  {
    $decoded = json_decode($str, TRUE);
    if ((version_compare(PHP_VERSION, '5.3.0') >= 0 && json_last_error() !== JSON_ERROR_NONE)
      || $decoded === NULL)
    {
      if (strpos($str, '=') !== FALSE)
      {
        $decoded = array();
        parse_str($str, $decoded);
      }
      else
      {
        $decoded = $str;
      }
    }

    return $decoded;
  }

  ////////////////////////////////////////////////////////////////////////
  // Properties
  ////////////////////////////////////////////////////////////////////////

  // The OAuth version
  public $oauthVersion;

  // The logger
  protected $logger;

  const OAUTH_VERSION1 = '1.0';
  const OAUTH_VERSION2 = '2.0';

  const LOG_LEVEL_DEBUG = 'debug';
  const LOG_LEVEL_ERROR = 'error';
}
