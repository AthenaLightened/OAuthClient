<?php
/**
 * An OAuth Client Exception
 */
class OAuthClientException extends Exception
{
  public $url;

  public $response;

  /**
   * Constructor
   *
   * @param string $message
   * @param string $url
   * @param string $response
   * @param int $http_code
   */
  public function __construct($message, $url = '', $response = '', $http_code = 0)
  {
    parent::__construct($message, $http_code);

    $this->url = $url;
    $this->response = $response;
  }
}

