<?

class CloudServer extends RackspaceApi
{
	public function __construct($user=null, $key=null)
	{
		parent::__construct($user, $key);
		$this->base = $this->x->server;
	}
	
	public function lista($details = null)
	{
		$ep = 'servers';
		$ep .= $details? '/detail' : ''; 
		$response = self::request($ep);
		
		return $response->servers;
	}
	
	
	public function detalles($server)
	{
		$detalles = self::request("servers/$server");
		return $detalles->server;
	}	
	
}