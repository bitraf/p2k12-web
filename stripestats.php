<?php
require_once('smarty3/Smarty.class.php');
require_once('lib/respond.php');
require_once('lib/p2k12.php');
require_once('lib/stripe-php/lib/Stripe.php');

setlocale (LC_ALL, 'en_US.UTF-8');
date_default_timezone_set ('Europe/Oslo');

require_once('db-connect-string.php');
if (false === pg_connect($db_connect_string))
    respond('500', 'PostgreSQL connect failed');

Stripe::setApiKey($stripe_apikey);

$sum_members = 0;
$sum_amount = 0;
$fees = 0;
$last_object = NULL;

$subscription_sum = array();

do {
  $customers = Stripe_Customer::all(array('limit' => '100', 'starting_after' => $last_object));

  foreach($customers->data as $customer)
  {
    foreach($customer->subscriptions->data as $subscription)
    {
      $amount = $subscription->plan->amount / 100;
      if (!isset($subscription_sum[$amount]))
        $subscription_sum[$amount] = 0;

      ++$subscription_sum[$amount];

      $sum_amount += $amount;
      $fees += $amount * 0.024 + 2;
      $sum_members++;
    }

    $last_object = $customer;
  }
} while ($customers->has_more);

?>

<html>
<body>

Oversikt medlemskap:

<ul>
<? foreach ($subscription_sum as $amount => $sum): ?>
<li> <?=$sum?> medlemmer som betaler <?=$amount?> kr (totalt <?=($sum*$amount)?> kr)
<? endforeach ?>
</ul>

<p>Totalt har vi <?=$sum_members?> betalende medlemmer og <?=$sum_amount?> kr i inntekter / mnd.  Se også <a href='/charts/stripe-stats.png'>graf.</a></p>

<p>Av dette går <?=$fees?> kr til Stripe i gebyr.</p>
</body>
</html>

