<?
class RackspaceApi
{
	
	public $base = "https://auth.api.rackspacecloud.com/v1.0";
	private $user = 'user';
	private $key = 'key';
	private $version = '1.0';

	private $headers = array();
	private $method = 'GET';
	
	public function __construct($user=null, $key=null)
	{
		$this->user = $user? $user : $this->user;
		$this->key = $key? $key : $this->key;
		
		if( !is_writable(dirname(__FILE__)) ){
			die('El directorio '.dirname(__FILE__).' no tiene permisos de escritura, no puedo continuar.');
		}
		
		return self::auth();
	}
	
	
	private function auth()
	{		
		if( file_exists(dirname(__FILE__).'/.rscache') ){
			$token = unserialize(file_get_contents(dirname(__FILE__).'/.rscache'));
			if( $token->since < time() ){
				$this->x->server = $token->x->server;
				$this->token = $token->string;
				self::header('X-Auth-Token', $this->token);
				return true;
			}
		}
		
		self::header('X-Auth-User', $this->user);
		self::header('X-Auth-Key', $this->key);
		$response = self::request();
		if( $response['code'] == '204' ){
			$this->token = $response['headers']['X-Auth-Token'];
			self::header('X-Auth-Token', $this->token);
			$token->string = $this->token;
			$token->since = time();
			$token->x->server = $response['headers']['X-Server-Management-Url'];
			$token->x->dns = $response['headers']['X-Dns-Management-Url'];
			file_put_contents(dirname(__FILE__).'/.rscache', serialize($token));
			$this->x->server = $token->x->server;
			return true;
		} else {
			print_r($response);
			die('No pitufa el servicio, Usuario/PWD incorrecto');
		}
	}
	
	
	protected function request($endpoint=false, $method = 'GET')
	{
		if( $this->token ){
			self::header('X-Auth-Token', $this->token);
		}
		$opts = array(
			CURLINFO_HEADER_OUT => true,
			CURLOPT_HTTPHEADER => self::headers(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5
		);
		if ($method != 'GET'){
			$opts[CURLOPT_POST] = true;
			$opts[CURLOPT_POSTFILEDS] = $method;
		}
		$endpoint = $endpoint? "/$endpoint.json" : '';
		$url = "$this->base{$endpoint}";
		//echo $url;
		$c = curl_init($url);
		curl_setopt_array($c, $opts);
		
		//print_r(self::headers());
		//echo "$url\n";
		//die();
		
		$this->headers = array();
		$ret = curl_exec($c);
		if($ret){
			return self::parse($ret);
		} else {
			die(curl_error($c));
		}
		
	}
	
	
	private function parse($output)
	{
		$full = $output;
		list($headers, $content) = explode("\r\n\r\n", $output);
		$headers = explode("\n", $headers);
		$code = preg_replace("/.+([\d]{3}).+/", "$1", array_shift($headers));
		foreach($headers as $header){
			list($name, $header) = explode(': ', $header);
			if(trim($name)=='' && $name){
				continue;
			}
			$h[trim($name)] = trim($header);
		}
		$headers = $h;
		$json = json_decode($content);
		$output = json_last_error()===JSON_ERROR_NONE? $json : $content;
		
		$response = compact('code', 'headers', 'output', 'full');
		return $response;
	}
	
	
	private function headers()
	{
		$return = array();
		foreach( $this->headers as $name=>$content ){
			$return[] = "$name: $content";
		}
		return $return;
	}
	
	
	private function header($name, $content){
		$this->headers[$name] = urlencode($content);
	}
	
}