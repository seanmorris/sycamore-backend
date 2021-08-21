<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;
use \SeanMorris\Ids\Settings;

class Root implements Routable
{
	public function actor()
	{
		if(file_exists($publicKeyFile = IDS_ROOT . '/data/local/ssl/ids_rsa.pub.pem'))
		{
			$publicKey = file_get_contents($publicKeyFile);
		}
		else
		{
			$publicKey = Settings::read('actor', 'public' 'key');
		}

		return json_encode([
			'@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
		
			'preferredUsername' => 'seanmorris',
			'id'    => 'https://pub.seanmorr.is/actor',
			'type'  => 'Person',
			'inbox' => 'https://pub.seanmorr.is/inbox',
			
			'publicKey' => [
				'publicKeyPem' => $publicKey,
				'owner'        => 'https://pub.seanmorr.is/actor',
				'id'           => 'https://pub.seanmorr.is/actor#main-key',
			]
		]);
	}

	public function webfinger()
	{
		return json_encode([
			"subject" => "acct:sean@seanmorr.is",
			"links"   => [[
				"type" => "application/activity+json",
				"href" => "https://pub.seanmorr.is/actor",
				"rel"  => "self",
			]]
		]);
	}

	public function sendMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');
		$key = '';
		$to  = 'seanmorris@mastodon.social';

		$url     = 'https://mastodon.social/inbox';
		$timeout = 3;

		$requestTarget = sprintf('(request-target) post /inbox
host: mastodon.social
date: %s
'
			, $now
		);

		if(file_exists($privateKeyFile = 'file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pem'))
		{
			$privateKey = openssl_pkey_get_private($privateKeyFile);
		}
		else
		{
			$publicKey = Settings::read('actor', 'private' 'key');
		}

		openssl_sign($requestTarget, $signature, $privateKey);

		$signatureBase64  = base64_encode($signature);

		$context = stream_context_create($contextSource = ['http' => [
			'method'    => 'POST'
			, 'header' => [
				'Content-Type: application/json'
				, 'Signature: ' . $signatureBase64
				, 'Host: mastodon.social'
				, 'Date: ' . $now
			]
			, 'content' => json_encode([
				'@context' => 'https://www.w3.org/ns/activitystreams'
				, 'id'     => 'https://pub.seanmorr.is/create-hello-world'
				, 'type'   => 'Create'
				, 'actor'  => 'https://pub.seanmorr.is/actor'
				, 'object' => [
					'id'             => 'https://pub.seanmorr.is/hello-world'
					, 'type'         => 'Note'
					, 'published'    => ''
					, 'attributedTo' => 'https://pub.seanmorr.is/actor'
					, 'content'      => '<b>Hello, world!</b>'
					, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
				]
			])
		]]);

		$handle = fopen($url, 'r', FALSE, $context);
		fpassthru($handle);
		fclose($handle);
	}
}
