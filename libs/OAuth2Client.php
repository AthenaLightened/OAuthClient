<?php
include_once('OAuthClient.php');

/**
 * An OAuth 2.0 class
 */
class OAuth2Client extends OAuthClient
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
   */
  public function __construct($oauth_config)
  {
    parent::__construct($oauth_config);
  }

  /**
   * @inheritDoc
   */
  public function getAuthorizationUrl($params = NULL)
  {
    $query = array();
    $query['client_id'] = $this->oauthConfig['client_id'];
    $query['response_type'] = 'code';
    if ($params === NULL || !isset($params['redirect']))
    {
      $query['redirect_uri'] = $this->oauthConfig['redirect_url'];
    }
    else
    {
      $query['redirect_uri'] = $params['redirect'];
    }

    if ($params !== NULL && isset($params['state']))
    {
      $query['state'] = $params['state'];
    }

    if ($params !== NULL && isset($params['scope']))
    {
      $query['scope'] = $params['scope'];
    }

    return $this->oauthConfig['authorization_url'] . '?' . http_build_query($query);
  }

  /**
   * @inheritDoc
   */
  public function exchangeAccessToken($token, $secret_or_redirect_url = '')
  {
    $redirect = $secret_or_redirect_url === '' ? $this->oauthConfig['redirect_url'] : $secret_or_redirect_url;

    $query = array();
    $query['client_id'] = $this->oauthConfig['client_id'];
    $query['client_secret'] = $this->oauthConfig['client_secret'];
    $query['redirect_uri'] = $redirect;
    $query['grant_type'] = 'authorization_code';
    $query['code'] = $token;

    $url = $this->oauthConfig['access_token_url'];
    try
    {
      $this->log(sprintf('Getting access token from %s.', $url), OAuthClient::LOG_LEVEL_DEBUG);

      $response = $this->sendRequest($url, $query, 'POST');

      $this->log(sprintf('Access token received from %s is %s.', $url, var_export($response, TRUE)), OAuthClient::LOG_LEVEL_DEBUG);
    }
    catch (Exception $err)
    {
      $this->log(sprintf('Failed to get access token from %s.Error: %s', $url, $err->getMessage()));
      $this->throwException($url, $err);
    }

    return $response;
  }

  /**
   * Set the token
   *
   * @param string $token
   * @param string $secret For OAuth 2.0, you may not set this parameter
   */
  public function setToken($token, $secret = '')
  {
    $this->log(sprintf('Set access token %s.', $token), OAuthClient::LOG_LEVEL_DEBUG);
    $this->accessToken = $token;
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

    $params['client_id'] = $this->oauthConfig['client_id'];
    $params['access_token'] = $this->accessToken;

    $log_message = 'Fetch api (%s) with params: (%s), method: %s, headers: (%s).';
    $log_message = sprintf($log_message, $api, var_export($params, TRUE), $method, var_export($headers, TRUE));
    $this->log($log_message, OAuthClient::LOG_LEVEL_DEBUG);

    return $this->sendRequest($api, $params, $method, $headers);
  }

  /**
   * Get the http info in the last response
   *
   * @return array
   */
  public function getLastResponseInfo()
  {
    return $this->lastResponseInfo;
  }

  /**
   * Get the last response
   *
   * @return string
   */
  public function getLastResponse()
  {
    return $this->lastResponse;
  }

  /**
   * Get the last response headers
   *
   * @return string
   */
  public function getLastResponseHeaders()
  {
    return $this->lastResponseHeaders;
  }

  /**
   * Send a http request using curl
   *
   * @param string $url
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return array|FALSE
   */
  protected function sendRequest($url, $params = array(), $method = 'POST', $headers = array())
  {
    // curl options
    $opts = array();

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    $headers[] = 'Expect:';
    $opts[CURLOPT_HTTPHEADER] = $headers;

    if (strtolower($method) != 'get')
    {
      $opts[CURLOPT_POST] = TRUE;

      // check if it's a file upload 
      // To be consistent with OAuth extension, both key and value should start with '@'
      $has_file = FALSE;
      foreach ($params as $k => $value)
      {
        if (substr($k, 0, 1) === '@' && substr($value, 0, 1) === '@')
        {
          // remove the leading '@', because curl doesn't support that
          $params[substr($k, 1)] = $value;
          unset($params[$k]);

          $has_file = TRUE;
          break;
        }
      }

      // multipart/form-data if there is no file for uploading
      $opts[CURLOPT_POSTFIELDS] = $has_file ? $params : http_build_query($params);
    }
    else
    {
      $url .= (strpos($url, '?') === FALSE ? '?' : '&') . http_build_query($params);
    }

    $opts[CURLOPT_CONNECTTIMEOUT] = 10;
    $opts[CURLOPT_HEADER] = TRUE;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_TIMEOUT] = 60;
    $opts[CURLOPT_USERAGENT] = 'OAuthClient';
    $opts[CURLOPT_URL] = $url;
    $opts[CURLOPT_SSL_VERIFYPEER] = FALSE;
    $opts[CURLINFO_HEADER_OUT] = TRUE;

    // send request
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);

    $error_code = curl_errno($ch);
    if ($error_code !== 0)
    {
      $this->lastResponseHeaders = '';
      $this->lastResponse = '';
      $this->lastResponseInfo = curl_getinfo($ch);

      $error_message = curl_error($ch);
      $this->throwException($url, new Exception($error_message, $error_code));
    }
    else
    {
      list($this->lastResponseHeaders, $this->lastResponse) = explode("\r\n\r\n", $response);
      $this->lastResponseInfo = curl_getinfo($ch);
    }
    curl_close($ch);

    // decode response
    $response = $this->decodeJSONOrQueryString($this->getLastResponse());
    $http_code = empty($this->lastResponseInfo) ? 0 : intval($this->lastResponseInfo['http_code']);

    // check error.
    // If the status code >= 400, it will be considered as an error
    // Or if there is an error key in the response, it will be considered as an error too
    $error = '';
    if (is_array($response))
    {
      if (!empty($response['error']))
      {
        $error = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
      }
      else if (!empty($response['error_description']))
      {
        $error = $response['error_description'];
      }
    }

    if ($error || $http_code >= 400)
    {
      $this->throwException($url, new Exception($error));
    }

    return $response;
  }


  ////////////////////////////////////////////////////////////////////////
  // Properties
  ////////////////////////////////////////////////////////////////////////

  // Config keys for OAuth 2.0
  protected $oauthConfigKeys = array(
    'client_id', 'client_secret', 'redirect_url',
    'authorization_url', 'access_token_url', 'api_url'
  );

  // Last Response Header
  protected $lastResponseHeaders;

  // Last Response Info
  protected $lastResponseInfo;

  // Last response
  protected $lastResponse;

  // Access Token
  protected $accessToken;
}
