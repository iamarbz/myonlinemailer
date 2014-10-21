<form method=POST action="<?php echo $_SERVER['PHP_SELF'];?>" >
<input type=email required name=email>
<input type=submit name=post>
</form>
<?php 

/*#**		 my own Version ** ;p lol tinamban..wahaha**
****/
	class EmailAddress {
		public $user;
		public $domain;
		public $name;
		public function __construct($user, $domain, $name=null) {
			$this->user = $user;
			$this->domain = $domain;
			$this->name = $name;
			print "==> Created user [".$this->addressWithName()."]<br>";
		}
		public function address() {
			return $this->user.'@'.$this->domain;
		}
		public function addressWithName() {
			return sprintf('%s <%s@%s>', $this->name, $this->user, $this->domain);
		}
	}
	class EmailMessage {
		public $to;
		public $cc;
		public $bcc;
		public $from;
		public $subject;
		public $headers;
		public $message;
		const EOL = "\r\n";
		public function __construct($to, $cc=array(), $bcc=array(), $from, $subject, $headers=array(), $message=null) {
			print "==> Creating message...<br>";
			$this->to = $to;
			$this->cc = ($cc ?: array());
			$this->bcc = ($bcc ?: array());
			$this->from = $from;
			$this->subject = $subject;
			$this->headers = ($headers ?: array());
//$this->headers.="MIME-Version: 1.0\r\n";
//$this->headers.="Content-Type: text/html; charset=ISO-8859-1\r\n";
	
			$this->message = $message;
		}
		static function recipientsAsArrayOfStrings($list) {
			$recipients = array();
			foreach($list as $r) {
				$recipients[] = $r->addressWithName();
			}
			return $recipients;
		}
		public function content() {
			// setup message headers
			if(!isset($this->headers['Date']))
				$this->headers['Date'] = date(DATE_RFC822);
			if(!isset($this->headers['Subject']))
				$this->headers['Subject'] = $this->subject;
				//$this->headers['Subject'] = '=?UTF-8?B?'.base64_encode($this->subject).'?=';
			if(!isset($this->headers['From']))
				$this->headers['From'] = $this->from->addressWithName();
			if(!isset($this->headers['To']) && $this->to)
				$this->headers['To'] = implode(', ', self::recipientsAsArrayOfStrings($this->to));
			if(!isset($this->headers['Cc']) && $this->cc)
				$this->headers['Cc'] = implode(', ', self::recipientsAsArrayOfStrings($this->cc));
			// setup message content
		$content = '';
			foreach($this->headers as $name => $value) {
				$content .= $name.': '.$value.self::EOL;
			}
			$content = trim($content).self::EOL.self::EOL.$this->message.self::EOL;
			return $content;
		}
	}
	class SmtpServer {
		protected $server;
		protected $port;
		protected $username;
		protected $password;
		protected $secure;
		protected $helo;
		private $connection;
		const EOL = "\r\n";
		public function __construct($server, $port=25, $username=null, $password=null, $secure=false) {
			$this->server = ($secure == 'ssl') ? 'ssl://'.$server : $server;
			$this->port = $port;
			$this->username = $username;
			$this->password = $password;
			$this->secure = $secure;
			$this->helo = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : gethostname());
			print "==> Created SMTP server.<br>";
		}
		public function __destruct() {
			$this->disconnect();
		}
		protected function read() {
			$data = '';
			while($str = fgets($this->connection, 4096)) {
				$data .= $str;
				if($str[3] == ' ') break;
			}
			//print $data;
			return $data;
		}
		protected function write($data, $eol=true) {
			if(!$this->connection)
				throw new Exception('Server not connected.');
			fputs($this->connection, $data.($eol ? self::EOL : ''));
			//print $data."<br>";
		}
		protected function code() {
			return substr($this->read(), 0, 3);
		}
		protected function command($data, $eol=true) {
			$this->write($data, $eol);
			return $this->code();
		}
		protected function connect() {
			//print "==> Connecting to server {$this->server}:{$this->port}...<br>";
			$this->connection = fsockopen($this->server, $this->port, $errno, $errstr, 15);
			if(!$this->connection) throw new Exception('Could not connect to server');
			if($this->code() != 220) throw new Exception('SMTP error');
		}
		protected function disconnect() {
			if($this->connection) {
				return "==> Closing connection...<br>";
				$this->command('QUIT');
				fclose($this->connection);
				$this->connection = null;
			}
		}
		protected function authenticate() {
			//print "==> Authenticating with server...<br>";
			$this->command('EHLO '.$this->helo);
			if($this->secure == 'tls') {
				return "==> Starting TLS session...<br>";
				if($this->command('STARTTLS') != 220) throw new Exception('Cannot start TLS session.');
				stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
				if($this->command('EHLO'.$this->helo) != 250) throw new Exception('SMTP error on TLS HELO.');
			}
			if(!is_null($this->username) && !is_null($this->password)) {
				if($this->command('AUTH LOGIN') != 334) throw new Exception('SMTP AUTH error.');
				if($this->command(base64_encode($this->username)) != 334) throw new Exception('SMTP AUTH error');
				if($this->command(base64_encode($this->password)) != 235) throw new Exception('SMTP AUTH error');
			}
		}
		public function sendWithMessage(EmailMessage $message) {
			//print "==> Sending message...<br>";
			// consolidate addresses
			$to = array();
			foreach($message->to as $r) { $to[$r->address()] = $r->name; }
			foreach($message->cc as $r) { $to[$r->address()] = $r->name; }
			foreach($message->bcc as $r) { $to[$r->address()] = $r->name; }
			// send
			$this->send($message->from->address(), array_keys($to), $message->content());
		}
		public function send($from, $to, $content) {
			//print "==> Sending...<br>";
			if(!$from) throw new Exception("No MAIL FROM data.<br>");
			if(!$to) throw new Exception("No RCPT TO data.<br>");
			if(!$content) throw new Exception("No DATA.<br>");
			// connect to server
			if(!$this->connection) {
				$this->connect();
				$this->authenticate();
			}
			if($this->command('MAIL FROM: <'.$from.'>') != 250) throw new Exception('Address was not accepted.');
			$atLeastOneAddressAccepted = false;
			foreach($to as $t)
				if($this->command('RCPT TO: <'.$t.'>') == 250) $atLeastOneAddressAccepted = true;
			if($atLeastOneAddressAccepted == false)
				throw new Exception('None of the recipient addresses were accepted.');
			if($this->command('DATA') != 354) throw new Exception('Server not ready to accept data.');
			if($this->command($content.'.') != 250) throw new Exception('Could not send message.');
			$this->disconnect();
		}
	}
	class SmtpMTA {
		private $message;
		private $domains;
		public function __construct($message) {
			$this->message = $message;
		}
		public function send() {
			$domains = array();

			foreach($this->message->bcc as $r) { $domains[$r->domain]['users'][$r->user] = $r; }
			foreach($this->message->cc as $r) { $domains[$r->domain]['users'][$r->user] = $r; }
			foreach($this->message->to as $r) { $domains[$r->domain]['users'][$r->user] = $r; }

			// setup mx records
		//	print "==> Getting MX records...<br>";
			foreach($domains as $domain => &$values) {
				$dns = null;
				$weight = null;
				if(getmxrr($domain, $dns, $weight)) {
					$values['mx'] = array_combine($weight, $dns);
					ksort($values['mx']);
				}
			}
			foreach($domains as $domain => $domainValues) {
				$recipients = array();
				foreach($domainValues['users'] as $user => $name) { $recipients[] = $user.'@'.$domain; }
				$server = reset($domainValues['mx']);
				$smtp = new SmtpServer($server);
				$smtp->send($this->message->from->address(), $recipients, $this->message->content());
			}
		}
	}
if(isset($_POST['post'])){
$email = (isset($_POST['email'])) ? trim($_POST['email']) : "kdpublisherpro@gmail.com";
$email=explode("@",$email);	

$msg = new EmailMessage(
		array(
			new EmailAddress($email[0], $email[1], 'Member')
		),
		null,
		null,
		new EmailAddress('pmtaonline', 'pmtaonline.marketing', 'Payment Notification Pro'),
	"Thank you for your Purchase of 1 Site Builder. Please proceed",
	                    null,
		"Thanks and God bless!"
	);
$svr=new SmtpServer('yoursmtp.domain.com',25,'youruser@yoursmtpdomain.com','yourpass','');
	$svr->sendWithMessage($msg);
               echo "sent to ".$email[0];
               exit(1);	
}
	$mta = new SmtpMTA($msg);
	$mta->send();

?>
