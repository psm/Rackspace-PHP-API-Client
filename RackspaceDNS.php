<?

class CloudDNS extends RackspaceApi
{
	public function __construct($user=null, $key=null)
	{
		parent::__construct($user, $key);
		$this->base = $this->x->dns;
	}
	
	public function lista($details = null)
	{
	
	}
	
	
	public function detalles($server)
	{
		
	}	
	
}