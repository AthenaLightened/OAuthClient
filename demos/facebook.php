<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$facebook = OAuthClientFactory::facebook('365905070171991', 'e7112b15c77c050ec41b331689f6827a', 'http://php.localhost/OAuthClient/demos/facebook.php');

// logout
if (!empty($_GET['logout']))
{
  $_SESSION = array();
}

$err = NULL;
$me = NULL;
$posted_feed = NULL;
try
{
  $authorization_url = $facebook->getAuthorizationUrl(array('scope' => 'publish_stream'));
  if (isset($_GET['code']) && empty($_SESSION['facebook']))
  {
    // exchange the token
    $token = $facebook->exchangeAccessToken($_GET['code']);

    $_SESSION['facebook'] = $token;

    // set the token
    $facebook->setToken($token['access_token']);

    // send a facebook
    $posted_feed = $facebook->fetch('me/feed', array(
      'message' => 'Hello world! at ' . date('Y-m-d H:i:s')
    ), 'POST');
  }

  if (!empty($_SESSION['facebook']))
  {
    $facebook->setToken($_SESSION['facebook']['access_token']);
    $me = $facebook->fetch('me', array(), 'GET');
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
<p>I am <?php echo htmlentities($me['name'], ENT_COMPAT, 'UTF-8');?>.<a href="?logout=1">Logout</a><p>
<?php else: ?>
<a href="<?php echo $authorization_url;?>">Login to facebook</a>
<?php endif;?>

<?php if ($posted_feed):?>
<p>Posted: <?php echo $posted_feed['id'];?></p>
<p><a href="http://www.facebook.com/" target="_blank">Click to view the posted feed.</a></p>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
