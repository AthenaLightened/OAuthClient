<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$weibo = OAuthClientFactory::qqWeibo('801294226', '2aa3f28efcce4a0c0d54bddbb2a3fdd5', 'http://php.localhost/OAuthClient/demos/qq_weibo.php');

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
    $token['openid'] = $_GET['openid'];
    $token['openkey'] = $_GET['openkey'];

    $_SESSION['weibo'] = $token;

    // set the token
    $weibo->setToken($token['access_token']);

    // send a weibo
    $params = array(
      'oauth_consumer_key' => $weibo->oauthConfig['client_id'],
      'openid' => $_SESSION['weibo']['openid'],
      'oauth_version' => '2.a',
      'content' => 'Hello world! at ' . date('Y-m-d H:i:s'),
      '@pic' => '@' . realpath('./php.gif')
    );
    $posted_weibo = $weibo->fetch('t/add_pic', $params, 'POST');
  }

  if (!empty($_SESSION['weibo']))
  {
    $weibo->setToken($_SESSION['weibo']['access_token']);
    $params = array(
      'oauth_consumer_key' => $weibo->oauthConfig['client_id'],
      'openid' => $_SESSION['weibo']['openid'],
      'oauth_version' => '2.a',
    );
    $me = $weibo->fetch('user/info', $params, 'GET');
  }
}
catch (OAuthClientException $err)
{
  // do nothing;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<?php if ($me):?>
<p>I am <?php echo htmlentities($me['data']['nick'], ENT_COMPAT, 'UTF-8');?>.<a href="?logout=1">Logout</a><p>
<?php else: ?>
<a href="<?php echo $authorization_url;?>">Login to QQ weibo</a>
<?php endif;?>

<?php if ($posted_weibo):?>
<p>Posted: <?php echo $posted_weibo['data']['id'];?></p>
<p><a href="http://t.qq.com/<?php echo $me['data']['name'];?>" target="_blank">Click to view the posted weibo.</a></p>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
