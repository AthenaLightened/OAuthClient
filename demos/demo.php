<?php
session_start();
include_once('../src/OAuth2Client.php');
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') 
  || $_SERVER['SERVER_PORT'] == 443)
{
  $protocol = 'https://';
}
else
{
  $protocol = 'http://';
}
$redirect = sprintf('%s%s%s', $protocol, $_SERVER['HTTP_HOST'], $_SERVER['PHP_SELF']);
$clients = array();
$configs = array(
  'weibo' => array(
    'name' => '新浪微博',
    'client_id' => '2190273302',
    'client_secret' => '5ab2cf740ba174e198d72a63cca20c9b'
  ),
  'qq' => array(
    'name' => '腾讯微博',
    'client_id' => '801294226',
    'client_secret' => '2aa3f28efcce4a0c0d54bddbb2a3fdd5'
  ),
  'renren' => array(
    'name' => '人人网',
    'client_id' => 'b3503ba7d2f14ed7b559bb68a160cc91',
    'client_secret' => '4b2d9c8d4db44c018d85986315a2a60c'
  ),
  'baidu' => array(
    'name' => '百度',
    'client_id' => 'rj7PIHeb004XB5dMioLdDpNN',
    'client_secret' => 'QA437CsMkQ2oF3q8Tch1WoihDkFKiBGK'
  )
);
foreach ($configs as $platform => $config)
{
  $config['redirection_endpoint'] = $redirect;
  $clients[$platform] = OAuth2Client::create($platform, $config);
}

// process callbacks
$state = isset($_GET['state']) ? $_GET['state'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';
$err = NULL;
try
{
  if (!isset($_SESSION[$state]) && $code)
  {
    $clients[$state]->exchangeAccessToken();
    $_SESSION[$state] = $clients[$state]->getAccessToken();
  }
}
catch (Exception $err)
{
  // do nothing
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="xu.li<AthenaLightenedMyPath@gmail.com>">
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/css/bootstrap-combined.min.css" rel="stylesheet">
  </head>

  <body>
    <div class="container">
      <form>
        <fieldset>
          <legend>Demo</legend>
          <label for="opt_platform">Choose a platform:</label>
          <select id="opt_platform">
            <option value=''></option>
            <?php foreach ($clients as $platform => $client):?>
            <?php if (!isset($_SESSION[$platform])):?>
            <option value="<?php echo $platform;?>" data-url="<?php echo $client->getAuthorizationUrl('', $platform);?>">
              <?php echo $configs[$platform]['name'];?>
            </option>
            <?php endif;?>
            <?php endforeach;?>
          </select>

          <?php if ($err):?>
          <legend>Error: </legend>
          <p>Message: <?php echo safe_html_entities($err->getMessage());?></p>
          <?php foreach (array('url', 'response_code', 'response') as $key):?>
            <?php if (!empty($err->$key)):?>
            <p><?php echo $key;?>: <?php echo safe_html_entities($err->$key);?></p>
            <?php endif;?>
          <?php endforeach;?>
          <?php endif;?>

          <legend>Authenticated Platforms</legend>
          <?php if (!empty($_SESSION)):?>
          <ul class="unstyled" id="platform_list">
            <?php foreach ($_SESSION as $platform => $token):?>
            <li>
              <p><a href="javascript:void(0)" style="color: #333"><strong><?php echo $configs[$platform]['name'];?></strong></a></p>
              <div class="attr_list hide">
                <?php if (!isset($token['scope'])):?>
                <p>scope: <?php echo $clients[$platform]->getScope();?></p>
                <?php endif;?>
                <?php foreach ($token as $key => $value):?>
                <p><?php echo $key;?>: <?php echo safe_html_entities($value);?></p>
                <?php endforeach; ?>
              </div>
            </li>
            <?php endforeach;?>
          </ul>
          <?php else:?>
          <p>None</p>
          <?php endif;?>
        </fieldset>
      </form>

    </div> <!-- /container -->

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script>
    var GLOBALS = {};
    GLOBALS.Session = <?php echo json_encode($_SESSION);?>;
    $(document).ready(function () {
      $('#opt_platform').change(function () {
        var platform = $(this).val();
        if (!platform) {
          return ;
        }
        if (platform in GLOBALS.Session) {
          return ;
        }

        document.location.href = $(this).find('option:selected').attr('data-url');
      });

      $('#platform_list').on('click', 'a', function (e) {
        $(e.currentTarget).parent().next().toggle();
      });
    });
    </script>
  </body>
</html>
<?php
function safe_html_entities($val)
{
  return htmlentities(is_string($val) ? $val : json_encode($val), ENT_COMPAT, 'utf-8');
}
