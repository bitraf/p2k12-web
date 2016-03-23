<?
require_once('lib/accept.php');

$content_type = parse_accept_headers();
pg_query("SET TIME ZONE 'CET'");

if (isset($_GET['time-format']) && $_GET['time-format'] == 'absolute')
  $time_format = 'absolute';
else
  $time_format = 'relative';

require_once('db-connect-string.php');
pg_connect($db_connect_string);

if ($time_format == 'absolute')
{
  $res = pg_query("SELECT a.name, MAX(date) AS date FROM checkins c INNER JOIN accounts a ON c.account = a.id GROUP BY a.name HAVING NOW() - MAX(date) < INTERVAL '14 days' ORDER BY MAX(date) DESC;");
}
else
{
  $res = pg_query("SELECT a.name, NOW() - MAX(date) elapsed FROM checkins c INNER JOIN accounts a ON c.account = a.id GROUP BY a.name  HAVING NOW() - MAX(date) < INTERVAL '14 days' ORDER BY elapsed ASC;");
}

$entries = array();

while ($row = pg_fetch_assoc($res))
  $entries[] = $row;

if ($content_type == 'text/html')
{
  header("Content-Type: $content_type; charset=utf-8");
?>
<!DOCTYPE html>
<title>p2k12 checkins last 14 days</title>
<style>
  body { font-family: sans-serif; }
  table { border-spacing: 0; border-collapse: collapse; }
  th { text-align: left; background: #eee; }
  th, td { border: 1px solid #ccc; padding: 2px 10px; white-space: nowrap; }
  td { text-align: right; }
  .m { color: #007; }
  .f { color: #f57; }
</style>
<table>
<?

$count = 0;

foreach ($entries as $row)
{
  $class = "m";

  if (in_array($row['name'], array('mulm', 'minimulm', 'magdalej')))
    $class = "f";
  ?>
  <tr>
    <td class="<?=$class?>"><?=$row['name']?>
    <td><?=$row[($time_format == 'absolute')?'date':'elapsed']?>
  <?
  ++$count;
}
?>
</table>
<p><?=$count?> unique
<?
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
