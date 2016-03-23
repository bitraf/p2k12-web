<?
function parse_accept_headers($default = 'text/html')
{
  if (!isset($_SERVER['HTTP_ACCEPT']) || !sizeof($_SERVER['HTTP_ACCEPT']))
    return $default;

  $formats = array(
    'text/*' => 'text/html',
    'text/html' => 'text/html',
    'text/csv' => 'text/csv',
    'application/*' => 'application/json',
    'application/json' => 'application/json',
    '*/*' => 'text/html',
    '' => 'text/html',
    );

  $accept = array();

  foreach (explode(',', $_SERVER['HTTP_ACCEPT']) as $header)
  {
    list($mime, $q) = explode(';q=', $header);
    $accept[$mime] = ($q === null)? 1 : $q;
  }

  arsort($accept);

  foreach ($accept as $format => $q)
    if ($formats[$format])
      break;

  return $formats[$format];
}
