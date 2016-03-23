<?
if (isset($_POST))
{
  if (isset($_POST['user_name']) && isset($_POST['mac_adress']))
  {
    if (false === pg_connect("dbname=p2k12 user=p2k12"))
      respond('500', 'PostgreSQL connect failed');

    pg_query("SET TIME ZONE 'CET'");

    $user_name = $_POST['user_name'];
    $mac_adress = $_POST['mac_adress'];
    $device = $_POST['device_string'];

    $res = pg_query_params("SELECT id FROM accounts WHERE name=$1", array($user_name));

    if (false === $res)
      echo "PostgreSQL query error [0]";

    $user = pg_fetch_assoc($res);

    //echo "Register mac address {$mac_adress} for user {$user_name} with id {$user['id']}";
    //echo "<br>Registered mac for {$user_id}";

    if (false === @pg_query_params('INSERT INTO mac (account, macaddr, device) VALUES ($1, $2, $3)', array($user['id'], $mac_adress, $device)))
    {
      echo "PostgreSQL query error [1]";
    }
    else
    {
      echo "You'r mac address is registered in p2k12";
      exit;
    }
  }
}
?>

<!DOCTYPE html>
  <html>
    <head>
      <title>Register MAC checkins</title>
      <body>
      <h1>WLAN MAC register</h1>
      <form action='index.php' method='POST'>
        <label for='user_name'>User name</label>
        <input type='text' name='user_name' value=''>
      <br>
        <label for='mac_adress'>Mac adress</label>
        <input type='text' name='mac_adress' value=''>
      <br>
        <label for='device'>Device type</label>
        <select name='device_string'>
        <option value='PC' selected='selected'>PC</option>
        <option value='MOBILE'>Mobile</option>
        </select>
        <p><input type='submit'></p>
      </form>
</body>

