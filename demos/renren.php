<?php
session_start();
include_once('../libs/OAuthClientFactory.php');
$renren = OAuthClientFactory::renren('b3503ba7d2f14ed7b559bb68a160cc91', '4b2d9c8d4db44c018d85986315a2a60c', 'http://php.localhost/OAuthClient/demos/renren.php');

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
  $authorization_url = $renren->getAuthorizationUrl(array('scope' => 'publish_feed'));
  if (isset($_GET['code']) && empty($_SESSION['renren']))
  {
    // exchange the token
    $token = $renren->exchangeAccessToken($_GET['code']);

    $_SESSION['renren'] = $token;

    // set the token
    $renren->setToken($token['access_token']);

    // send a renren
    $params = array(
      'v' => '1.0',
      'format' => 'json',
      'method' => 'feed.publishFeed',
      'access_token' => $_SESSION['renren']['access_token'],
      'uids' => $_SESSION['renren']['user']['id'],
      'client_id' => $renren->oauthConfig['client_id'],
      'name' => 'test',
      'description' => 'Hello world! at ' . date('Y-m-d H:i:s'),
      'url' => $_SERVER['HTTP_HOST'],
      'message' => 'It just works.'
    );
    $params['sig'] = generateSig($renren->oauthConfig['client_secret'], $params);
    $posted_feed = $renren->fetch('', $params, 'POST');
  }

  if (!empty($_SESSION['renren']))
  {
    $renren->setToken($_SESSION['renren']['access_token']);

    $params = array(
      'v' => '1.0',
      'format' => 'json',
      'method' => 'users.getInfo',
      'access_token' => $_SESSION['renren']['access_token'],
      'uids' => $_SESSION['renren']['user']['id'],
      'client_id' => $renren->oauthConfig['client_id']
    );
    $params['sig'] = generateSig($renren->oauthConfig['client_secret'], $params);
    $me = $renren->fetch('', $params, 'POST');
  }
}
catch (OAuthClientException $err)
{
  // do nothing;
}

function generateSig($secret, $params)
{
  $formatted = array();
  foreach ($params as $k => $v)
  {
    $formatted[] = $k . '=' . $v;
  }
  sort($formatted);

  return md5(implode('', $formatted) . $secret);
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<?php if ($me):?>
<p>I am <?php echo htmlentities($me[0]['name'], ENT_COMPAT, 'UTF-8');?>.<a href="?logout=1">Logout</a><p>
<?php else: ?>
<a href="<?php echo $authorization_url;?>">Login to renren</a>
<?php endif;?>

<?php if ($posted_feed):?>
<p>Posted: <?php echo $posted_feed['post_id'];?></p>
<p><a href="http://www.renren.com" target="_blank">Click to view the posted feed.</a></p>
<?php endif;?>

<?php if ($err):?>
<p>Error: </p>
<p><?php echo var_export($err, TRUE);?></p>
<?php endif;?>
</body>
</html>
