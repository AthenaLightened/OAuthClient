<?php
// we need OAuth extension for OAuth1
if (!class_exists('OAuth'))
{
  throw new OAuthClientException('OAuth extension is required.');
}

/**
 * An OAuth 1.0 class, wrapper for OAuth class in the OAuth extension
 */
class OAuth1Client implements IOAuthClient
{
  /**
   * Constructor
   *
   * @param array $oauth_config
   * <code>
   * array(
   *   'consumer_key' => 'YOUR CONSUMER KEY',
   *   'consumer_secret' => 'YOUR CONSUMER SECRET',
   *   'request_token_url' => 'URL FOR GETTING THE REQUEST TOKEN',
   *   'authorization_url' => 'URL FOR AUTHORIZATION',
   *   'access_token_url' => 'URL FOR GETTING THE ACCESS TOKEN',
   *   'api_url' => 'URL FOR CALLING THE API'
   * )
   * </code>
   * @param OAuthClient $wrapper
   */
  public function __construct($oauth_config, $wrapper)
  {
    foreach (self::$oauthConfigKeys as $config)
    {
      if (empty($oauth_config[$config]))
      {
        $message = sprintf('%s is required for OAuth %s.', $key, $version);
        $wrapper->log($message, OAuthClient::LOG_LEVEL_ERROR);

        throw new OAuthClientException($message);
      }
    }

    $this->oauth = new OAuth($oauth_config['consumer_key'], $oauth_config['consumer_secret']);
    $this->oauthConfig = $oauth_config;
    $this->wrapper = $wrapper;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorizationUrl($scope = '', $state = '', $redirect = '')
  {
    $session = &$this->wrapper->getSession();

    $url = $this->oauthConfig['request_token_url'];
    $params = array();
    try
    {
      $request_token_info = $this->oauth->getRequestToken($url);
      $session['token_secret'] = $request_token_info['oauth_token_secret'];
      $params['oauth_token'] = $request_token_info['oauth_token'];
    }
    catch (Exception $err)
    {
      $this->wrapper->log(sprintf('Failed to get request token at %s.', $url), OAuthClient::LOG_LEVEL_ERROR);
      throw new OAuthClientException($err->getMessage());
    }

    return $this->oauthConfig['authorization_url'] . '?' . http_build_query($params);
  }

  /**
   * Exchange for the access token
   *
   * @param string $code OAuth token for OAuth 1.0, Authorization Code for OAuth 2.0
   * @return bool
   */
  public function exchangeAccessToken($code)
  {
    $session = &$this->wrapper->getSession();

    if (!isset($session['token_secret']))
    {
      $this->wrapper->log('Missing token secret. Please call getAuthorizationUrl first.', OAuthClient::LOG_LEVEL_ERROR);
      return FALSE;
    }

    $this->oauth->setToken($code, $session['token_secret']);
    $url = $this->oauthConfig['access_token_url'];
    try
    {
      $access_token_info = $oauth->getAccessToken($url);
    }
    catch (Exception $err)
    {
      $this->wrapper->log(sprintf('Failed to get access token at %s.', $url), OAuthClient::LOG_LEVEL_ERROR);
      throw new OAuthClientException($err->getMessage());
    }

    unset($session['token_secret']);
    $session['token'] = $access_token_info['oauth_token'];
    $session['secret'] = $access_token_info['oauth_token_secret'];
    return TRUE;
  }

  /**
   * Set the token
   *
   * @param string $token
   * @param string $secret For OAuth 2.0, you may not set this parameter
   */
  public function setToken($token, $secret = '')
  {

  }

  /**
   * Fetch the resource
   *
   * @param string $api
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return mixed
   */
  public function fetch($api, $params = array(), $method = 'POST', $headers = array())
  {
  }

  /**
   * Get the http info in the last response
   *
   * @return array
   */
  public function getLastResponseInfo()
  {
    return $this->oauth->getLastResponseInfo();
  }

  /**
   * Get the last response
   *
   * @return string
   */
  public function getLastResponse()
  {
    return $this->oauth->getLastResponse();
  }

  /**
   * Get the last response headers
   *
   * @return string
   */
  public function getLastResponseHeaders()
  {
    return $this->oauth->getLastResponseHeaders();
  }

  /**
   * Magic functions to pass to the wrapped OAuthClient instance
   *
   * @param string $name
   * @param array $arguments
   */
  public function __call($name, $arguments)
  {
    return call_user_func_array(array($this->oauth, $name), $arguments);
  }

  ////////////////////////////////////////////////////////////////////////
  // Properties
  ////////////////////////////////////////////////////////////////////////

  // Config keys for OAuth 1.0
  private static $oauthConfigKeys = array(
    'consumer_key', 'consumer_secret', 'request_token_url',
    'authorization_url', 'access_token_url', 'api_url'
  );

  // The OAuth Config passed in the constructor
  public $oauthConfig;

  // The OAuth instance
  protected $oauth;

  // The wrapper
  protected $wrapper;
}
