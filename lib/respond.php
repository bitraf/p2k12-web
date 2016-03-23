<?
function respond($code, $message, $body = false, $content_type = 'text/plain')
{
  if (!$body)
    $body = $message;

  header("HTTP/1.1 $code $message");
  header("Content-Type: $content_type");
  echo $body;

  exit;
}

function respond_303($uri)
{
  header('HTTP/1.1 303 See Other');
  header('Location: ' . $uri);
  header("Content-Type: text/html");

  echo '<!DOCTYPE html><title>See Other</title><p><a href="' . htmlentities($uri, ENT_QUOTES, 'utf-8') . '">' . htmlentities($uri, ENT_NOQUOTES, 'utf-8') . '</a>';

  exit;
}
