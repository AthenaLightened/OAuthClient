<?php
/**
 * An OAuth Client Exception
 *
 * @see http://tools.ietf.org/html/rfc6749#appendix-A
 */
class OAuthClientException extends Exception
{
  /**
   * Constructor
   *
   * @param string|array $error
   * @param string $url The url of the request
   * @param string $response
   * @param int $response_code
   * @param Exception $previous
   */
  public function __construct($error, $url = '', $response = '', $response_code = 200, $previous = NULL)
  {
    // normalize to array
    $error = is_string($error) ? array('error' => $error) : $error;

    $this->error = $error;
    $this->url = $url;
    $this->response = $response;
    $this->response_code = $response_code;

    $message = $error['error'];
    parent::__construct($message, $response_code, $previous);
  }

  /**
   * Get the error
   */
  public function getError()
  {
    return $this->error;
  }

  /**
   * Get the error description
   */
  public function getErrorDescription()
  {
    $error = $this->getError();
    return isset($error['error_description']) ? $error['error_description'] : '';
  }

  /**
   * Get the error uri
   */
  public function getErrorUri()
  {
    $error = $this->getError();
    return isset($error['error_uri']) ? $error['error_uri'] : '';
  }

  public $error;
  public $url;
  public $response;
  public $response_code;
}
