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
}
