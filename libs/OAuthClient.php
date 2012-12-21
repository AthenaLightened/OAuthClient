<?php
if (!function_exists('curl_init'))
{
  throw new OAuthClientException('CURL PHP extension is required.');
}

include_once('IOAuthClient.php');
include_once('OAuthClientException.php');

$o1 = new OAuthClient(array(
  'consumer_key' => '013ebe46ef976d1a',
  'consumer_secret' => 'eda09223a9d940bba00f809b99c17171',
  'request_token_url' => 'http://api.tudou.com/auth/request_token.oauth',
  'authorization_url' => 'http://api.tudou.com/auth/authorize.oauth',
  'access_token_url' => 'http://api.tudou.com/auth/access_token.oauth',
  'api_url' => 'http://api.tudou.com/auth/verify_credentials.oauth'
), OAuthClient::OAUTH_VERSION1);

$o2 = new OAuthClient(array(
  'client_id' => '51885333',
  'client_secret' => 'c1b238a2f5ed43c177014fd6bcc76ee4',
  'redirect_url' => 'http://oauth-api-tester.appspot.com',
  'authorization_url' => 'https://api.weibo.com/oauth2/authorize',
  'access_token_url' => 'https://api.weibo.com/oauth2/access_token',
  'api_url' => 'https://api.weibo.com/2'
));

var_dump($o1->getAuthorizationUrl());
echo "---------------\r\n";
var_dump($o1->getLastResponseInfo());
echo "---------------\r\n";
var_dump($o1->getLastResponse());
echo "---------------\r\n";
var_dump($o1->getLastResponseHeaders());
echo "---------------\r\n";
var_dump($o2->getAuthorizationUrl());

/**
 * OAuthClient class
 *
 * Requirement:
 * 1. curl, http://php.net/manual/en/book.curl.php
 * 2. OAuth, if using OAuth 1.0, http://php.net/manual/en/book.oauth.php
 */
class OAuthClient
{
  /**
   * Constructor
   *
   * @param array $oauth_config
   * @param string $version OAUTH_VERSION1 or OAUTH_VERSION2
   */
  public function __construct($oauth_config, $version = self::OAUTH_VERSION2)
  {
    if ($version === self::OAUTH_VERSION1)
    {
      include_once('OAuth1Client.php');
      $this->oauth = new OAuth1Client($oauth_config, $this);
      $this->oauthVersion = $version;
    }
    else
    {
      include_once('OAuth2Client.php');
      $this->oauth = new OAuth2Client($oauth_config, $this);
      $this->oauthVersion = self::OAUTH_VERSION2;
    }
  }

  /**
   * Set the Logger
   * 
   * @param callable $logger The logger should look like function ($message, $level)
   */
  public function setLogger($logger)
  {
    $this->logger = $logger;
  }

  /**
   * Log the message
   *
   * @param string $message
   * @param string $level
   */
  protected function log($message, $level = self::LOG_LEVEL_DEBUG)
  {
    if ($this->logger)
    {
      call_user_func($this->logger, $message, $level);
    }
  }

  /**
   * Get the session, start if not started yet
   *
   * @return array
   */
  public function &getSession()
  {
    // start session
    if (session_id() === '')
    {
      session_start();
    }

    if (!isset($_SESSION[$this->sessionKey]))
    {
      $_SESSION[$this->sessionKey] = array();
    }

    return $_SESSION[$this->sessionKey];
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

  // The OAuth version
  public $oauthVersion;

  // The OAuth instance
  protected $oauth;

  // The logger
  protected $logger;

  // Session key to save
  public $sessionKey = 'OAuthClient';

  const OAUTH_VERSION1 = '1.0';
  const OAUTH_VERSION2 = '2.0';

  const LOG_LEVEL_DEBUG = 'debug';
  const LOG_LEVEL_ERROR = 'error';
}
