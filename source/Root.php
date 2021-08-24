<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

use \SeanMorris\Sycamore\Discovery;
use \SeanMorris\Sycamore\Payment;
use \SeanMorris\Sycamore\ActivityPub\Root as ActivityPubRoot;

class Root extends Controller
{
	public $routes = [
		'/.well-known/' => Discovery::CLASS
		, '/pay/'       => Payment::CLASS
		, '/ap/'        => ActivityPubRoot::CLASS
	];

	public function index($router)
	{
		header('Content-Type: text/plain');

		return 'It works!';
	}

	// public function superchat($router)
	// {
	// 	header('Content-Type: text/plain');

	// 	$matrix = new Matrix('https://matrix.org');

	// 	$matrix->login();

	// 	return $matrix->send(
	// 		'!FIoireJEFPfTCUfUrL:matrix.org'
	// 		, 'sycamore-backend test'
	// 	);
	// }

	public function sean()
	{
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/jrd+json; charset=utf-8');

		if(file_exists($publicKeyFile = IDS_ROOT . '/data/local/ssl/ids_rsa.pub.pem'))
		{
			$publicKey = file_get_contents($publicKeyFile);
		}
		else
		{
			$publicKey = Settings::read('actor', 'public', 'key');
		}

		return json_encode([
			'@context' => [
				'https://www.w3.org/ns/activitystreams',
				'https://w3id.org/security/v1'
			],
			'id'    => 'https://sycamore-backend.herokuapp.com/sean',
			'type'  => 'Person',
			'preferredUsername' => 'sean',
			'inbox' => 'https://sycamore-backend.herokuapp.com/inbox',

			'publicKey' => [
				'id'           => 'https://sycamore-backend.herokuapp.com/sean#main-key',
				'owner'        => 'https://sycamore-backend.herokuapp.com/sean',
				'publicKeyPem' => $publicKey,
			]
		]);
	}

	protected function createTestMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return [
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'type'   => 'Create'
			, 'id'     => 'https://sycamore-backend.herokuapp.com/createhelloworld'
			, 'actor'  => 'https://sycamore-backend.herokuapp.com/sean'
			, 'object' => $this->testMessage()
		];
	}

	protected function testMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return [
			'id'             => 'https://sycamore-backend.herokuapp.com/helloworld'
			, 'type'         => 'Note'
			, 'published'    => $now
			, 'attributedTo' => 'https://sycamore-backend.herokuapp.com/sean'
			// , 'inReplyTo'    => 'https://noovi.org/display/a2a4b854-1861-21f3-3532-855982766261'
			, 'inReplyTo'    => 'https://mastodon.social/@seanmorris/106798459503650980'
			, 'content'      => 'Hello, world!'
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
		];
	}

	public function createhelloworld()
	{
		header('Content-Type: application/jrd+json; charset=utf-8');

		return json_encode($this->createTestMessage());
	}

	public function helloworld()
	{
		header('Content-Type: application/jrd+json; charset=utf-8');

		return json_encode($this->testMessage());
	}

	public function sendMessage()
	{
		header('Content-Type: text/plain');

		$timeout = 3;
		$now  = gmdate('D, d M Y H:i:s T');
		// $host = 'noovi.org';
		$host = 'mastodon.social';
		$url  = sprintf('https://%s/inbox', $host);

		$document = json_encode($this->createTestMessage());

		$hash = 'SHA-256=' . base64_encode(hash('SHA256', $document, TRUE));
		$requestTarget = sprintf('(request-target): post /inbox
host: %s
date: %s
digest: %s', $host, $now, $hash);

		if(file_exists($privateKeyFile = 'file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pem'))
		{
			$privateKey = openssl_pkey_get_private($privateKeyFile);
			$publicKey  = file_get_contents('file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pub.pem');
		}
		else
		{
			$privateKey = openssl_pkey_get_private(Settings::read('actor', 'private', 'key'));
			$publicKey = Settings::read('actor', 'public', 'key');
		}

		$signature = '';

		openssl_sign($requestTarget, $signature, $privateKey, 'sha256WithRSAEncryption');

		$signatureHeader = sprintf(
			'keyId="%s",headers="(request-target) host date digest",signature="%s"'
			, 'https://sycamore-backend.herokuapp.com/sean#main-key'
			, base64_encode($signature)
		);

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'content'     => $document
			, 'method'      => 'POST'
			, 'header' => [
				'Content-Type: application/json'
				, 'Host: '      . $host
				, 'Date: '      . $now
				, 'Digest: '    . $hash
				, 'Signature: ' . $signatureHeader
			]
		]]);

		$body    = file_get_contents($url, FALSE, $context);
		$headers = print_r($http_response_header, 1) . PHP_EOL;

		return $headers . $body;
	}
}
