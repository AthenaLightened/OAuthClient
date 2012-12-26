<?php
include_once('OAuth1Client.php');
include_once('OAuth2Client.php');

class OAuthClientFactory
{
  /**
   * Create an OAuthClient instance
   *
   * @param array $config
   * @param string $version
   */
  public static function create($config, $version = OAuthClient::OAUTH_VERSION2)
  {
    if ($version === OAuthClient::OAUTH_VERSION1)
    {
      return new OAuth1Client($config);
    }

    return new OAuth2Client($config);
  }

  /**
   * Create a tudou OAuthClient
   *
   * @see http://api.tudou.com
   * @param string $consumer_key
   * @param string $consumer_secret
   */
  public static function tudou($consumer_key, $consumer_secret)
  {
    return self::create(array(
      'consumer_key' => $consumer_key,
      'consumer_secret' => $consumer_secret,
      'request_token_url' => 'http://api.tudou.com/auth/request_token.oauth',
      'authorization_url' => 'http://api.tudou.com/auth/authorize.oauth',
      'access_token_url' => 'http://api.tudou.com/auth/access_token.oauth',
      'api_url' => 'http://api.tudou.com/v3/gw?method='
    ), OAuthClient::OAUTH_VERSION1);
  }

  /**
   * Create a weibo OAuthClient
   *
   * @see http://open.weibo.com/wiki/API文档_V2
   * @param string $client_id
   * @param string $client_secret
   * @param string $redirect_url
   */
  public static function weibo($client_id, $client_secret, $redirect_url)
  {
    return self::create(array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'redirect_url' => $redirect_url,
      'authorization_url' => 'https://api.weibo.com/oauth2/authorize',
      'access_token_url' => 'https://api.weibo.com/oauth2/access_token',
      'api_url' => 'https://api.weibo.com/2/'
    ), OAuthClient::OAUTH_VERSION2);
  }

  /**
   * Create a qq weibo OAuthClient
   *
   * @see http://dev.open.t.qq.com/
   * @param string $client_id
   * @param string $client_secret
   * @param string $redirect_url
   */
  public static function qqWeibo($client_id, $client_secret, $redirect_url)
  {
    return self::create(array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'redirect_url' => $redirect_url,
      'authorization_url' => 'https://open.t.qq.com/cgi-bin/oauth2/authorize',
      'access_token_url' => 'https://open.t.qq.com/cgi-bin/oauth2/access_token',
      'api_url' => 'https://open.t.qq.com/api/'
    ), OAuthClient::OAUTH_VERSION2);
  }

  /**
   * Create a renren OAuthClient
   *
   * @see http://dev.renren.com/
   * @param string $client_id
   * @param string $client_secret
   * @param string $redirect_url
   */
  public static function renren($client_id, $client_secret, $redirect_url)
  {
    return self::create(array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'redirect_url' => $redirect_url,
      'authorization_url' => 'https://graph.renren.com/oauth/authorize',
      'access_token_url' => 'https://graph.renren.com/oauth/token',
      'api_url' => 'http://api.renren.com/restserver.do'
    ), OAuthClient::OAUTH_VERSION2);
  }

  /**
   * Create a facebook OAuthClient
   *
   * @see https://developers.facebook.com/
   * @param string $client_id
   * @param string $client_secret
   * @param string $redirect_url
   */
  public static function facebook($client_id, $client_secret, $redirect_url)
  {
    return self::create(array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'redirect_url' => $redirect_url,
      'authorization_url' => 'https://www.facebook.com/dialog/oauth',
      'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
      'api_url' => 'https://graph.facebook.com/'
    ), OAuthClient::OAUTH_VERSION2);
  }

  /**
   * Create a yahoo OAuthClient
   *
   * @see http://developer.yahoo.com/
   * @param string $consumer_key
   * @param string $consumer_secret
   */
  public static function yahoo($consumer_key, $consumer_secret)
  {
    return self::create(array(
      'consumer_key' => $consumer_key,
      'consumer_secret' => $consumer_secret,
      'request_token_url' => 'https://api.login.yahoo.com/oauth/v2/get_request_token',
      'authorization_url' => 'https://api.login.yahoo.com/oauth/v2/request_auth',
      'access_token_url' => 'https://api.login.yahoo.com/oauth/v2/get_token',
      'api_url' => 'http://social.yahooapis.com/v1/'
    ), OAuthClient::OAUTH_VERSION1);
  }
}
