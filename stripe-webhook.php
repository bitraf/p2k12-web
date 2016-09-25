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

// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

if ($event_json->type === "invoice.payment_succeeded")
{
  payment_succeeded($event_json->data->object->id);
  respond('200', 'OK');
}
else if ($event_json->type === "invoice.payment_failed")
{
  payment_failed($event_json->data->object->id);
  respond('200', 'OK');
}
else if ($event_json->type === "customer.subscription.deleted")
{
  subscription_deleted($event_json->data->object);
  respond('200', 'OK');
}
else
{
  respond('400', 'Unhandled webhook');
}

function payment_succeeded($invoiceId)
{
  try {
    $invoice = Stripe_Invoice::retrieve($invoiceId);    
    if (isset($invoice->charge))
      $charge = Stripe_Charge::retrieve($invoice->charge);    
  } catch (Stripe_Error $e)
  {
    respond('404', 'Invoice not found');
  }

  $accountId = get_account_from_stripe_customer($invoice->customer);

  $line = $invoice->lines->all()->data[0];

  $start_date = date("Y-m-d", $line->period->start);
  $end_date = date("Y-m-d", $line->period->end);
  $price = $line->amount / 100.0;

  // Invoices with 0 amount can be created by Stripe.
  if (isset($charge))
    $paid_date = date("Y-m-d", $charge->created);
  else
    $paid_date = NULL;

  // Insert invoice

  $status = pg_query_params("INSERT INTO stripe_payment(invoice_id, account, start_date, end_date, price, paid_date) VALUES ($1, $2, $3, $4, $5, $6)", array($invoiceId, $accountId, $start_date, $end_date, $price, $paid_date));

  if ($status === false)
    respond('500', 'PostgreSQL query error');

  // Send updates from Bitraf by email

  send_payment_receipt(get_member_from_stripe_customer($invoice->customer), $price, $start_date, $end_date);
}

function send_payment_receipt($member, $price, $start_date, $end_date)
{
  $from = 'kasserer@bitraf.no';
  $subject = 'Hva skjer på Bitraf';
  $to = $member['email'];

  $smarty = new Smarty;
  $smarty->assign('name', strtok($member['full_name'], ' '));
  $smarty->assign('amount', $price);
  $smarty->assign('start_date', $start_date);
  $smarty->assign('end_date', $end_date);
  $smarty->assign('accountId', $member['accountId']);
  $smarty->assign('hash', $member['hash']);

  $smarty->assign('events', generate_meetup_html());
  
  $html_body = $smarty->fetch('templates/stripe_receipt.tpl'); 

  return send_html_email($to, $from, $subject, $html_body);
}

function generate_meetup_html()
{
  $meetup_json = @file_get_contents('/var/lib/bitweb/meetup.bitraf');
  if (!$meetup_json) return;
  $output = @json_decode($meetup_json);
  if (!$output) return;

  $max = 5;
  $i = 0;

  $now_ms = time() * 1000;

  // We should do this by Smarty, but for now...
  $html_events = '';

  foreach ($output->results as $event)
  {
    if ($event->visibility != "public") continue;

    if ($i++ > $max && !(isset($event->featured) && $event->featured == true))
      continue;

    // Since we fetch the meetup list asynchronously, it can get out of date.
    if ($event->time + 7200000 < $now_ms) continue;

    $event_date = ucwords(strftime("%A %e. %B, %H:%M", $event->time / 1000));
    $event_description = preg_replace("/<img[^>]+\>/i", '', $event->description);
    $event_description = substr($event_description,0,strpos($event_description, "</p>")+4);

    $html_events .= "<tr><th>";

    if ((isset($event->featured) && $event->featured == true))
      $html_events .= "<p style='text-align: center; color: #ff6000'>Featured</p>";
    $html_events .= "<p>{$event_date}";

    $html_events .= "<td style='width: 700px;'>";
    $html_events .= "<p><a href='{$event->event_url}'>{$event->name}</a>";
    $html_events .= $event_description;

    $html_events .= "</td></th></tr>\n";
  }

  return $html_events;
}

function payment_failed($invoiceId)
{
  try {
    $invoice = Stripe_Invoice::retrieve($invoiceId);    
    $charge = Stripe_Charge::retrieve($invoice->charge);    
  } catch (Stripe_Error $e)
  {
    respond('404', 'Invoice not found');
  }

  $accountId = get_account_from_stripe_customer($invoice->customer);

  $line = $invoice->lines->all()->data[0];

  $start_date = date("Y-m-d", $line->period->start);
  $end_date = date("Y-m-d", $line->period->end);
  $price = $line->amount / 100.0;
  $created_date = date("Y-m-d", $charge->created);

  $member = get_member_from_stripe_customer($invoice->customer);

  $from = 'kasserer@bitraf.no';
  $subject = 'Mislykket betaling av medlemsavgift';
  $to = $member['email'];

  $smarty = new Smarty;
  $smarty->assign('name', strtok($member['full_name'], ' '));
  $smarty->assign('amount', $price);
  $smarty->assign('start_date', $start_date);
  $smarty->assign('end_date', $end_date);
  $smarty->assign('accountId', $member['accountId']);
  $smarty->assign('hash', $member['hash']);
  $smarty->assign('created_date', $created_date);

  $smarty->assign('events', generate_meetup_html());
  
  $html_body = $smarty->fetch('templates/payment_failed.tpl'); 

  return send_html_email($to, $from, $subject, $html_body);
}

function subscription_deleted($subscription)
{
  try {
    //$subscription = Stripe_Subscription::retrieve($subscriptionId);    
    $customer = Stripe_Customer::retrieve($subscription->customer);
  } catch (Stripe_Error $e)
  {
    respond('404', 'Customer not found');
  }

  $member = get_member_from_stripe_customer($customer->id);

  if ($member['price'] != 0)
  {
    // Subscription cancelled by Stripe. Update p2k12

    // Update p2k12
    $price = 0;
    if (false === pg_query_params("INSERT INTO members (full_name, email, price, account) VALUES ($1, $2, $3, $4)", array($member['full_name'], $member['email'], $price, $member['accountId'])))
      respond('500', 'PostgreSQL query error');
  }

  send_membership_cancelled_email($member); 
}

function send_membership_cancelled_email($member)
{
  $from = 'kasserer@bitraf.no';
  $subject = 'Avsluttet medlemskap i Bitraf';
  $to = $member['email'];

  $smarty = new Smarty;
  $smarty->assign('name', strtok($member['full_name'], ' '));
  $smarty->assign('accountId', $member['accountId']);
  $smarty->assign('hash', $member['hash']);

  $smarty->assign('events', generate_meetup_html());
  
  $html_body = $smarty->fetch('templates/membership_ended.tpl'); 

  return send_html_email($to, $from, $subject, $html_body);
}

function get_account_from_stripe_customer($customerId)
{
  if (false === ($res = pg_query_params("SELECT account, id FROM stripe_customer WHERE id = $1", array($customerId))))
    respond('500', 'PostgreSQL query error');

  if (!pg_num_rows($res))
    respond('404', 'Stripe customer does not exist');

  $account = pg_fetch_assoc($res);

  return $account['account'];
}

function get_member_from_stripe_customer($customerId)
{
  $accountId = get_account_from_stripe_customer($customerId);

  if (false === ($member_res = pg_query_params("SELECT price, recurrence, full_name, email, organization, flag FROM active_members WHERE account = $1", array($accountId))))
    respond('500', 'PostgreSQL query error');

  if (!pg_num_rows($member_res))
    respond('404', 'Member does not exist');

  $member = pg_fetch_assoc($member_res);

  $member['accountId'] = $accountId;
  $member['hash'] = hash_for_account($accountId);

  return $member;
}


?>
