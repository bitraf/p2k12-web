<?
require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query("SELECT ac.id + 100000 AS id, ac.name, au.data FROM auth au INNER JOIN accounts ac ON ac.id = au.account WHERE au.realm = 'login' AND ac.name NOT LIKE '% %'");

header('Content-Type: text/plain; charset=utf-8');
echo "mkdir -p /bitraf\n";
echo "chown root:root /bitraf\n";
echo "chmod 775 /bitraf\n";

echo "mkdir -p /bitraf/home\n";
echo "chown root:root /bitraf/home\n";
echo "chmod 775 /bitraf/home\n";

while ($row = pg_fetch_assoc($res))
{
  echo "mkdir -p /bitraf/home/{$row['name']}\n";
  echo "chown {$row['id']}:{$row['id']} /bitraf/home/{$row['name']}\n";
  echo "chmod 775 /bitraf/home/{$row['name']}\n";
}
