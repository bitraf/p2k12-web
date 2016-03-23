<?php

function get_macaddr()
{
  include 'secret.php';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://77.88.71.253:8080/DEV_device.htm');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  $output = curl_exec($ch);
  $info = curl_getinfo($ch);
  curl_close($ch);

  //echo $output;
  preg_match_all("/([a-fA-F0-9]{2}[:|\-]?){6}/", $output, $foo); 
  //preg_match_all("/^(.*:.*)$/mi", $foo, $foo); 

  return $foo;
}

function IsValid($mac) { return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac) == 1); } 


?>
