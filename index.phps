<?php
require_once('./daumOAuth.php');

session_start();//for save request token.

$consumer_key = '090cb499-6e91-488c-bb96-fbf4be7d2ae0';
$consumer_secret = 'T7jB3zXmcH.J76X5wkfKy1biG4My4PNNl4r.TZ9ZHw5.S1wRz9GSRg00';
$callback_url = 'http://daumoauth.azki.org/';

$session_var_names = array('request_token', 'user_auth_url', 'access_token', 'blog_info');

$o_mode = @$_REQUEST['o_mode'];
switch ($o_mode) {
	default:
		//ó�� ���ӽ� or 2���� �ݹ����� ���ͼ� 3�� ����.
		$o_token = @$_REQUEST['oauth_token'];
		$o_verifier = @$_REQUEST['oauth_verifier'];
		if (isset($o_token) && isset($o_verifier)) {
			//2������ ���� �Ŀ� �ݹ� URL�� �ٽ� ���ƿ��� ��.
			$rTok = $_SESSION['request_token'];
			$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url, $o_token, $rTok['oauth_token_secret']);
			$aTok = @$to->getAccessToken($o_verifier);
			$_SESSION['access_token'] = $aTok;
		}
		else {
			//ó�� ���ӽ�.
			foreach ($session_var_names as $var_name) {
				if (isset($_SESSION[$var_name])) {
					unset($_SESSION[$var_name]);
				}
			}
		}
		break;
	case 'request_token':
		//1���� ��ư�� ������ �����.
		$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url);
		$rTok = $to->getRequestToken();
		$url = $to->getAuthorizeURL($rTok);
		$_SESSION['request_token'] = $rTok;
		$_SESSION['user_auth_url'] = $url;
		break;
	case 'blog_info':
		//3���� ��ư�� ������ �����.
		$aTok = @$_SESSION['access_token'];
		$blog_id = $_REQUEST['blog_id'];
		if (isset($aTok)) {
			$to = new DaumOAuth($consumer_key, $consumer_secret, $callback_url, $aTok['oauth_token'], $aTok['oauth_token_secret']);
			$url = 'http://apis.daum.net/blog/info/blog.do';
			$args = array('blogName'=>$blog_id);
			$response = $to->OAuthRequest($url, $args);
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
		<h3>0. ���� �ʱ�ȭ �ϱ�.</h3>
		<form>
			<input name="o_mode" value="reset" type="hidden" />
			<input type="submit" />
		</form>
	</p>
	<p>
		<h3>1. �������� ���� Request ��ū ȹ�� �� ���� ���ι��̴��� ����� ���� URL ���ϱ�.</h3>
		<form>
			<input name="o_mode" value="request_token" type="hidden" />
			<input type="submit" />
		</form>
	</p>
	<p>
		<h3>2. ����� ���� ȹ�� �� Access ��ū ȹ��</h3>
		1������ ���� user_auth_url �� �̵��Ͽ� �����Ͻʽÿ�.
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
		<h3>3. ��ȣ�� ���ҽ��� ����</h3>
		���� ���� API �� "��α� ���� ��������"�� ����(http://apis.daum.net/blog/info/blog.do).
		<form>
			<input name="o_mode" value="blog_info" type="hidden" />
			��α� ���̵�: <input name="blog_id" value="<?php
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