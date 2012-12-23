<?php
include_once('OAuthClient.php');

// we need OAuth extension for OAuth1
if (!class_exists('OAuth'))
{
  throw new OAuthClientException('OAuth extension is required.');
}

/**
 * An OAuth 1.0 class, wrapper for OAuth class in the OAuth extension
 */
class OAuth1Client extends OAuthClient
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
   */
  public function __construct($oauth_config)
  {
    parent::__construct($oauth_config);

    $this->oauth = new OAuth($oauth_config['consumer_key'], $oauth_config['consumer_secret']);
    $this->oauthConfig = $oauth_config;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorizationUrl($params = NULL)
  {
    $url = $this->oauthConfig['request_token_url'];
    try
    {
      $this->log(sprintf('Getting request token from %s.', $url), OAuthClient::LOG_LEVEL_DEBUG);

      $request_token_info = $this->oauth->getRequestToken($url);
      $token = $request_token_info['oauth_token'];
      $secret = $request_token_info['oauth_token_secret'];
      $authorization_url = $this->oauthConfig['authorization_url'] . '?oauth_token=' . urlencode($token);

      $this->log(sprintf('Request token received from %s is (%s, %s).Authorization url is %s.', 
                         $url, $token, $secret, $authorization_url), OAuthClient::LOG_LEVEL_DEBUG);
    }
    catch (Exception $err)
    {
      $this->log(sprintf('Failed to get request token from %s.Error: %s', $url, $err->getMessage()));

      $response_info = $this->getLastResponseInfo();
      $http_code = empty($response_info) ? 0 : $response_info['http_code'];
      throw new OAuthClientException($err->getMessage(), $url, $this->getLastResponse(), $http_code);
    }

    return array($secret, $authorization_url);
  }

  /**
   * @inheritDoc
   */
  public function exchangeAccessToken($token, $secret_or_redirect_url = '')
  {
    if (empty($secret_or_redirect_url))
    {
      return '';
    }

    $this->oauth->setToken($token, $secret_or_redirect_url);
    $url = $this->oauthConfig['access_token_url'];
    try
    {
      $this->log(sprintf('Getting access token from %s.', $url), OAuthClient::LOG_LEVEL_DEBUG);

      $info = $this->oauth->getAccessToken($url);

      $this->log(sprintf('Access token received from %s is %s.', $url, var_export($info, TRUE)), OAuthClient::LOG_LEVEL_DEBUG);
    }
    catch (Exception $err)
    {
      $this->log(sprintf('Failed to get access token from %s.Error: %s', $url, $err->getMessage()));

      $response_info = $this->getLastResponseInfo();
      $http_code = empty($response_info) ? 0 : $response_info['http_code'];
      throw new OAuthClientException($err->getMessage(), $url, $this->getLastResponse(), $http_code);
    }

    return $info;
  }

  /**
   * Set the token
   *
   * @param string $token
   * @param string $secret For OAuth 2.0, you may not set this parameter
   */
  public function setToken($token, $secret = '')
  {
    $this->log(sprintf('Set token (%s, %s).', $token, $secret), OAuthClient::LOG_LEVEL_DEBUG);
    $this->oauth->setToken($token, $secret);
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
    if (strtolower(substr($api, 0, 4)) !== 'http')
    {
      $api = $this->oauthConfig['api_url'] . $api;
    }

    $this->log(sprintf('Fetch api (%s) with params: (%s), method: %s, headers: (%s).', 
                       $api, var_export($params, TRUE), $method, var_export($headers, TRUE)), OAuthClient::LOG_LEVEL_DEBUG);

    try
    {
      $this->oauth->fetch($api, $params, $method, $headers);

      $response = $this->getLastResponse();
    }
    catch (Exception $err)
    {
      $this->log(sprintf('Failed to fetch resource from %s.Error: %s.', $api, $err->getMessage()));

      $response_info = $this->getLastResponseInfo();
      $http_code = empty($response_info) ? 0 : $response_info['http_code'];
      throw new OAuthClientException($err->getMessage(), $url, $this->getLastResponse(), $http_code);
    }

    return $this->decodeJSONOrQueryString($response);
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
  protected $oauthConfigKeys = array(
    'consumer_key', 'consumer_secret', 'request_token_url',
    'authorization_url', 'access_token_url', 'api_url'
  );

  // The OAuth Config passed in the constructor
  public $oauthConfig;

  // The OAuth instance
  protected $oauth;

  // Version
  public $oauthVersion = OAuthClient::OAUTH_VERSION1;
}
