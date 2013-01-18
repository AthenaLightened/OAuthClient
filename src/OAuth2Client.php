<?php
include_once('OAuthClientException.php');

if (!function_exists('curl_init'))
{
  throw new OAuthClientException('CURL PHP extension is required.');
}

if (!function_exists('json_encode'))
{
  throw new OAuthClientException('JSON PHP extension is required.');
}

/**
 * An OAuth 2.0 client class
 *
 * Support:
 * Grant type: authorization code, http://tools.ietf.org/html/rfc6749#section-1.3
 * Token type: default, bearer, http://tools.ietf.org/html/draft-ietf-oauth-v2-bearer-23
 */
class OAuth2Client
{
  /**
   * Constructor
   *
   * oauth_config
   *   client_id, required 
   *   client_secret, required
   *   redirection_endpoint, required
   *   authorization_endpoint, required
   *   token_endpoint, required
   *   scope, optional
   *   resource_endpoint, optional
   *
   * @param array $oauth_config
   * @param array $options
   */
  public function __construct($oauth_config, $options = array())
  {
    $oauth2_properties = array('client_id', 'client_secret', 'redirection_endpoint',
                               'authorization_endpoint', 'token_endpoint');
    foreach ($oauth2_properties as $key)
    {
      if (!isset($oauth_config[$key]))
      {
        throw new OAuthClientException('OAuth config "' . $key . '" is required.');
      }

      $this->$key = $oauth_config[$key];
    }

    if (isset($oauth_config['scope']))
    {
      $this->scope = $oauth_config['scope'];
    }

    if (isset($oauth_config['resource_endpoint']))
    {
      $this->resource_endpoint = $oauth_config['resource_endpoint'];
    }

    // set other options
    foreach ($options as $k => $v)
    {
      if (property_exists($this, $k))
      {
        $this->$k = $v;
      }
    }
  }

  /**
   * Get the authorization url
   *
   * @link http://tools.ietf.org/html/rfc6749#section-4.1
   * @param string $scope
   * @param string $state
   * @param string $redirect_uri
   * @return array|string
   */
  public function getAuthorizationUrl($scope = '', $state = '', $redirect_uri = '')
  {
    $query = array();
    $query['client_id'] = $this->client_id;
    $query['response_type'] = 'code';
    $query['redirect_uri'] = $redirect_uri === '' ? $this->redirection_endpoint : $redirect_uri;

    $scope = $scope === '' ? $this->scope : $scope;
    if ($scope !== '')
    {
      $query['scope'] = $this->scope = $scope;
    }

    if ($state !== '')
    {
      $query['state'] = $state;
    }

    if (strpos($this->authorization_endpoint, '?') === FALSE)
    {
      return $this->authorization_endpoint . '?' . http_build_query($query);
    }

    return $this->authorization_endpoint . '&' . http_build_query($query);
  }


  /**
   * Fetch the resource
   *
   * @param string $uri
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return mixed
   */
  public function fetch($uri, $params = array(), $method = 'POST', $headers = array())
  {
    if (strtolower(substr($uri, 0, 4)) !== 'http')
    {
      $uri = $this->resource_endpoint . $uri;
    }

    // check if token exists.
    $access_token = $this->getAccessToken();
    if (empty($access_token))
    {
      throw new OAuthClientException('Access token is required.');
    }

    // check if token has expired.
    if (time() >= $this->getAccessTokenExpirationTime())
    {
      // try to refresh the access token
      $this->refreshAccessToken();
      $access_token = $this->getAccessToken();
      if (empty($access_token))
      {
        throw new OAuthClientException('Access token has expired.');
      }
    }

    $this->appendAccessToken($uri, $params, $method, $headers);
    $this->sendRequest($uri, $params, $method, $headers);
    $this->checkAccessTokenError();
    return $this->processResponse();
  }

  /**
   * Append the access token to the request
   *
   * @param string $uri
   * @param array $params
   * @param string $method
   * @param array $headers
   */
  protected function appendAccessToken(&$uri, &$params, &$method, &$headers)
  {
    $access_token = $this->getAccessToken();

    switch ($this->getAccessTokenType())
    {
      case self::TOKEN_TYPE_DEFAULT:
      $params[$this->key_client_id] = $this->client_id;
      $params['access_token'] = $access_token['access_token'];
      break;

      case self::TOKEN_TYPE_BEARER:
      switch ($this->getAccessTokenAuthenticationType())
      {
        case self::TOKEN_AUTHENTICATE_TYPE_URI_QUERY:
        if (strpos($uri, '?') === FALSE)
        {
          $uri = sprintf('%s?access_token=%s', $uri, urlencode($access_token['access_token']));
        }
        else
        {
          $uri = sprintf('%s&access_token=%s', $uri, urlencode($access_token['access_token']));
        }
        break;

        case self::TOKEN_AUTHENTICATE_TYPE_FORM_BODY:
        $params['access_token'] = $access_token['access_token'];
        $method = 'POST';
        break;

        case self::TOKEN_AUTHENTICATE_TYPE_AUTHORIZATION:
        $headers[] = 'Authorization: Bearer ' . $access_token['access_token'];
        break;
      }
      break;

      case self::TOKEN_TYPE_MAC:
      break;

      default:
      throw new OAuthClientException('Access token type "' . $this->getAccessTokenType() . '" is not supported.');
    }
  }

  /**
   * Check access token error
   */
  protected function checkAccessTokenError()
  {
    $access_token = $this->getAccessToken();

    switch ($this->getAccessTokenType())
    {
      case self::TOKEN_TYPE_DEFAULT:
      // do nothing
      break;

      case self::TOKEN_TYPE_BEARER:
      break;

      case self::TOKEN_TYPE_MAC:
      break;
    }
  }

  /**
   * Get the scope
   *
   * @return string
   */
  public function getScope()
  {
    return $this->scope;
  }

  ////////////////////////////////////////////////////////////////////////
  // Access Token
  ////////////////////////////////////////////////////////////////////////

  protected $access_token = array();

  /**
   * Get the access token
   *
   * @return array
   */
  public function getAccessToken()
  {
    return $this->access_token;
  }

  /**
   * Get the expiration time of the access token
   *
   * @return int
   */
  public function getAccessTokenExpirationTime()
  {
    $access_token = $this->getAccessToken();
    if (empty($access_token))
    {
      return 0;
    }
    else
    {
      if (isset($access_token['expiration_time']))
      {
        return $access_token['expiration_time'];
      }
      else
      {
        // will never expires
        return PHP_INT_MAX;
      }
    }
  }

  /**
   * Get the access token type
   *
   * @return string
   */
  public function getAccessTokenType()
  {
    $access_token = $this->getAccessToken();
    if (!empty($access_token) && isset($access_token['token_type']))
    {
      return $access_token['token_type'];
    }

    return self::TOKEN_TYPE_DEFAULT;
  }

  /**
   * Get the access token authentication type
   *
   * @return string
   */
  public function getAccessTokenAuthenticationType()
  {
    $access_token = $this->getAccessToken();

    if (!empty($access_token) && isset($access_token['token_authentication_type']))
    {
      return $access_token['token_authentication_type'];
    }

    return '';
  }

  /**
   * Get the refresh token
   */
  public function getRefreshToken()
  {
    $access_token = $this->getAccessToken();
    if (!empty($access_token) && isset($access_token['refresh_token']))
    {
      return $access_token['refresh_token'];
    }

    return '';
  }

  /**
   * Exchange for the access token
   *
   * 1. Using authorization code
   * $oauth2->exchangeAccessToken(array('code' => $_GET['code'], 'redirect_uri' => 'XXXX'));
   *
   * 2. Using user name and password
   * // not supported yet
   *
   * @param array $params
   */
  public function exchangeAccessToken($params = array())
  {
    $access_token = array();

    switch ($this->grant_type)
    {
      case self::GRANT_TYPE_AUTHORIZATION_CODE:
      $code = isset($params['code']) ? $params['code'] : '';
      $redirect_uri = isset($params['redirect_uri']) ? $params['redirect_uri'] : $this->redirection_endpoint;

      if ($code === '')
      {
        $code = isset($_GET[$this->key_code]) ? $_GET[$this->key_code] : '';
      }

      // check the error in the $_GET
      if ($code === '')
      {
        $error = $this->extractError($_GET);
        if ($error === FALSE)
        {
          return;
        }
        
        throw new OAuthClientException($error);
      }

      $query = array();
      $headers = array();
      $method = 'POST';
      if ($this->client_authentication_type === self::CLIENT_AUTHENTICATION_TYPE_REQUEST_BODY)
      {
        $query['client_id'] = $this->client_id;
        $query['client_secret'] = $this->client_secret;
      }
      else
      {
        $headers[] = 'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret);
      }
      $query['redirect_uri'] = $redirect_uri;
      $query['grant_type'] = 'authorization_code';
      $query['code'] = $code;

      $this->sendRequest($this->token_endpoint, $query, $method, $headers);
      break;

      default:
      throw new OAuthClientException('Grant type (' . $this->grant_type . ') is not supported.');
    }

    $response = $this->processResponse();

    // It should be an array.
    // But if it's a string, we assume this is a valid access token
    if (is_string($response))
    {
      $response = array('access_token' => $response);
    }

    // calculate the expiration time
    if (isset($response['expires_in']) && is_numeric($response['expires_in']))
    {
      $response['expiration_time'] = time() + $response['expires_in'];
    }

    // set token type
    if (!isset($response['token_type']))
    {
      $response['token_type'] = self::TOKEN_TYPE_DEFAULT;
    }

    // set token authenticate type
    switch ($response['token_type'])
    {
      case self::TOKEN_TYPE_BEARER:
      $response['token_authentication_type'] = self::TOKEN_AUTHENTICATE_TYPE_URI_QUERY;
      break;

      case self::TOKEN_TYPE_MAC:
      $response['token_authentication_type'] = self::TOKEN_AUTHENTICATE_TYPE_AUTHORIZATION;
      break;
    }

    $this->setAccessToken($response);
  }

  /**
   * Refresh the access token
   */
  public function refreshAccessToken()
  {
    return ;
  }

  /**
   * Set access token
   *
   * @param string|array $token
   */
  public function setAccessToken($token)
  {
    if (is_string($token))
    {
      $this->access_token = array('access_token' => $token);
    }
    else
    {
      $this->access_token = $token;
    }

    // override the scope
    if (isset($this->access_token['scope']))
    {
      $this->scope = $this->access_token['scope'];
    }
  }


  ////////////////////////////////////////////////////////////////////////
  // Last Request & Response
  ////////////////////////////////////////////////////////////////////////

  protected $last_request_params;
  protected $last_response_headers;
  protected $last_response_info;
  protected $last_response;

  /**
   * Get the last request headers
   *
   * @return string
   */
  public function getLastRequestHeaders()
  {
    $response_info = $this->getLastResponseInfo();
    if (empty($response_info) || !isset($response_info['request_header']))
    {
      return '';
    }
    else
    {
      return $response_info['request_header'];
    }
  }

  /**
   * Get the last request params
   *
   * @return array
   */
  public function getLastRequestParams()
  {
    return $this->last_request_params;
  }

  /**
   * Get the last response http code
   *
   * @return int
   */
  public function getLastResponseCode()
  {
    $response_info = $this->getLastResponseInfo();
    if (empty($response_info) || !isset($response_info['http_code']))
    {
      return 0;
    }
    else
    {
      return $response_info['http_code'];
    }
  }

  /**
   * Get the http info in the last response
   *
   * @return array
   */
  public function getLastResponseInfo()
  {
    return $this->last_response_info;
  }

  /**
   * Get the last response headers
   *
   * @return string
   */
  public function getLastResponseHeaders()
  {
    return $this->last_response_headers;
  }

  /**
   * Get the last response
   *
   * @return string
   */
  public function getLastResponse()
  {
    return $this->last_response;
  }

  ////////////////////////////////////////////////////////////////////////
  // Utils
  ////////////////////////////////////////////////////////////////////////

  /**
   * Send a http request using curl
   *
   * @param string $url
   * @param array $params
   * @param string $method
   * @param array $headers
   */
  protected function sendRequest($url, $params = array(), $method = 'POST', $headers = array())
  {
    // curl options
    $opts = array();

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    $headers[] = 'Expect:';
    $opts[CURLOPT_HTTPHEADER] = $headers;

    switch (strtoupper($method))
    {
      case 'GET':
      if (!empty($params))
      {
        $url .= (strpos($url, '?') === FALSE ? '?' : '&') . http_build_query($params);
        $this->last_request_params = array();
      }
      break;
      
      case 'POST':
      $opts[CURLOPT_POST] = TRUE;

      $has_file = FALSE;
      foreach ($params as $k => $value)
      {
        if (substr($value, 0, 1) === '@' && is_file(substr($value, 1)))
        {
          $has_file = TRUE;
          break;
        }
      }

      $this->last_request_params = $params;

      // multipart/form-data if there is a file for uploading
      $opts[CURLOPT_POSTFIELDS] = $has_file ? $params : http_build_query($params);
      break;
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
    $this->last_response_info = curl_getinfo($ch);

    // check curl error
    $error_code = curl_errno($ch);
    if ($error_code !== CURLE_OK)
    {
      $this->last_response_headers = '';
      $this->last_response = '';

      throw new OAuthClientException(curl_error($ch), $url, '', 0);
    }
    else
    {
      list($this->last_response_headers, $this->last_response) = explode("\r\n\r\n", $response);
    }
    curl_close($ch);
  }

  /**
   * Process response
   *
   * @return mixed
   */
  protected function processResponse()
  {
    $response = $this->getLastResponse();
    $response_info = $this->getLastResponseInfo();
    $response_code = $this->getLastResponseCode();

    // some OAuth2 implementations don't use a correct content type
    if ($this->ignore_response_content_type)
    {
      $response = $this->decodeJSONOrQueryString($response);
    }
    else
    {
      // handle 'text/html' and 'text/html;charset=UTF-8'
      $content_type = trim(current(explode(';', $response_info['content_type'])));

      switch ($content_type)
      {
        case 'text/html':
        // assign the value back
        parse_str($response, $response);
        break;

        case 'text/javascript':
        case 'application/json':
        $response = json_decode($response, TRUE);
        break;

        default:
        $response = $this->decodeJSONOrQueryString($response);
        break;
      }
    }

    if ($response_code >= 400 || empty($response))
    {
      $error = $this->extractError($response);
      $error = $error === FALSE ? '' : $error;
      $url = $response_info['url'];
      throw new OAuthClientException($error, $url, $this->getLastResponse(), $response_code);
    }

    return $response;
  }

  /**
   * Extract the error from an array
   *
   * @param array $arr
   * @return array|FALSE
   */
  protected function extractError($arr)
  {
    $key = $this->key_error;

    if (isset($arr[$key]))
    {
      $error = array();
      $error['error'] = is_string($arr[$key]) ? $arr[$key] : json_encode($arr[$key]);

      $key = $this->key_error_description;
      if (isset($arr[$key]))
      {
        $error['error_description'] = $arr[$key];
      }

      $key = $this->key_error_uri;
      if (isset($arr[$key]))
      {
        $error['error_uri'] = $arr[$key];
      }

      return $error;
    }

    return FALSE;
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

  /**
   * Factory method
   *
   * @param string $platform
   * @param array $oauth_config
   * @param array $options
   */
  public static function create($platform, $oauth_config, $options = array())
  {
    switch (strtolower($platform))
    {
      case 'sina':
      case 'weibo':
      $oauth_config['authorization_endpoint'] = 'https://api.weibo.com/oauth2/authorize';
      $oauth_config['token_endpoint'] = 'https://api.weibo.com/oauth2/access_token';
      $oauth_config['resource_endpoint'] = 'https://api.weibo.com/2/';
      break;

      case 't':
      case 'qq':
      $oauth_config['authorization_endpoint'] = 'https://open.t.qq.com/cgi-bin/oauth2/authorize';
      $oauth_config['token_endpoint'] = 'https://open.t.qq.com/cgi-bin/oauth2/access_token';
      $oauth_config['resource_endpoint'] = 'https://open.t.qq.com/api/';
      $options['key_client_id'] = 'oauth_consumer_key';
      break;

      case 'renren':
      $oauth_config['authorization_endpoint'] = 'https://graph.renren.com/oauth/authorize';
      $oauth_config['token_endpoint'] = 'https://graph.renren.com/oauth/token';
      $oauth_config['resource_endpoint'] = 'http://api.renren.com/restserver.do';
      break;

      case 'baidu':
      $oauth_config['authorization_endpoint'] = 'https://openapi.baidu.com/oauth/2.0/authorize';
      $oauth_config['token_endpoint'] = 'https://openapi.baidu.com/oauth/2.0/token';
      $oauth_config['resource_endpoint'] = 'https://openapi.baidu.com/rest/2.0';
      break;

      case 'facebook':
      $oauth_config['authorization_endpoint'] = 'https://www.facebook.com/dialog/oauth';
      $oauth_config['token_endpoint'] = 'https://graph.facebook.com/oauth/access_token';
      $oauth_config['resource_endpoint'] = 'https://graph.facebook.com/';
      break;

      case 'google':
      break;


      default:
      throw new OAuthClientException('Platform "' . $platform . '" is not supported');
    }

    return new self($oauth_config, $options);
  }


  ////////////////////////////////////////////////////////////////////////
  // Properties
  ////////////////////////////////////////////////////////////////////////

  // oauth 2, properties
  // @link http://tools.ietf.org/html/rfc6749
  protected $client_id;
  protected $client_secret;
  protected $redirection_endpoint;
  protected $authorization_endpoint;
  protected $token_endpoint;
  protected $resource_endpoint;
  protected $scope;

  protected $grant_type = self::GRANT_TYPE_AUTHORIZATION_CODE;
  protected $client_authentication_type = self::CLIENT_AUTHENTICATION_TYPE_REQUEST_BODY;

  // key properties
  protected $key_client_id = 'client_id';
  protected $key_state = 'state';
  protected $key_code = 'code';
  protected $key_error = 'error';
  protected $key_error_description = 'error_description';
  protected $key_error_uri = 'error_uri';

  // flags
  protected $ignore_response_content_type = FALSE;

  ////////////////////////////////////////////////////////////////////////
  // Consts
  ////////////////////////////////////////////////////////////////////////
  
  // @link http://tools.ietf.org/html/rfc6749#section-1.3
  const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

  // @link http://tools.ietf.org/html/rfc6749#section-2.3
  const CLIENT_AUTHENTICATION_TYPE_AUTHORIZATION = 'authorization';
  const CLIENT_AUTHENTICATION_TYPE_REQUEST_BODY = 'request_body';

  // @link http://tools.ietf.org/html/rfc6749#section-7.1
  // @see appendAccessToken
  const TOKEN_TYPE_DEFAULT = 'default';
  const TOKEN_TYPE_BEARER = 'bearer';
  const TOKEN_TYPE_MAC = 'mac';

  // @link http://tools.ietf.org/html/rfc6750#section-2
  const TOKEN_AUTHENTICATE_TYPE_AUTHORIZATION = 'authorization';
  const TOKEN_AUTHENTICATE_TYPE_FORM_BODY = 'form_body';
  const TOKEN_AUTHENTICATE_TYPE_URI_QUERY = 'uri_query';
}
