<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;
use \SeanMorris\Sycamore\ActivityPub\Type\Note;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;

class Root extends Controller
{
	public $routes = [
		'/outbox/'  => Outbox::CLASS
		, '/inbox/' => PublicInbox::CLASS
		, '/actor/' => ActorList::CLASS
	];

	public function index($router)
	{
		header('Content-Type: application/ld+json');

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');

		return json_encode([
			'actors'  => 'http://' . $domain . '/ap/actor'
			, 'actor' => 'http://' . $domain . '/ap/actor/{NAME}'
		]);
	}

	protected function testMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return Note::consume([
			// 'id'             => 'https://sycamore-backend.herokuapp.com/helloworld'
			'type'           => 'Note'
			, 'published'    => $now
			, 'attributedTo' => 'https://sycamore-backend.herokuapp.com/ap/actor/sean'
			// , 'inReplyTo'    => 'https://noovi.org/display/a2a4b854-1861-21f3-3532-855982766261'
			// , 'inReplyTo'    => 'https://mastodon.social/@seanmorris/106798459503650980'
			// , 'inReplyTo'    => 'http://localhost:2020/ap/actor/sean/outbox/1'
			, 'content'      => 'Hello, world!'
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
		]);
	}

	protected function createTestMessage()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return Create::consume([
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'type'   => 'Create'
			, 'actor'  => 'https://sycamore-backend.herokuapp.com/ap/actor/sean'
			, 'object' => $this->testMessage()
			// , 'id'     => 'https://sycamore-backend.herokuapp.com/createhelloworld'
		]);
	}

	public function sendMessage()
	{
		header('Content-Type: text/plain');

		$timeout = 3;
		$now = gmdate('D, d M Y H:i:s T');
		// $host = 'noovi.org';

		$host = 'mastodon.social';
		$url  = sprintf('https://%s/inbox', $host);

		// $host = '10.0.0.1:2020';
		// $url  = sprintf('http://%s/ap/inbox', $host);

		// $host = 'sycamore-backend.herokuapp.com';
		// $url  = sprintf('https://%s/ap/inbox', $host);

		$activity = $this->createTestMessage();

		$document = json_encode($activity->unconsume());

		$activity->store('activity-pub::outbox::' . 'sean');

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

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';
		// $domain = 'https://sycamore-backend.herokuapp.com';

		$signatureHeader = sprintf(
			'keyId="%s",headers="(request-target) host date digest",signature="%s"'
			, $scheme . $domain . '/ap/actor/sean#main-key'
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
