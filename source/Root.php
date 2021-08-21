<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\Discovery;

class Root implements Routable
{
	public $routes = ['/.well-known/' => Discovery::CLASS];

	public function actor()
	{
		if(file_exists($publicKeyFile = IDS_ROOT . '/data/local/ssl/ids_rsa.pub.pem'))
		{
			$publicKey = file_get_contents($publicKeyFile);
		}
		else
		{
			$publicKey = Settings::read('actor', 'public', 'key');
		}

		return json_encode([
			'@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
		
			'preferredUsername' => 'sean',
			'id'    => 'https://sycamore-backend.herokuapp.com/actor',
			'type'  => 'Person',
			'inbox' => 'https://sycamore-backend.herokuapp.com/inbox',
			
			'publicKey' => [
				'id'           => 'https://sycamore-backend.herokuapp.com/actor#main-key',
				'owner'        => 'https://sycamore-backend.herokuapp.com/actor',
				'publicKeyPem' => $publicKey,
			]
		]);
	}

	public function sendMessage()
	{
		$document = json_encode([
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'id'     => 'https://sycamore-backend.herokuapp.com/create-hello-world'
			, 'type'   => 'Create'
			, 'actor'  => 'https://sycamore-backend.herokuapp.com/actor'
			, 'object' => [
				'id'             => 'https://sycamore-backend.herokuapp.com/hello-world'
				, 'type'         => 'Note'
				, 'published'    => ''
				, 'attributedTo' => 'https://sycamore-backend.herokuapp.com/actor'
				, 'content'      => '<b>Hello, world!</b>'
				, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
			]
		]);

		$timeout = 3;
		$hash = 'SHA-256=' . base64_encode(openssl_digest($document, 'SHA256', TRUE));
		$now = gmdate('D, d M Y H:i:s T');
		$key = '';
		$to  = 'seanmorris@mastodon.social';
		$url = 'https://mastodon.social/inbox';
		$requestTarget = sprintf('(request-target) post /inbox
host: mastodon.social
date: %s'
			, $now
		);

		if(file_exists($privateKeyFile = 'file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pem'))
		{
			$privateKey = openssl_pkey_get_private($privateKeyFile);
		}
		else
		{
			$privateKey = Settings::read('actor', 'private', 'key');
		}

		openssl_sign($requestTarget, $signature, $privateKey, OPENSSL_ALGO_SHA256);

		$signatureHeader = sprintf(
			'keyId="%s",headers="(request-target) host digest date",signature="%s"'
			, 'https://sycamore-backend.herokuapp.com/actor#main-key'
			, base64_encode($signature)
		);

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'content'     => $document
			, 'method'      => 'POST'
			, 'header' => [
				'Content-Type: application/json'
				, 'Signature: ' . $signatureHeader
				, 'Host: mastodon.social'
				, 'Digest: '. $hash
				, 'Date: ' . $now
			]
		]]);

		$handle = fopen($url, 'r', FALSE, $context);
		fpassthru($handle);
		fclose($handle);
	}
}
