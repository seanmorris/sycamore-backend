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

	protected function testMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return [
			'id'             => 'https://sycamore-backend.herokuapp.com/helloworld'
			, 'type'         => 'Note'
			, 'published'    => $now
			, 'attributedTo' => 'https://sycamore-backend.herokuapp.com/actor'
			, 'inReplyTo'    => 'https://mastodon.social/@seanmorris/106793688635996404'
			, 'content'      => '<p>Hello, world!</p>'
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
		];
	}

	public function helloworld()
	{
		return json_encode($this->testMessage());
	}

	public function sendMessage()
	{
		$timeout = 3;
		$now = gmdate('D, d M Y H:i:s T');
		$to  = 'seanmorris@mastodon.social';
		$url = 'https://mastodon.social/inbox';

		$document = json_encode([
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'id'     => 'https://sycamore-backend.herokuapp.com/create-helloworld'
			, 'type'   => 'Create'
			, 'actor'  => 'https://sycamore-backend.herokuapp.com/actor'
			, 'object' => $this->textMessage()
		]);

		$hash = 'SHA-256=' . base64_encode(openssl_digest($document, 'SHA256', TRUE));
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
