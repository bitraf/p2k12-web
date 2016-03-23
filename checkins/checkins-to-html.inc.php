<!DOCTYPE html>
<head>
  <title>Checkins</title>
<body>
<h1>Checkins</h1>
<table>
  <tr>
    <th>Date
    <th>User name
<?
foreach ($payload as $checkin)
{
  ?>
  <tr>
    <td><?=$checkin['date']?>
    <td><?=$checkin['name']?>
  <?
}
