<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$weibo = OAuthClientFactory::weibo('2190273302', '5ab2cf740ba174e198d72a63cca20c9b', 'http://php.localhost/OAuthClient/demos/weibo.php');

// logout
if (!empty($_GET['logout']))
{
  $_SESSION = array();
}

$err = NULL;
$me = NULL;
$posted_weibo = NULL;
try
{
  $authorization_url = $weibo->getAuthorizationUrl();
  if (isset($_GET['code']) && empty($_SESSION['weibo']))
  {
    // exchange the token
    $token = $weibo->exchangeAccessToken($_GET['code']);

    $_SESSION['weibo'] = $token;

    // set the token
    $weibo->setToken($token['access_token']);

    // send a weibo
    $posted_weibo = $weibo->fetch('statuses/upload.json', array(
      'status' => 'Hello world! at ' . date('Y-m-d H:i:s'),
      '@pic' => '@' . realpath('./php.gif')
    ), 'POST');
  }

  if (!empty($_SESSION['weibo']))
  {
    $weibo->setToken($_SESSION['weibo']['access_token']);
    $me = $weibo->fetch('users/show.json', array('uid' => $_SESSION['weibo']['uid']), 'GET');
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
<p>I am <?php echo htmlentities($me['name']);?>.<a href="?logout=1">Logout</a><p>
<?php else: ?>
<a href="<?php echo $authorization_url;?>">Login to weibo</a>
<?php endif;?>

<?php if ($posted_weibo):?>
<p>Posted: <?php echo $posted_weibo['idstr'];?></p>
<p><a href="http://weibo.com/u/<?php echo $_SESSION['weibo']['uid'];?>" target="_blank">Click to view the posted weibo.</a></p>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
