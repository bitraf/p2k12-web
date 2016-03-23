<?
include 'get_macaddr.php';

$mac_addresses = get_macaddr();

function MacAddressFilter($addr) {
  return IsValid($addr) && $addr !== '7C:6D:62:CF:75:9A';
}
$mac_addresses = array_filter($mac_addresses, 'MacAddressFilter');

if (!sizeof($mac_addresses)) {
  echo 'No MAC addresses found';
  exit;
}

printf('%d MAC addresses found:', sizeof($mac_addresses));

if (false === pg_connect("dbname=p2k12 user=p2k12"))
  echo (' PostgreSQL connect failed');

$first = true;
$i = 0;
$users = "";
foreach ($mac_addresses as $t)
{
  foreach ($t as $tt)
  {
    if (IsValid($tt) &&  preg_match('/[^a-zA-Z\d]/', $tt))
    {
      //ignore bitmart tablet
      if (strcmp("7C:6D:62:CF:75:9A", $tt) != 0)
      {
        //echo strtolower($tt) . "\n";
        $tt = strtolower($tt);
        $res = pg_query_params("SELECT account FROM mac WHERE macaddr=$1", array($tt));
        if (false === $res)
        {
          echo "Query failed: " . pg_last_error();
          exit;
        }

        $account = pg_fetch_assoc($res);

        if ($account['account'] != NULL)
        {
          $res = pg_query_params("SELECT name FROM accounts WHERE id=$1", array($account['account']));

          $n = pg_fetch_assoc($res);
          if ($first == false)
            $users .= ", ";
          $users .= $n['name'];
          $first = false;
        }
      }
    }
  }
}

echo $users;
?>
