#!/usr/bin/env php
<?
die("We are using Fiken!!!\n");
require_once('Mail.php');
require_once('Mail/mime.php');
setlocale (LC_ALL, 'nb_NO.UTF-8');
date_default_timezone_set ('Europe/Oslo');

$today = ltrim(strftime('%e. %B %Y'));

$secret = file_get_contents('/var/lib/p2k12/secret');

if (false === pg_connect("dbname=p2k12 options='-c search_path=memberships'"))
{
  echo "Drats!\n";

  exit;
}

$smtp = Mail::factory('smtp', array ('host' => 'localhost', 'auth' => false));

/* Add new members */
pg_query(<<<SQL
INSERT INTO membership_periods (account_id, start_date, end_date, price)
  SELECT account, date::DATE, date::DATE + INTERVAL '1 month', price
    FROM public.active_members
    WHERE account NOT IN (SELECT DISTINCT account_id FROM membership_periods) AND price > 0;
SQL
  );

/* Add new "membership periods" for current members */
pg_query(<<<SQL
INSERT INTO membership_periods (account_id, start_date, end_date, price)
  WITH ended_memberships AS (SELECT MAX(end_date) AS start_date, account_id
                              FROM membership_periods WHERE account_id IN (SELECT account FROM public.active_members WHERE price > 0)
                              GROUP BY account_id
                              HAVING MAX(end_date) < NOW())
    SELECT account_id,
           start_date,
           DATE_TRUNC('month', start_date + INTERVAL '1 month'),
           price * EXTRACT(epoch FROM DATE_TRUNC('month', start_date + INTERVAL '1 month') - start_date) / EXTRACT(epoch FROM (start_date + INTERVAL '1 month') - start_date)
    FROM public.active_members am
    INNER JOIN ended_memberships e ON e.account_id = am.account;
SQL
  );

$members_res = pg_query(<<<SQL
SELECT DISTINCT account_id, full_name, email, organization
  FROM membership_periods mp
  INNER JOIN public.active_members am ON am.account = mp.account_id
  NATURAL LEFT JOIN membership_period_invoices npi
  WHERE start_date > '2012-10-21' AND npi.invoice_id IS NULL
  ORDER BY full_name
SQL
  );

$today = ltrim(strftime('%e. %B %Y'));

$pay_by = strftime('%Y-%m-%d', time() + 7 * 86400);

while ($member = pg_fetch_assoc($members_res))
{
  $signature = substr(hash_hmac('sha256', $member['account_id'], $secret), 0, 8);

  pg_query('BEGIN');

  echo "*** Processing {$member['account_id']}\n";

  $products_res = pg_query_params(<<<SQL
SELECT mp.membership_period_id, start_date, (end_date - INTERVAL '1 day')::DATE AS end_date, mp.price
  FROM membership_periods mp
  NATURAL LEFT JOIN membership_period_invoices npi
  INNER JOIN public.active_members am ON am.account = mp.account_id
  WHERE npi.invoice_id IS NULL
        AND mp.start_date > '2012-10-21'
        AND mp.account_id = $1
SQL
  , array($member['account_id']));

  if (false === ($res = pg_query('SELECT COALESCE(MAX(invoice_id), 0) + 1 FROM invoices;')))
  {
    echo "*** Failed to create new invoice ID\n";

    exit;
  }

  $invoice = pg_fetch_result($res, 0, 0);

  pg_query_params("INSERT INTO invoices (invoice_id, text) VALUES ($1, 'Medlemskap, ' || $2)", array($invoice, $member['full_name']));

  $products = array();

  while ($product = pg_fetch_assoc($products_res))
  {
    if (false === pg_query_params("INSERT INTO membership_period_invoices (membership_period_id, invoice_id) VALUES ($1, $2)", array($product['membership_period_id'], $invoice)))
    {
      echo "Aborting due to query error\n";
      exit;
    }

    $products[] = $product;
  }

  $ledger_res = pg_query_params(<<<SQL
  SELECT paid_date AS date, amount AS change, 'Innbetaling' AS type, NULL AS invoice
      FROM payments
      WHERE account_id = $1
  UNION
    SELECT mp.start_date, -mp.price, 'Medlemskap', npi.invoice_id
      FROM membership_periods mp
      NATURAL LEFT JOIN membership_period_invoices npi
      WHERE account_id = $1
    ORDER BY date
SQL
  , array($member['account_id']));

  $ledger = array();

  $balance = 0;
  while ($row = pg_fetch_assoc($ledger_res))
  {
    $balance += $row['change'];
    $row['balance'] = $balance;
    $ledger[] = $row;
  }
  
  if ($balance >= 0)
  {
    echo "*** {$member['full_name']} is ajour: {$balance} kr\n";

    pg_query('ROLLBACK');

    continue;
  }

  $payment_count = 0;
  $membership_count = 0;

  for ($i = sizeof($ledger); $i-- > 0 && (($ledger[$i]['change'] - $ledger[$i]['balance']) || ($payment_count < 1 && $membership_count < 2)); )
  {
    if ($ledger[$i]['type'] == 'Medlemskap')
      ++$membership_count;
    if ($ledger[$i]['type'] == 'Innbetaling')
      ++$payment_count;
  }

  echo "*** {$member['full_name']} is behind: {$balance} kr\n";

  $ledger = array_slice($ledger, $i);
  $first = reset($ledger);
  $incoming_balance = $first['balance'] - $first['change'];

  $subject = "[Bitraf] Faktura for medlemskap #{$invoice}";

  ob_start();

  printf ("Dato: %s\n", $today);
  printf ("Medlemskap i Bitraf\n");
  printf ("\n");

  printf ("Fakturanummer: %s\n", $invoice);
  printf ("\n");

  printf ("Varer:\n");
  printf ("\n");

  foreach ($products as $product)
    printf ("  %s kr  Medlemskap fra %s til %s\n", number_format($product['price'], 2, ',', '.'), $product['start_date'], $product['end_date']);
  printf ("\n");

  printf ("Betalingsfrist: %s\n", $pay_by);
  printf ("\n");

  printf ("Kunde:\n");
  printf ("\n");
  printf ("  %s\n", $member['full_name']);
  if ($member['organization'])
    printf ("  Organisasjonsnummer: %s\n", $member['organization']);
  printf ("\n");

  printf ("Siste bevegelser:\n");
  printf ("\n");

  if ($incoming_balance)
    printf ("  %s  %-11s  %9s kr\n", $first['date'], 'Balanse', number_format($first['balance'] - $first['change'], 2, ',', '.'));

  foreach ($ledger as $row)
    printf ("  %s  %-11s  %9s kr\n", $row['date'], $row['type'], number_format($row['change'], 2, ',', '.'));

  printf ("  -------------------------------------\n");
  printf ("  Sum å betale             %9s kr\n", number_format(-$balance, 2, ',', '.'));
  printf ("  =====================================\n");
  printf ("\n");
  printf ("Mottaker:\n");
  printf ("\n");
  printf ("  Bitraf\n");
  printf ("  Youngs gate 6\n");
  printf ("  0181 OSLO\n");
  printf ("  Organisasjonsnummer: 898 124 452\n");
  printf ("\n");
  printf ("Kontonummer: 1503 273 5581\n");
  printf ("\n");
  printf ("Takk for at du betaler medlemsavgift!  Bitraf er avhengig av medlemsavgift for \n");
  printf ("å betale husleie og å kjøpe nødvendig utstyr.\n");
  printf ("\n");
  printf ("For å endre eller avslutte ditt medlemskap, gå til:\n");
  printf ("\n");
  printf ("  https://p2k12.bitraf.no/my/%s/%s/\n", $member['account_id'], $signature);

  $body = ob_get_contents();
  ob_end_clean();

  pg_query_params('INSERT INTO documents (text) VALUES ($1)', array($body));
  $document_id_res = pg_query('SELECT LASTVAL()');
  $document_id = pg_fetch_result($document_id_res, 0, 0);

  pg_query_params('INSERT INTO invoice_documents (invoice_id, document_id) VALUES ($1, $2)', array($invoice, $document_id));

  $html_body = htmlentities($body, ENT_QUOTES, 'utf-8');
  $html_body = "<html><body><pre>$html_body</pre></body></html>";

  $headers = array ('Subject' => $subject);

  $headers['From'] = "Bitraf <billing@bitraf.no>";
  $headers['To'] = "{$member['full_name']} <{$member['email']}>";
  $headers['Content-Transfer-Encoding'] = '8bit';
  $headers['Content-Type'] = 'text/plain; charset="UTF-8"';

  $message = new Mail_mime("\n");
  $message->setTXTBody($body);
  $message->setHTMLBody($html_body);
  $body = $message->get(array('text_charset' => 'utf-8', 'head_charset' => 'utf-8'));

  $headers = $message->headers($headers);

  $test = false;

  if ($test)
  {
    $mail_result = $smtp->send('morten.hustveit@gmail.com', $headers, $body);

    if ($mail_result !== TRUE)
    {
      printf ("Failed to send e-mail: %s\n", $mail_result->getMessage());

      pg_query('ROLLBACK');

      exit;
    }

    pg_query('ROLLBACK');

    break;
  }
  else
  {
    $mail_result = $smtp->send($member['email'], $headers, $body);

    if ($mail_result !== TRUE)
    {
      printf ("Failed to send e-mail: %s\n", $mail_result->getMessage());
      pg_query('ROLLBACK');
      exit;
    }

    pg_query('COMMIT');
  }
}
