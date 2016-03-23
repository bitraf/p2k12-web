<?
if (isset($_GET['media-type']) && $input_media_type == $_GET['media-type'])
{
  $media_type = $input_media_type;
  $media_handler = $input_media_handler;
}
else if (isset($_GET['media-type']) && isset($media_type_conversions["$input_media_type {$_GET['media-type']}"]))
{
  $media_type = $_GET['media-type'];
  $media_quality = 1.0;
  $media_handler = $media_type_conversions["$input_media_type {$_GET['media-type']}"];
}
else if (isset($_SERVER['HTTP_ACCEPT']) && sizeof($_SERVER['HTTP_ACCEPT']))
{
  $media_quality = 0;

  $accept = array();

  foreach (explode(',', $_SERVER['HTTP_ACCEPT']) as $header)
  {
    $tmp = explode(';q=', $header, 2);

    $accept[$tmp[0]] = (sizeof($tmp) < 2) ? 1.0 : $tmp[1];
  }

  arsort($accept);

  foreach ($accept as $type => $q)
  {
    if ($q <= $media_quality)
      continue;

    if ($type == '*/*' || $type == $input_media_type)
    {
      $media_type = $input_media_type;
      $media_quality = $q;
      $media_handler = $input_media_handler;
    }
    elseif (isset($media_type_conversions["$input_media_type $type"]))
    {
      $media_type = $type;
      $media_quality = $q;
      $media_handler = $media_type_conversions["$input_media_type $type"];
    }
  }

  if (!isset($media_type))
  {
    header('HTTP/1.1 406 Not Acceptable');

    echo "406 Not Acceptable\n";

    exit;
  }
}
else
{
  $media_type = $input_media_type;
  $media_handler = $input_media_handler;
}
