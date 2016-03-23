<?
require_once('smarty3/Smarty.class.php');

require_once('lib/respond.php');
require_once('lib/p2k12.php');
require_once('lib/stripe-php/lib/Stripe.php');

setlocale (LC_ALL, 'nb_NO.UTF-8');
date_default_timezone_set ('Europe/Oslo');

require_once('db-connect-string.php');
if (false === pg_connect($db_connect_string))
  respond('500', 'PostgreSQL connect failed');

Stripe::setApiKey($stripe_apikey);

$membership_infos_res = pg_query("SELECT name, price FROM membership_infos WHERE recurrence = '1 month'::INTERVAL ORDER BY price = 500 DESC, price DESC");
$membership_types = array();

while ($row = pg_fetch_assoc($membership_infos_res))
  $membership_types[$row['name']] = $row['price'];

$errors = array();

function html($s)
{
    return htmlentities($s, ENT_QUOTES, 'utf-8');
}

function send_new_member_alert($full_name, $email, $price, $username)
{
  $from = 'kasserer@bitraf.no';
  $subject = 'Nytt Bitraf-medlem';
  $to = $GLOBALS['new_member_alert_email'];

  $smarty = new Smarty;
  $smarty->assign('full_name', $full_name);
  $smarty->assign('price', $price);
  $smarty->assign('email', $email);
  $smarty->assign('username', $username);

  $html_body = $smarty->fetch('templates/new_member_alert.tpl'); 

  return send_html_email($to, $from, $subject, $html_body);
}

if (isset($_POST['user-name']))
{
    $user_name = strtolower($_POST['user-name']);

    if (!preg_match('/^[a-z]+$/', $user_name))
	$errors[] = 'Invalid user name.  The user name can only contain letters';
    else if (pg_num_rows(pg_query_params("SELECT * FROM accounts WHERE LOWER(name) = LOWER($1)", array($user_name))))
	$errors[] = 'The user name "' . $user_name . '" is already taken';

    if (strlen($_POST['full-name']) < 3)
	$errors[] = 'Real name unrealistically short';

    if(!preg_match('/.+@.+\..+/', $_POST['email']))
	$errors[] = 'Invalid e-mail address';
    else if (pg_num_rows(pg_query_params("SELECT * FROM active_members WHERE LOWER(email) = LOWER($1)", array($_POST['email']))))
	$errors[] = 'A user with the e-mail address "' . $_POST['email'] . '" is already registered';

    // Disable blacklist
    //  if (!sizeof($errors))
    //  {
    //    $dnsbl_prefix = implode('.', array_reverse(explode('.', $_SERVER['REMOTE_ADDR'])));
    //
    //    if(checkdnsrr("$dnsbl_prefix.dnsbl.ahbl.org", 'A'))
    //      $errors[] = 'Your IP address is blocked due to abusive behaviour.  See http://www.ahbl.org/ for details';
    //  }

    if (!sizeof($errors))
    {
	$status = pg_query('BEGIN');
        $price = $_POST['price'];

        if ($price > 0 && !isset($_POST['stripeToken']))
        {
          $errors[] = 'Stripe must be used to register account';
          $status = false;
        }

	if ($status !== false)
	    $status = pg_query_params("INSERT INTO accounts (name, type) VALUES ($1, 'user')", array($user_name));

	if ($status !== false)
	    $status = pg_query("SELECT CURRVAL('accounts_id_seq'::REGCLASS)");

	if ($status !== false)
	    $account = pg_fetch_result($status, 0, 0);

	// Create a Stripe customer if member will pay by creditcard

	$use_stripe = false;
	if ($status !== false && isset($_POST['stripeToken']) && $price > 0)
	{
	    $plan = "medlem" . $_POST['price'];

	    try {
		$customer = Stripe_Customer::create(array(
			    "card" => $_POST['stripeToken'],
			    "plan" => $plan,
			    "email" => $_POST['email'])
			);
		$status = pg_query_params("INSERT INTO stripe_customer (account, id) VALUES ($1, $2)", array($account, $customer->id));

	    } catch (Stripe_Error $e) {
		$errors[] = "There was a problem with your payment. Contact kasserer@bitraf.no if this continues.";
		$status = false;
                $errors[] = $e->getMessage();
                syslog(LOG_ERR, $e->getMessage());
	    }

	    $use_stripe = true;
	}

	if ($status !== false)
	{
	    $status = pg_query_params("INSERT INTO members (full_name, email, price, account) VALUES ($1, $2, $3, $4)", array($_POST['full-name'], $_POST['email'], $price, $account));
	}

	if ($status !== false)
	    pg_query('COMMIT');

	if ($status !== false)
	{
            if ($price > 0)
              send_new_member_alert($_POST['full-name'], $_POST['email'], $_POST['price'], $user_name);

            if ($use_stripe) {
              respond_303("https://{$_SERVER['HTTP_HOST']}/mystripe.php?id={$account}&signature=" . hash_for_account($account));
            } else {
              echo "OK, account id = {$account}";
              exit;
            }
	}
	else
	{
	    $errors[] = 'A database error occured while storing your user information: ' . pg_last_error();

	    if ($use_stripe && isset($customer))
		$customer->delete();

	    pg_query('ROLLBACK');
	}
    }
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $errors[] = "HTTP POST method used, but not user-name given.  Don't know what to do";
}

if (isset($_GET['type']))
$type = $_GET['type'];
else
$type = 'aktiv';

$user_name = '';
if (isset($_GET['user-name']))
  $user_name = $_GET['user-name'];

$full_name = '';
if (isset($_GET['full-name']))
  $full_name = $_GET['full-name'];

$email = '';
if (isset($_GET['email']))
  $email = $_GET['email'];

$organization = '';
if (isset($_GET['organization']))
  $organization = $_GET['organization'];

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Bitraf: p2k12: Join!</title>
    <style>
      @import '//bitraf.no/style/main.css';
      #regular-submit { display: none; }
    </style>
    <script>
      function price_changed() {
        var priceWidget = document.getElementById('price');
        var price = priceWidget.options[priceWidget.selectedIndex].value;
        var stripeSubmit = document.getElementsByClassName('stripe-button-el')[0];
        var regularSubmit = document.getElementById('regular-submit');
        if (price > 0) {
          stripeSubmit.style.display = 'inline-block';
          regularSubmit.style.display = 'none';
        } else {
          stripeSubmit.style.display = 'none';
          regularSubmit.style.display = 'inline-block';
        }
      }

      function submit() {
        // Strip hijacks the form submit, so we need to un-hijack it.
        var form = document.getElementById('form');
        form.submit();
      }
    </script>
  <body>
    <div id='globalWrapper'>
    <h1><img src="/small-logo.png" alt='' style='margin-right: 20px'>Bli medlem i Bitraf</h1>
    <h2>Innmelding</h2>
    <? if ($errors): ?>
      <h2>Errors</h2>
      <ul>
        <? foreach ($errors as $error): ?>
        <li><?=html($error)?>.
        <? endforeach ?>
      </ul>
      <h2>No skjedde galt og brukeren ble ikke opprettet. Trykk <a href="<?=html($_SERVER['REQUEST_URI'])?>">her</a> for å forsøke igjen.</h2>
      <? die(); ?>
    <? endif ?>
    <form method='post' action='<?=html($_SERVER['REQUEST_URI'])?>' id=form>
    <p>Brukernavn (kun a-z):<br>
    <input name='user-name' type='text' size='20' pattern='[a-z]+' value='<?=html($user_name)?>'>

    <p>Fullt navn:<br>
    <input name='full-name' type='text' size='20' pattern='...+' value='<?=html($full_name)?>'>

<!--    <p>Organization number (if applicable):<br>
    <input name='organization' type='text' size='20' pattern='[0-9 ]*' value='<?=html($organization)?>'> -->

    <p>E-post:<br>
    <input name='email' type='text' size='20' pattern='.+@.+\..+' value='<?=html($email)?>'>
    
    <p>Medlemstype:<br>

    <select id=price name=price onchange=price_changed()>
    <? foreach ($membership_types as $membership_type => $price): ?>
    <? if ($price > 0): ?> <option value="<?=$price?>"><?=html($membership_type)?> (<?=$price?> kr / mnd) </option> <? endif ?>
    <? endforeach ?>
      <option value=0>Kontorplass (etter avtale)</option>
    </select>

    <br/><br/>

  <script
    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
    data-key='<?=$stripe_pubkey?>'
    data-currency="NOK"
    data-name="Signup"
    data-description="Medlemskap"
    data-label="Videre til betaling"
    data-panel-label="Bli medlem"
    data-image="/small-logo.png">
  </script>

    <input type=button value=Registrer id=regular-submit onclick=submit()>
    </form>

  <h2>Medlemskap</h2>
  <p>Vi setter veldig stor pris på alle bidrag. Bitraf er en frivillig organisasjon som styres, brukes og finansieres av sine medlemmer.<p>

  <p>I hovedsak har vi to type medlemskap. Vanlig medlemskap er for deg som er innom lokalet en gang i blant. Støttemedlemskap er for deg som liker arbeidet vårt og som kan avse en slant for at vi skal fortsette å tilby det vi gjør.</p>

  <p>Du kan når som helst avslutte medlemskapet ditt og det påløper ingen ekstra kostnader fra oss. Bitraf lagrer ikke betalingsdetaljene dine. Dette blir håndtert av Stripe.com.</p>
<!--
    <h2>Membership Dues</h2>

    <table id='dues'>
    <? foreach ($membership_types as $membership_type => $price): ?>
      <tr>
        <th style='text-align:left; vertical-align: middle; padding: 5px'><?=$membership_type?>
        <td style='text-align:left; vertical-align: middle; padding: 5px'><?=$price?> per month
    <? endforeach ?>
    </table>
-->
