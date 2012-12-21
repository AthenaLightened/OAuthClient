<?php
/**
 * An OAuth Client Interface
 */
interface IOAuthClient
{
  /**
   * Get the authorization url
   *
   * @param string $scope Scope
   * @param string $state State
   * @param string $redirect Redirect url for authorization
   * @return string
   */
  public function getAuthorizationUrl($scope = '', $state = '', $redirect = '');

  /**
   * Exchange for the access token
   *
   * @param string $code OAuth token for OAuth 1.0, Authorization Code for OAuth 2.0
   * @return bool
   */
  public function exchangeAccessToken($code);

  /**
   * Set the token
   *
   * @param string $token
   * @param string $secret For OAuth 2.0, you may not set this parameter
   */
  public function setToken($token, $secret = '');

  /**
   * Fetch the resource
   *
   * @param string $api
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return mixed
   */
  public function fetch($api, $params = array(), $method = 'POST', $headers = array());

  /**
   * Get the http info in the last response
   *
   * @return array
   */
  public function getLastResponseInfo();

  /**
   * Get the last response
   *
   * @return string
   */
  public function getLastResponse();

  /**
   * Get the last response headers
   *
   * @return string
   */
  public function getLastResponseHeaders();
}
