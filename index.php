<?php
require_once('./daumOAuth.php');

session_start();//for save request token.

$consumer_key = 'c3f0b01b-1d88-44ef-ba43-03f01c9b6ba7';
$consumer_secret = 'd_BTMyP6mALSdkUnYMCUbIZfdP6XGr5dbu1.SgFbgHd4paGkqtmCzg00';
$callback_url = 'http://daumoauth.azki.org/';

$session_var_names = array('request_token', 'user_auth_url', 'access_token', 'blog_info');

$o_mode = @$_REQUEST['o_mode'];
switch ($o_mode) {
	default:
		//처음 접속시 or 2번후 콜백으로 들어와서 3번 진행.
		$o_token = @$_REQUEST['oauth_token'];
		$o_verifier = @$_REQUEST['oauth_verifier'];
		if (isset($o_token) && isset($o_verifier)) {
			//2번에서 인증 후에 콜백 URL로 다시 돌아오는 곳.
			$rTok = $_SESSION['request_token'];
			$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url, $o_token, $rTok['oauth_token_secret']);
			$aTok = @$to->getAccessToken($o_verifier);
			$_SESSION['access_token'] = $aTok;
		}
		else {
			//처음 접속시.
			foreach ($session_var_names as $var_name) {
				if (isset($_SESSION[$var_name])) {
					unset($_SESSION[$var_name]);
				}
			}
		}
		break;
	case 'request_token':
		//1번의 버튼을 눌러서 진행됨.
		$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url);
		$rTok = $to->getRequestToken();
		$url = $to->getAuthorizeURL($rTok);
		$_SESSION['request_token'] = $rTok;
		$_SESSION['user_auth_url'] = $url;
		break;
	case 'blog_info':
		//3번의 버튼을 눌러서 진행됨.
		$aTok = @$_SESSION['access_token'];
		$blog_id = $_REQUEST['blog_id'];
		if (isset($aTok)) {
			$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url, $aTok['oauth_token'], $aTok['oauth_token_secret']);
			$url = 'http://apis.daum.net/blog/info/blog.do';
			$args = array('blogName'=>$blog_id);
			$response = $to->OAuthRequest($url, $args, 'GET');
			$_SESSION['blog_info'] = $response;
		}
		break;
}

$dump_data = array();
foreach ($session_var_names as $var_name) {
	if (isset($_SESSION[$var_name])) {
		$dump_data[$var_name] = $_SESSION[$var_name];
	}
}
?><!DOCTYPE html> 
<html> 
<head>
<meta charset=utf-8><!-- simplified version; works on legacy browsers -->
<title>Daum OAuth Example.</title>
</head>
<body>
	<h1>Daum OAuth Example.</h1>
	<textarea rows="10" cols="120"><?php print_r($dump_data); ?></textarea>
	<hr/>
	<p>
		<h3>0. 정보 초기화 하기.</h3>
		<form>
			<input name="o_mode" value="reset" type="hidden" />
			<input type="submit" />
		</form>
	</p>
	<p>
		<h3>1. 인증되지 않은 Request 토큰 획득 및 서비스 프로바이더의 사용자 인증 URL 구하기.</h3>
		<form>
			<input name="o_mode" value="request_token" type="hidden" />
			<input type="submit" />
		</form>
	</p>
	<p>
		<h3>2. 사용자 인증 획득 및 Access 토큰 획득</h3>
		1번에서 받은 user_auth_url 로 이동하여 인증하십시요.
		<div>
			<?php 
if (isset($_SESSION['user_auth_url'])) {
	$url = $_SESSION['user_auth_url'];
	print '<a href="' . $url . '">' . $url . '</a>';
}
			?>
		</div>
	</p>
	<p>
		<h3>3. 보호된 리소스에 접근</h3>
		다음 오픈 API 중 "블로그 정보 가져오기"의 예제(http://apis.daum.net/blog/info/blog.do).
		<form>
			<input name="o_mode" value="blog_info" type="hidden" />
			블로그 아이디: <input name="blog_id" value="<?php
print isset($blog_id) ? $blog_id : 'ahahblog';		
			?>" type="text" />
			<br/><input type="submit" />
		</form>
		<div>
			<pre><?php
if (isset($_SESSION['blog_info'])) {
	$info = $_SESSION['blog_info'];
	print htmlspecialchars($info);
}
			?></pre>
		</div>
	</p>
	<hr/>
	<addr>Powered by <a href="http://github.com/azki/Daum-OAuth">http://github.com/azki/Daum-OAuth</a></addr>
</body>
</html>