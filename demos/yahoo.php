<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$yahoo = OAuthClientFactory::yahoo('dj0yJmk9TWt4NXRmdk9iU1hLJmQ9WVdrOVQwSk9XR1kzTkRJbWNHbzlNakF3T1RrNE9EVTJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD0xZQ--', 'd45633e3437f3c062ad837726f4425272c690875');

// logout
if (!empty($_GET['logout']))
{
  $_SESSION = array();
}

$err = NULL;
$me = NULL;
try
{
  if (empty($_SESSION['yahoo']))
  {
    list($secret, $authorization_url) = $yahoo->getAuthorizationUrl(array('callback_url' => ''));
    $_SESSION['yahoo'] = array(
      'secret' => $secret
    );
  }
  else
  {
    if (isset($_GET['oauth_token']) && empty($_SESSION['yahoo']['token']))
    {
      $access_token_info = $yahoo->exchangeAccessToken($_GET['oauth_token'], $_SESSION['yahoo']['secret']);
      var_dump($access_token_info);exit();

      $_SESSION['yahoo']['token'] = $access_token_info['oauth_token'];
      $_SESSION['yahoo']['secret'] = $access_token_info['oauth_token_secret'];
      $yahoo->setToken($_SESSION['yahoo']['token'], $_SESSION['yahoo']['secret']);
    }

    if (!empty($_SESSION['yahoo']['token']))
    {
      $yahoo->setToken($_SESSION['yahoo']['token'], $_SESSION['yahoo']['secret']);

      $me = $yahoo->fetch('http://api.yahoo.com/auth/verify_credentials.oauth', array(), 'GET');
    }
  }
}
catch (OAuthClientException $err)
{
  // do nothing;
}
?>
<html>
<head>
</head>
<body>
<?php if ($me):?>
<p>I am <?php echo htmlentities($me['nickName']);?>.<a href="?logout=1">Logout</a><p>
<?php else: ?>
<a href="<?php echo $authorization_url;?>">Login to yahoo</a>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
