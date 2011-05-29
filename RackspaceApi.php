<?

/**
 * Rackspace API Client
 *
 * @package	default
 * @author	Partido Surrealista Mexicano
 * @version	0.15
 */
class RackspaceApi
{
	
	//base del API para Auth
	public $base = "https://auth.api.rackspacecloud.com/v1.0";
	//por si me da hueva hacer un config
	private $user = 'user';
	private $key = 'key';
	//Acá dejamos toda la mamada que luego no uso
	protected $response = null;
	//mis headers por armar
	private $headers = array();
	
	
	/**
	 * Checa que pueda hacer tanta mamada, y si no hay pedo, inicializa sesión
	 *
	 * @param	string	$user 
	 * @param	string	$key 
	 * @author	Roberto Hidalgo
	 */
	public function __construct($user=null, $key=null)
	{
		$this->user = $user? $user : $this->user;
		$this->key = $key? $key : $this->key;
		
		if( !is_writable(dirname(__FILE__)) ){
			die('El directorio '.dirname(__FILE__).' no tiene permisos de escritura, no puedo continuar.');
		}
		
		if ( !function_exists('http_post_data') ){
			die('No está instalado pecl_http!');
		}
		
		return self::auth();
	}
	
	
	/**
	 * Prueba credenciales con el API de Rackspace
	 *
	 * @return	BOOL	Tengo necte ó no.
	 * @author	Roberto Hidalgo
	 */
	private function auth()
	{	
		// Me cago en APC y XCache por hacerme creer que tendría bendiciones en CLI
		// Ni pedo, de regreso al viejo amigo file_get/put_contents 
		if( file_exists(dirname(__FILE__).'/.rscache') ){
			$token = unserialize(file_get_contents(dirname(__FILE__).'/.rscache'));
			
			if( time() < $token->since+(3600*24) ){ //nomás tenemos una hora, wemembew?
				// Here be dragons:
				$this->x->server = $token->x->server;
				$this->x->dns = "https://dns.api.rackspacecloud.com/v1.0/588796";
				$this->token = $token->string;
				self::header('X-Auth-Token', $this->token);
				return true;
			}
		}
		
		//Agrega credenciales
		self::header(array(
			'X-Auth-User' => $this->user,
			'X-Auth-Key'=> $this->key
		));
		
		//print_r($this->headers);
		
		//manda a llamar el request, sin verbo REST
		self::request();
		
		if( $this->response->status == '204' ){
			$this->token = $this->response->headers['X-Auth-Token'];
			self::header('X-Auth-Token', $this->token);
			$token->string = $this->token;
			$token->since = time();
			
			//here be even more (algunos experimentales) dragons:
			$token->x->server = $this->response->headers['X-Server-Management-Url'];
			$token->x->dns = $this->response->headers['X-Dns-Management-Url']; //nomás me activen el API, lo pruebo
			
			//y mandamos el token y los URLs de acceso a servicios a cache
			file_put_contents(dirname(__FILE__).'/.rscache', serialize($token));
			$this->x->server = $token->x->server;
			return true;
		} else {
			throw new Exception('No pitufa el servicio, Usuario/PWD incorrecto');
			return false;
		}
	}
	
	
	/**
	 * Request contra el API de Rackspace
	 *
	 * @param	string	$endpoint 	La acción REST
	 * @param	string	$method 	El método HTTP
	 * @param	string	$data 		Los datos tal cual que voy a mandar
	 * @return	string	$output		El body del response, des-jzonizado if possible
	 * @author	Roberto Hidalgo
	 */
	protected function request($endpoint=false, $method = 'GET', $data=null)
	{
		$this->response = null;
		$metod = strtoupper($method);
		
		if( $this->token ){
			self::header('X-Auth-Token', $this->token);
		}
		
		$endpoint = $endpoint? "/$endpoint" : '';
		
		//Bien importante, en cada driver tengo que settear el URL del endpoint, que usualmente tiene un ID
		$url = "$this->base{$endpoint}";
		
		$options = array(
			'compress'		=> true,
			'verifypeer'	=> false, #si no, SSL por API endpoint me da hueva
			'headers'		=> $this->headers
		);
		
		$request = new HTTPRequest($url, constant("HTTP_METH_$method"), $options);
		if( $method!='GET' && $data!=null ){
			$m = strtolower($method);
			
			$request->addHeaders(array('Content-Type' => 'application/xml'));
			
			if($method=='POST'){
				$request->addPostData($data);
			}
			if( $method=='PUT' ){
				$request->addPutData($data);
			}
			
		}
		
		try {
			header('Content-type: text/plain');
			$response = $request->send();
			//print_r($request->getRawRequestMessage());
			//echo "\n\n";
			//print_r($response->toString());
			$this->response->headers = $response->getHeaders();
			$this->response->status = $response->getResponseCode();
			$body = $response->getBody();
			$json = json_decode($body);
			$output = $json? $json : $body;
			return $output;
		} catch (HttpException $ex) {
			return FALSE;
		}
		
	}
	
	
	/**
	 * Agrega header al request
	 *
	 * @param	string	$name 		El nombre del header
	 * @param	string	$content 	el valor del header
	 * @return	void
	 * @author	Roberto Hidalgo
	 */
	private function header($name, $content=null){
		if( is_array($name) ){
			$this->headers = array_merge($this->headers, $name);
		} else {
			$this->headers[$name] = urlencode($content);
		}	
	}
	
}