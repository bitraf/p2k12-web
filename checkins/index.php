<?
require_once('../db-connect-string.php');
pg_connect($db_connect_string);

date_default_timezone_set('Europe/Oslo'); 
pg_query("SET TIME ZONE 'CET'");

if (isset($_GET['interval']))
{
  $res = pg_query_params("SELECT MAX(c.date) date, a.name FROM checkins c INNER JOIN accounts a ON a.id = c.account  WHERE c.type='checkin' AND c.date > NOW() - $1::INTERVAL GROUP BY a.name ORDER BY MAX(c.date) DESC", array($_GET['interval']));
  $payload = array();

  while ($row = pg_fetch_assoc($res))
    $payload[] = $row;

  $input_media_type = 'application/vnd.bitraf.checkins-json';
  $input_media_handler = 'dump-json.inc.php';
}
else
{
  ?><!DOCTYPE html?>
  <html>
    <head>
      <title>Bitraf checkins</title>
    <body>
      <form method='GET' action='/checkins/' class='http://p2k12.bitraf.no/api#checkins'>
        <p>Interval:<br><input type='text' name='interval' value='1 day'></p>
        <p>Media type:<br><select name='media-type'><option>application/vnd.bitraf.checkins-json</option><option>text/plain</option><option>text/html</option></select></p>
        <p><input type='submit'></p>
      </form>
  <?

  exit;
}

$media_type_conversions = array(
  'application/vnd.bitraf.checkins-json text/plain' => 'checkins-to-text.inc.php',
  'application/vnd.bitraf.checkins-json text/html' => 'checkins-to-html.inc.php');

require_once('media-type.php');

header("Content-Type: $media_type");

require_once($media_handler);
