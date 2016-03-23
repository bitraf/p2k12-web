<?
$first = true;
$today = false;
$yesterday = false;

if ($_GET['interval'] == '1 day')
  $response = "unike siste 24 timer";

foreach ($payload as $checkin)
{
  if ($_GET['interval'] == '1 day')
  {
    if (!$today && date('Ymd') == date('Ymd', strtotime($checkin['date'])))
    {
      $response .= ", senest i dag: ";
      $today = true;
    }
    else if (!$yesterday && date('Ymd') != date('Ymd', strtotime($checkin['date'])))
    {
      $response .= ", senest i går: ";
      $yesterday = true;
    }
    else
    {
      $response .= ", ";
    }

    $response .= sprintf("%s %s", substr($checkin['date'], 11, 5), $checkin['name']);
  }
  else
  {
    if (!$first)
      $response .= ", ";

    $response .= sprintf("%s %s", substr($checkin['date'], 0, 16), $checkin['name']);
  }

  $first = false;
}

$response = str_replace(", [", " [", $response);
echo $response;
