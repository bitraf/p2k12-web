<?
function check_ip($addr, $networks) {
  $is_ipv6 = filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

  if (!$is_ipv6) {
    $addr = ip2long($addr);
  }

  foreach ($networks as $network) {
    if ($network[0] == '/') {
      if (!$is_ipv6) continue;

      if (1 === preg_match($network, $addr)) return true;
    } else {
      if ($is_ipv6) continue;

      list ($net_addr, $net_mask) = explode ("/", $network);

      $net_addr = ip2long($net_addr);
      $net_mask = ip2long($net_mask);

      if (($addr & $net_mask) == ($net_addr & $net_mask)) return true;
    }
  }

  return false;
}

$networks = file_get_contents('bitraf-networks.txt');
$networks = array_filter(explode("\n", $networks));

if (!check_ip($_SERVER['REMOTE_ADDR'], $networks)) {
  header('HTTP/1.1 403 Forbidden');
  header('Content-Type: text/plain');
  echo "403 Forbidden";
  exit;
}


require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query("SELECT ac.id + 100000 AS id, ac.name, au.data, am.full_name FROM auth au INNER JOIN accounts ac ON ac.id = au.account INNER JOIN active_members am ON am.account = ac.id WHERE au.realm = 'login'");

header('Content-Type: text/plain; charset=utf-8');

while ($row = pg_fetch_assoc($res))
{
  echo "{$row['name']}:{$row['data']}:{$row['id']}:{$row['id']}:{$row['full_name']}:/bitraf/home/{$row['name']}:/bin/bash\n";
}
