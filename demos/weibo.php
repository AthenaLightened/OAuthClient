<?php
session_start();
include_once('../src/OAuth2Client.php');
$client = new OAuth2Client(array(
  'client_id' => '2190273302',
  'client_secret' => '5ab2cf740ba174e198d72a63cca20c9b',
  'redirection_endpoint' => 'http://php.localhost/OAuthClient/demos/weibo.php',
  'authorization_endpoint' => 'https://api.weibo.com/oauth2/authorize',
  'token_endpoint' => 'https://api.weibo.com/oauth2/access_token',
  'resource_endpoint' => 'https://api.weibo.com/2/'
));

try
{
  if (isset($_GET['logout']))
  {
    $_SESSION['client'] = array();
  }

  if (empty($_SESSION['client']))
  {
    $client->exchangeAccessToken();

    $access_token = $client->getAccessToken();
    if (empty($access_token))
    {
      header('Location: ' . $client->getAuthorizationUrl());
      exit();
    }

    $_SESSION['client'] = $access_token;
  }

  if (!empty($_SESSION['client']))
  {
    $client->setAccessToken($_SESSION['client']);
    var_dump($client->fetch('account/get_uid.json', array(), 'GET'));
  }
}
catch (OAuthClientException $err)
{
  $error = $err->getError();

  echo "<p>OAuthClient Exception: </p>";
  echo sprintf("<p>Url: %s</p>", empty($err->url) ? '<EMPTY>' : htmlspecialchars($err->url));
  echo sprintf("<p>Response code: %d</p>", $err->getCode());
  echo sprintf("<p>Response: %s</p>", empty($err->response) ? '<EMPTY>' : htmlspecialchars($err->response));
  echo sprintf("<p>Error: %s</p>", htmlspecialchars($error['error']));

  if ($err->getErrorDescription())
  {
    echo sprintf("<p>Error Description: %s</p>", htmlspecialchars($err->getErrorDescription()));
  }

  if ($err->getErrorUri())
  {
    echo sprintf("<p>Error Uri: %s</p>", htmlspecialchars($err->getErrorUri()));
  }
}
