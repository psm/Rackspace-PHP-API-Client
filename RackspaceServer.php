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
		$servers = $response['output']->servers;
		
		foreach($servers as $server){
			self::detalles($server->id);
		}
	}
	
	
	public function detalles($server)
	{
		$detalles = self::request("servers/$server");
		print_r($detalles['output']->server);
	}	
	
}