<?
require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query("SELECT ac.id + 100000 AS id, ac.name FROM auth au INNER JOIN accounts ac ON ac.id = au.account WHERE au.realm = 'login'");

header('Content-Type: text/plain; charset=utf-8');

$accounts = array();

while ($row = pg_fetch_assoc($res))
{
  $accounts[] = $row['name'];
  echo "{$row['name']}:x:{$row['id']}\n";
}

echo "drift:x:100000:" . join(',', $accounts) . "\n";
