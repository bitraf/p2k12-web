<?php
header('Content-Type: application/vnd.collection+json');
require_once('db-connect-string.php');
pg_connect($db_connect_string);

$from = NULL;
$matches = NULL;
if (isset($_GET["from"])) {
  $x = preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $_GET["from"], $matches);
  if($x != 1) {
    header("HTTP/1.1 400 Bad request, x=" . $x);
    return;
  }
  $from = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
  // header("from: " . $from);
}

$to = NULL;
if (isset($_GET["to"])) {
  $x = preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $_GET["to"], $matches);
  if($x != 1) {
    header("HTTP/1.1 400 Bad request, x=" . $x);
    return;
  }
  $to = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
  // header("to: " . $to);
}

// TODO: Consider adding queries for searching for certain time intervals instead of loading everything
?>
{ "collection" :
  {
    "version" : "1.0",
    "links" : [],
    "items" : [
<?php
$query = "SELECT TO_CHAR(date, 'YYYY-MM-DD') as date, COUNT(DISTINCT(account)) as count FROM checkins WHERE";
if($from) {
  $query .= " date >= '$from' AND";
}
if($to) {
  $query .= " date < '$to' AND";
}
$query .= " 1=1 GROUP BY TO_CHAR(date, 'YYYY-MM-DD') ORDER BY TO_CHAR(date, 'YYYY-MM-DD')";

$res = pg_query($query);

// header("Query: $query");

$first = TRUE;
while ($row = pg_fetch_assoc($res))
{
  if($first) {
    $first = FALSE;
  }
  else {
    echo ",";
  }
?>
      {
        "data" : [
          {"name" : "date", "value" : "<?=$row['date']?>"},
          {"name" : "checkins", "value" : "<?=$row['count']?>"}
        ]
      }
<?php
}
?>
    ]
  }
}
