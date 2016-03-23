<?
require_once('lib/respond.php');
require_once('lib/p2k12.php');

setlocale (LC_ALL, 'nb_NO.UTF-8');
date_default_timezone_set ('Europe/Oslo');

require_once('db-connect-string.php');
if (false === pg_connect($db_connect_string))
    respond('500', 'PostgreSQL connect failed');

$text = '';

if (isset($_POST['email']))
{
  // Get membership details
  if (false === ($member_res = pg_query_params("SELECT account, price, recurrence, full_name, email, organization, flag FROM active_members WHERE upper(email) = upper($1)", array($_POST['email']))))
    respond('500', 'PostgreSQL query error');
  
  if (!pg_num_rows($member_res))
    respond('404', 'Ugyldig epost');

  $member = pg_fetch_assoc($member_res);

  $account = $member['account'];

  $link = 'https://' . $_SERVER['HTTP_HOST'] . "/mystripe.php?id={$account}&signature=" . hash_for_account($account);

  ob_start();
  include 'templates/getmylink_email.txt';
  $text_body = ob_get_clean();
  
  $to = $member['email'];
  $from = 'post@bitraf.no';
  $subject = 'Link til Bitraf medlemsside';

  send_email($to, $from, $subject, $text_body);

  $text = 'E-post sendt';

}

function send_email($to, $from, $subject, $text)
{
  $headers = "From: " . $from . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
  $encoded_subject = '=?UTF-8?B?'.base64_encode($subject).'?=';

  return mail($to, $encoded_subject, $text, $headers, "-f $from");
}

function html($s)
{
  return htmlentities($s, ENT_QUOTES, 'utf-8');
}

$email = "";
if (isset($_POST['email']))
  $email = $_POST['email'];

?>

<html>
  <head>
    <title>Finn min medlemsinformasjon!</title>
    <style>
      @import '//bitraf.no/style/main.css';
    </style>
  <body>
    <div id='globalWrapper'>
    <h1><img src="small-logo.png" alt="" style='margin-right: 20px'>
    Finn min medlemsinformasjon</h1>

    <h2>Informasjon</h2>

    <p>For å endre medlemskapstype eller oppdatere betalingsinformasjon må du
    gå inn på din medlemsside.  Denne nås ved å bruke en hemmelig URL som er
    knyttet til din konto.  Dersom du ikke har denne URL-en, kan du bruke skjemaet
    under for å få den tilsendt.</p>
    <p>Hvis du ikke allerede er medlem, men ønsker å bli det, må du bruke <a href='/join'>innmeldingsskjemaet.</a></p>

    <h2>Bestill link</h2>

    <form method='post' action='<?=html($_SERVER['REQUEST_URI'])?>'>

    <p>Min registrerte e-postadresse:<br>
    <input name='email' type='text' size='20' pattern='.+@.+\..+' value='<?=html($email)?>'>

    <br/><br/>

    <input type='submit' value='Send meg min medlemsside'>

    </form>

    <p><?=$text?><p>

  </body>
</html>
