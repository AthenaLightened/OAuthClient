<?php
/**
 * An OAuth 2.0 class
 */
class OAuth2Client implements IOAuthClient
{
  /**
   * Constructor
   *
   * @param array $oauth_config
   * <code>
   * array(
   *   'client_id' => 'YOUR CLIENT ID',
   *   'client_secret' => 'YOUR CLIENT SECRET',
   *   'redirect_url' => 'URL FOR REDIRECTING BACK AFTER AUTHORIZATION',
   *   'authorization_url' => 'URL FOR AUTHORIZATION',
   *   'access_token_url' => 'URL FOR EXCHANGING FOR THE ACCESS TOKEN'
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

    $this->oauthConfig = $oauth_config;
    $this->wrapper = $wrapper;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorizationUrl($scope = '', $state = '', $redirect = '')
  {
    $params = array();
    $params['client_id'] = $this->oauthConfig['client_id'];
    $params['response_type'] = 'code';
    $params['redirect_uri'] = empty($redirect) ? $this->oauthConfig['redirect_url'] : $redirect;

    if (!empty($state))
    {
      $params['state'] = $state;
    }

    if (!empty($scope))
    {
      $params['scope'] = $scope;
    }

    return $this->oauthConfig['authorization_url'] . '?' . http_build_query($params);
  }

  /**
   * Exchange for the access token
   *
   * @param string $code Authorization Code for OAuth 2.0
   * @return bool
   */
  public function exchangeAccessToken($code)
  {

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
  }

  /**
   * Get the last response
   *
   * @return string
   */
  public function getLastResponse()
  {
  }

  /**
   * Get the last response headers
   *
   * @return string
   */
  public function getLastResponseHeaders()
  {
  }

  ////////////////////////////////////////////////////////////////////////
  // Properties
  ////////////////////////////////////////////////////////////////////////

  // Config keys for OAuth 2.0
  private static $oauthConfigKeys = array(
    'client_id', 'client_secret', 'redirect_url',
    'authorization_url', 'access_token_url', 'api_url'
  );

  // The OAuth Config passed in the constructor
  public $oauthConfig;

  // The wrapper
  protected $wrapper;
}
