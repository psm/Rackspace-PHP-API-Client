<?
require 'RackspaceApi.php';
require 'RackspaceServer.php';

@include 'rackspace.config.php';

$cs = new CloudServer($config['user'], $config['key']);
print_r($cs->lista(true));