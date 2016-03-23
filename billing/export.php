<?
$admins = array('morten.hustveit@gmail.com');
$errors = array();

header('Content-Type: text/plain');
require_once('lib/html.php');

if (false === pg_connect('dbname=p2k12 user=p2k12'))
{
  echo "PostgreSQL connection error\n";

  exit;
}

$members_res = pg_query(<<<SQL
SELECT am.date, am.full_name, am.email, am.price, am.account, COALESCE(SUM(p.amount), 0) AS sum_paid
  FROM active_members am
  LEFT JOIN payments p ON p.account = am.account
  GROUP BY am.date, am.full_name, am.email, am.price, am.account
  HAVING (am.price > 0) OR COALESCE(SUM(p.amount), 0) > 0
  ORDER BY am.price > 0 DESC, sum_paid DESC
SQL
  );

if ($members_res === false)
  $errors[] = 'PostgreSQL error: ' . html(pg_last_error());

$active = true;

while($member = pg_fetch_assoc($members_res))
{
    $sum_expected_res = pg_query_params(<<<SQL
SELECT SUM(price)
  FROM GENERATE_SERIES((SELECT MIN(date) FROM members WHERE account = $1 AND price > 0), NOW(), INTERVAL '1 month') gs
  INNER JOIN members m
    ON m.date = (SELECT MAX(date) FROM members WHERE date <= gs AND account = m.account)
    AND m.account = $1
SQL
      , array($member['account']));

    $sum_expected = pg_fetch_result($sum_expected_res, 0, 0);
    $balance = $member['sum_paid'] - $sum_expected;

    /* Do not try this at home */
    $ledger_res = pg_query_params(<<<SQL
SELECT a.date, -a.price AS change, SUM(-a.price) OVER (ORDER BY date), a.type
  FROM (SELECT gs::DATE AS date, price, 'Membership dues' AS type
    FROM GENERATE_SERIES((SELECT MIN(date) FROM members WHERE account = $1 AND price > 0), NOW(), INTERVAL '1 month') gs
    INNER JOIN members m
      ON m.date = (SELECT MAX(date) FROM members WHERE date <= gs AND account = m.account)
      AND m.account = $1 AND m.price > 0
  UNION
  SELECT paid_date, -amount, 'Payment'
    FROM payments
    WHERE account = $1) a
SQL
    , array($member['account']));

    while ($line = pg_fetch_assoc($ledger_res))
    {
      if($line['type'] == 'Membership dues')
      {
        printf ("%s\t%s\t%s\n", $member['account'], $line['date'], -$line['change']);
      }
    }
}
