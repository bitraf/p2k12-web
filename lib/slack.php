<?
function request_slack_invite($email)
{
	$url = 'https://bitraf.slack.com/api/users.admin.invite';
	$data = array('email' => $email, 'token' => $GLOBALS['slack_token'], 'set_active' => 'true');

	// use key 'http' even if you send the request to https://...
	$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data)
				)
			);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) { /* Handle error */ }

	return $result;
}
