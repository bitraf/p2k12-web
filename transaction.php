<?
require_once('lib/accept.php');

$content_type = parse_accept_headers();

require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query('SELECT * FROM pretty_transaction_lines');

if ($content_type == 'text/csv')
{
  header('Content-Type: text/csv; charset=utf-8');
  while ($row = pg_fetch_assoc($res))
  {
    echo $row["transaction"] . "\t" . $row["debit_account"] . "\t" . $row["debit_account_name"] . "\t" . $row["credit_account"] . "\t" . $row["credit_account_name"] . "\t" . $row["amount"] . "\t" . $row["currency"] . "\t" . $row["stock"] . "\n";
  }
}
else
{
  header('Content-Type: text/html; charset=utf-8');
  ?>
  <!DOCTYPE html>
  <title>p2k12 transactions</title>
  <style>
    body { font-family: sans-serif; }
  </style>
  <table>
      <tr>
        <td style='text-align:right'>TID
        <td colspan='2'>Debit account
        <td colspan='2'>Credit account
        <td>Amount
        <td>Currency
        <td>Stock
  <?
  while ($row = pg_fetch_assoc($res))
  {
    ?>
      <tr>
        <td style='text-align:right'><?=$row["transaction"]?>
        <td style='text-align:right'><?=$row["debit_account"]?>
        <td><?=$row["debit_account_name"]?>
        <td style='text-align:right'><?=$row["credit_account"]?>
        <td><?=$row["credit_account_name"]?>
        <td style='text-align:right'><?=$row["amount"]?>
        <td><?=$row["currency"]?>
        <td style='text-align:right'><?=$row["stock"]?>
    <?
  }
}
