<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$tudou = OAuthClientFactory::tudou('013ebe46ef976d1a', 'eda09223a9d940bba00f809b99c17171');

// logout
if (!empty($_GET['logout']))
{
  $_SESSION = array();
}

$err = NULL;
$me = NULL;
try
{
  if (empty($_SESSION['tudou']))
  {
    list($secret, $authorization_url) = $tudou->getAuthorizationUrl();
    $_SESSION['tudou'] = array(
      'secret' => $secret
    );
  }
  else
  {
    if (isset($_GET['oauth_token']) && empty($_SESSION['tudou']['token']))
    {
      $access_token_info = $tudou->exchangeAccessToken($_GET['oauth_token'], $_SESSION['tudou']['secret']);

      $_SESSION['tudou']['token'] = $access_token_info['oauth_token'];
      $_SESSION['tudou']['secret'] = $access_token_info['oauth_token_secret'];
      $tudou->setToken($_SESSION['tudou']['token'], $_SESSION['tudou']['secret']);
    }

    if (!empty($_SESSION['tudou']['token']))
    {
      $tudou->setToken($_SESSION['tudou']['token'], $_SESSION['tudou']['secret']);

      $me = $tudou->fetch('http://api.tudou.com/auth/verify_credentials.oauth', array(), 'GET');
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
<a href="<?php echo $authorization_url;?>">Login to tudou</a>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
