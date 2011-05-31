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
		$ep = 'domains';
		$ep .= $details? '/detail' : ''; 
		$response = self::request($ep);
		return $response->domains;
	}
	
	public function detalles($dominio)
	{
		$ep = 'domains/'.$dominio;
		$response = self::request($ep);
		return $response->domains;
	}
	
	
	public function modificaRecord($dominio, $record, $data)
	{
		$ep = "domains/$dominio/records/";
		$data['id'] = "{$data['type']}-$record";
		unset($data['type']);
		$record = "<records xmlns=\"http://docs.rackspacecloud.com/dns/api/v1.0\"><record ".self::toXML($data)."></record></records>";
		$response = self::request($ep, 'PUT', $record);
	}
	
	
	function toXML($data)
	{
		$r = array();
		foreach($data as $k=>$v){
			$r[] = "$k=\"$v\"";
		}
		return join(' ', $r);
	}
	
}