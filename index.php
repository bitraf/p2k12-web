<?
header('Content-Type: text/html; charset=utf-8');
require_once('db-connect-string.php');
pg_connect($db_connect_string);

$res = pg_query('SELECT * FROM all_balances WHERE balance != 0 OR stock != 0');
?>
<!DOCTYPE html>
<title>p2k12 accounts</title>
<style>
  body { font-family: sans-serif; }
</style>
<!-- Global site tag (gtag.js) - Google Analytics, MH's account -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-29715437-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-29715437-1');
</script>

<?
while ($row = pg_fetch_assoc($res))
{
  if (!isset($type) || $row['type'] != $type)
  {
    if (isset($type))
    {
      ?></table><?
    }

    ?>
    <h2><?=$row['type']?></h2>
    <table>
      <colgroup>
        <col width='200'>
        <col width='100'>
        <col width='100'>
        <col width='100'>
      </colgroup>
      <tr>
        <th>Name
        <th style='text-align:right'>Assets
        <th style='text-align:right'>Liabilities
        <th style='text-align:right'>Stock
    <?

    $type = $row['type'];
  }

  ?>
  <tr>
    <td><?=$row['name']?>

    <? if ($row['balance'] > 0): ?>
      <td style='text-align: right'><?=$row['balance']?>
    <? else: ?>
      <td>
    <? endif ?>

    <? if ($row['balance'] < 0): ?>
      <td style='text-align: right'><?=-$row['balance']?>
    <? else: ?>
      <td>
    <? endif ?>

      <td style='text-align: right'><?=$row['stock']?>
  <?
}

if (isset($type))
{
  ?></table><?
}
