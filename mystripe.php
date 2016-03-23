<?php
require_once('lib/respond.php');
require_once('lib/p2k12.php');
require_once('lib/stripe-php/lib/Stripe.php');

setlocale (LC_ALL, 'nb_NO.UTF-8');
date_default_timezone_set ('Europe/Oslo');

require_once('db-connect-string.php');
if (false === pg_connect($db_connect_string))
    respond('500', 'PostgreSQL connect failed');

Stripe::setApiKey($stripe_apikey);

$membership_infos_res = pg_query("SELECT name, price FROM membership_infos WHERE recurrence = '1 month'::INTERVAL ORDER BY name = 'aktiv' DESC, price DESC");
$membership_types = array();

while ($row = pg_fetch_assoc($membership_infos_res))
    $membership_types[$row['name']] = $row['price'];

if (!isset($_GET['id']) || !isset($_GET['signature']))
  respond('404', 'Not found');

$account = $_GET['id'];
$provided_signature = $_GET['signature'];
$correct_signature = hash_for_account($account);

if ($provided_signature != $correct_signature)
  respond('404', 'Not found');

// Get membership details
if (false === ($member_res = pg_query_params("SELECT price, recurrence, full_name, email, organization, flag, name AS username FROM active_members, accounts WHERE account = $1 AND active_members.account = accounts.id", array($account))))
  respond('500', 'PostgreSQL query error');

if (!pg_num_rows($member_res))
  respond('404', 'Member does not exist');

$member = pg_fetch_assoc($member_res);



// Update credit card
if (isset($_POST['stripeToken'])) {

  // Get stripe customer and set new card
  if (false === ($member_res = pg_query_params("SELECT account, id FROM stripe_customer WHERE  account = $1", array($account))))
    respond('500', 'PostgreSQL query error');


  if (pg_num_rows($member_res))
  {
    $stripe_customer = pg_fetch_assoc($member_res);
    try {
      $customer = Stripe_Customer::retrieve($stripe_customer['id']);

      if (count($customer->cards->data) > 0)
      {
        // Delete existing card
        $cardid = $customer->cards->data[0]->id;
        $customer->cards->retrieve($cardid)->delete();
      }

      $customer->cards->create(array("card" => $_POST['stripeToken']));
    } catch (Stripe_Error $e)
    {
      respond('404', 'Error updating stripe customer: ' . $e->getMessage());
    }

  }
  else // Create new Stripe customer
  {
    $status = pg_query("BEGIN"); 

    $plan = "medlem" . $member['price'];
    try {
      $customer = Stripe_Customer::create(array(
      "card" => $_POST['stripeToken'],
      "plan" => $plan,
      "email" => $member['email']));

      $status = pg_query_params("INSERT INTO stripe_customer (account, id) VALUES ($1, $2)", array($account, $customer->id));

    } catch (Stripe_Error $e) {
      respond('500', 'Error creating stripe customer: ' . $e->getMessage());
    }

    if ($status !== false)
    {
      $status = pg_query_params("INSERT INTO members (full_name, email, price, account) VALUES ($1, $2, $3, $4)", array($member['full_name'], $member['email'], $member['price'], $account));
    }
    if ($status !== false)
      pg_query("COMMIT");
    else {
      pg_query("ROLLBACK");
      if (isset($customer))
          $customer->delete();
      syslog(LOG_ERR, pg_last_error());
      respond('500', 'PostgreSQL query error while creating Stripe customer');
    }

  }
}

// Endre medlemstype
if (isset($_POST['membership_price']))
{
    $price = $_POST['membership_price'];
    $plan = "medlem" . $price;

    // Get stripe customer
    if (false === ($member_res = pg_query_params("SELECT account, id FROM stripe_customer WHERE  account = $1", array($account))))
      respond('500', 'PostgreSQL query error');

    // Oppdater stripe info hvis det brukes.
    if (pg_num_rows($member_res))
    {
      $stripe_customer = pg_fetch_assoc($member_res);

      try {
        $customer = Stripe_Customer::retrieve($stripe_customer['id']);

        // Create subscription if it does not exist
        if (count($customer->subscriptions->data) == 0 && $price != 0) {
          $customer->subscriptions->create(array('plan' => $plan));
        }
        else
        {
          $subscription = $customer->subscriptions->data[0];

          if ($price == 0)
          {
            // Delete subscription if price==0
            $subscription->cancel(array('at_period_end' => true));
          }
          else {
            // Update subscription
            $subscription->plan = $plan;
            $subscription->save();
          }
        } 
      
      } catch (Stripe_Error $e) {
        respond('500', 'Error communicating with stripe: ' . $e->getMessage());
      }
    } 

    // Update p2k12
    if (false === pg_query_params("INSERT INTO members (full_name, email, price, account) VALUES ($1, $2, $3, $4)", array($member['full_name'], $member['email'], $price, $account)))
      respond('500', 'PostgreSQL query error');


    respond_303($_SERVER['REQUEST_URI']);
}

// Set some defaults
$invoices = array();
$card_expire = '';
$card_num = 'N/A';

// Get Stripe details
if (false === ($member_res = pg_query_params("SELECT account, id FROM stripe_customer WHERE  account = $1", array($account))))
  respond('500', 'PostgreSQL query error');

if (pg_num_rows($member_res))
{

  $stripe_customer = pg_fetch_assoc($member_res);

  $customer = Stripe_Customer::retrieve($stripe_customer['id']);

  if (count($customer->cards->data) > 0)
  {
    $card_num = '**** **** **** ' . $customer->cards->data[0]->last4;
    $card_expire = $customer->cards->data[0]->exp_month . '/' . $customer->cards->data[0]->exp_year;
  }
  else
  {
    $card_num = 'No card set';
    $card_expire = '00/00';
  }

  if (false === ($res = pg_query_params("SELECT * FROM stripe_payment WHERE  account = $1 ORDER BY paid_date DESC", array($account))))
    respond('500', 'PostgreSQL query error');
  
  $invoices = array();

  while ($row = pg_fetch_assoc($res))
    $invoices[] = $row;
}

function html($s)
{
    return htmlentities($s, ENT_QUOTES, 'utf-8');
}

?>

<html>
<head>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<script src="https://checkout.stripe.com/v2/checkout.js"></script>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

</style>
</head>

<body>
<div class="container">
<div class="page-header">
<h1>
<img src="small-logo.png" style="margin-right: 20px">Mitt Bitraf-medlemskap</h1>
</div>

<div class="row">
  <div class="col-md-6">
  <div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">Medlemskap</h3>
  </div>
  <div class="panel-body">
	<table class="table">
	<tr>
		<th>Navn:</th>
		<td><?=htmlentities($member['full_name'], ENT_QUOTES, 'utf-8')?></td>
	</tr>
	<tr>
		<th>Brukernavn:</th>
		<td><?=htmlentities($member['username'], ENT_QUOTES, 'utf-8')?></td>
	</tr>
	<tr>
		<th>Organisasjon:</th>
		<td><?=htmlentities($member['organization'], ENT_QUOTES, 'utf-8')?></td>
	</tr>
	<tr>
		<th>E-post:</th>
		<td><?=htmlentities($member['email'], ENT_QUOTES, 'utf-8')?></td>
	</tr>
	<tr>
		<th>Medlemspris:</th>
		<td><?=htmlentities($member['price'], ENT_QUOTES, 'utf-8')?></td>
	</tr>
	<tr>
		<th>Betalingsm&aring;te:</th>
		<td><? if (isset($customer)) print 'Stripe (kredittkort)'; else print 'Bankoverf&oslash;ring';?> </td>
	</tr>
	</table>

  </div>
  </div>
  </div>

  <div class="col-md-6">
  <div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">Betaling</h3>
  </div>
  <div class="panel-body">
	<table class="table">
	<tr>
		<th>Kredittkort:</th>
		<td> <?=$card_num?> </td>
	</tr>
	<tr>
		<th>Utl&oslash;psdato:</th>
		<td> <?=$card_expire?> </td>
	</tr>
	</table>

    
    <div class="row">
      <div class="col-md-6">
    <form method='post' action='<?=html($_SERVER['REQUEST_URI'])?>'>
      <button type="button" class="btn btn-success" id="updateStripe">Oppdater kredittkort</a>
      <script>
        $('#updateStripe').click(function(){
        var token = function(res){
          var $input = $('<input type=hidden name=stripeToken />').val(res.id);
          $('form').append($input).submit();
        };
        StripeCheckout.open({
          key: '<?=$stripe_pubkey?>',
          panelLabel: 'Endre kredittkort',
          name: 'Bitraf medlemskap',
          token: token
          });
        return false;
        });
      </script>
    </form>
      </div>


      <div class="col-md-6">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#endreMedlemskap">Endre Medlemskap</a>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="endreMedlemskap" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
      <form method='post' action='<?=html($_SERVER['REQUEST_URI'])?>'>
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="myModalLabel">Endre medlemsskapstype</h4>
          </div>
          <div class="modal-body">

            <p>Medlemskap</p>
            <select class="form-control" name="membership_price">
              <? foreach ($membership_types as $membership_type => $price): ?>
                <option value="<?=$price?>" <? if ($price===$member['price']) print 'selected'?>><?=html($membership_type)?> (<?=$price?> kr / mnd) </option>
              <? endforeach ?>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Lukk</button>
            <button type="submit" class="btn btn-primary">Lagre</button>
          </div>
        </form>
        </div>
      </div>
    </div>

  <p>Kredittkortdetaljer blir aldri overf&oslash;rt eller lagret av Bitraf og h&aring;ndteres kun av <a href='http://stripe.com'>stripe.com</a>.</p>
  </div>
  </div>
  </div>
</div>


<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">Medlemsperioder</h3>
  </div>
  <div class="panel-body" style="width: 50%;">
	<table class="table">
	<tr>
		<th>Fra</th>
		<th>Til</th>
		<th>Bel&oslash;p</th>
		<th>Betalt</th>
	</tr>

  <?
    foreach($invoices as $inv)
    {
      print "<tr>";

      print "<td>" . $inv['start_date'] . "</td>";
      print "<td>" . $inv['end_date'] . "</td>";
      print "<td>" . $inv['price'] . "</td>";
      print "<td>" . $inv['paid_date'] . "</td>";

      print "</tr>";
    }
  ?>
  </table>

  </div>
</div>



</div>


</body>

</html>
