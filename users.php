<?
require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query("SELECT ac.id + 100000 AS id, ac.name, am.full_name FROM accounts ac JOIN active_members am ON am.account = ac.id ORDER BY full_name");

header('Content-Type: text/plain; charset=utf-8');

function utfPadding($str, $len) {
  $name_len = iconv_strlen($str, 'UTF-8');
  $padding = "";
  if ($len > $name_len) {
    $padding = str_repeat ( " ", $len-$name_len);
  }
  return $padding . $str;
}

while ($row = pg_fetch_assoc($res))
{
  printf("%s %s\n",utfPadding ($row['full_name'], 40), utfPadding($row['name'], 40));
}
