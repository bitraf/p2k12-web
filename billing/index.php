<?
$admins = array('morten.hustveit@gmail.com');
$errors = array();

require_once('lib/google-auth.php');
require_once('lib/html.php');

//require_admin();
if (false === pg_connect('dbname=p2k12 user=p2k12'))
{
  echo "PostgreSQL connection error\n";

  exit;
}

$paying_member_res = pg_query(<<<SQL
SELECT COUNT(account) AS paid FROM active_members WHERE price > 0;
SQL
  );

$payments_res = pg_query(<<<SQL
SELECT paid_date, SUM(amount) OVER (PARTITION BY name ORDER BY paid_date) AS sum, name FROM memberships.payments JOIN public.accounts ON id = account_id ORDER BY name, paid_Date;
SQL
  );

if ($payments_res === false)
  $errors[] = 'PostgreSQL error: ' . html(pg_last_error());

$periods_res = pg_query(<<<SQL
SELECT start_date, end_date, SUM(price) OVER (PARTITION BY name ORDER BY start_date) AS sum, name FROM memberships.membership_periods JOIN accounts ON id = account_id;
SQL
  );

if ($periods_res === false)
  $errors[] = 'PostgreSQL error: ' . html(pg_last_error());
?>
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Droid Sans, sans-serif; }
    td, th { text-align: left; vertical-align: top; padding: 0 10px; }
    tr td:first-child,
    tr th:first-child { padding-left: 0; }
    .n { text-align: right; }
    .details { font-size: .8em; padding: 20px; color: #333; }
    .details h2 { margin: 0; font-size: 1.0em; font-weight: bold; }
  </style>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <title>p2k12: billing</title>

<body>
<? 
while($row = pg_fetch_array($paying_member_res)){
echo "There are ". $row["paid"]. " members with the price > 0;";
break;
}
?>
  <h2>Payments</h2>
  <table>
  <? while ($payment = pg_fetch_assoc($payments_res)): ?>
    <tr>
      <td><?=$payment['paid_date']?>
      <td><?=$payment['sum']?>
      <td><?=$payment['name']?> 
  <? endwhile ?>
  </table>
  <h2>Membership Periods</h2>
  <table>
  <? while ($period = pg_fetch_assoc($periods_res)): ?>
    <tr>
      <td><?=$period['start_date']?>
      <td><?=$period['end_date']?>
      <td><?=$period['sum']?>
      <td><?=$period['name']?> 
  <? endwhile ?>
  </table>
