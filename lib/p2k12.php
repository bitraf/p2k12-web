<?
function hash_for_account($account)
{
  $secret = file_get_contents($GLOBALS['secret_file']);
  return substr(hash_hmac('sha256', $account, $secret), 0, 8);
}

function send_html_email($to, $from, $subject, $html_body)
{
  $headers = "From: " . $from . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=utf-8\r\n";

  $encoded_subject = '=?UTF-8?B?'.base64_encode($subject).'?=';

  return mail($to, $encoded_subject, $html_body, $headers, "-f $from");
}
