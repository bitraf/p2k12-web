<?
require_once('lib/accept.php');

$content_type = parse_accept_headers();

require_once('db-connect-string.php');
pg_connect($db_connect_string);
$res = pg_query('SELECT COUNT(*) AS count, a.date FROM (SELECT DISTINCT account, date::DATE FROM checkins ORDER BY date ASC) a GROUP BY a.date ORDER BY a.date');
$entries = array();
while ($row = pg_fetch_assoc($res))
  $entries[] = $row;

if ($content_type == 'text/html')
{
  header("Content-Type: $content_type; charset=utf-8");
?>
<!DOCTYPE html>
<title>p2k12 accounts</title>
<style>
  body { font-family: sans-serif; }
  table { border-spacing: 0; border-collapse: collapse; }
  th { text-align: left; background: #eee; }
  th, td { border: 1px solid #ccc; padding: 2px 10px; white-space: nowrap; }
  td { text-align: right; }
</style>
<p>Check-ins became mandatory at 2012-04-19.</p>
<table>
<?

foreach ($entries as $row)
{
  if ($row['date'] == '2012-04-19')
  {
    ?>
    <tr>
      <td colspan='2' style='border-top:2px solid black; padding: 0;'>
    <?
  }
  ?>
  <tr>
    <td><?=$row['date']?>
    <td><?=$row['count']?>
  <?
}
}
else if ($content_type == 'application/json')
{
  header("Content-Type: $content_type; charset=utf-8");
  echo json_encode($entries);
}
else
{
  header('HTTP/1.1 406 Not Acceptable');
  header("Content-Type: text/plain; charset=utf-8");

  echo "No acceptable content types supported.\n";
}
