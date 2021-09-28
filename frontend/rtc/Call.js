import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';
import { Router } from 'curvature/base/Router';

import { Access } from '../Access';

import { Server } from './Server';
import { Client } from './Client';

import { NoteModel } from '../activity-pub/NoteModel';

export class Call extends View
{
	template = require('./call.html');

	onAttach()
	{
		if('pickup' in Router.query)
		{
			this.answerCall();
		}
	}

	startCall()
	{

		// .then(stream => stream.getTracks().forEach(track => {
		// 	console.log(track);
		// }));
	}

	answerCall()
	{
		const onTrack = event => {
			if(event.detail.streams && event.detail.streams[0])
			{
				this.tags.remote.node.srcObject = event.detail.streams[0];
			}
			else
			{
				const stream = new MediaStream([event.detail.track]);

				this.tags.remote.node.srcObject = stream;
			}

			this.tags.remote.play();

			console.log(event);
		}

		const onOpen = event => {
			console.log('Connection opened!');

			navigator.mediaDevices.getUserMedia({video: true, audio: true}).then(stream => {

				this.tags.mirror.node.srcObject = stream;
				this.tags.mirror.play();

				stream.getTracks().forEach(track => window.server.addTrack(track, stream));

			});
		}

		server = this.getRtcServer();

		this.listen(server, 'negotiationneeded', event => console.log(event));
		this.listen(server, 'open', onOpen, {once:true});
		this.listen(server, 'track', onTrack);
	}

	sendCallInvite(content, inReplyTo)
	{
		const getBackend = Config.get('backend');
		const getUser = Access.whoAmI();

		window.client = this.getRtcClient();

		navigator.mediaDevices.getUserMedia({video: true, audio: true}).then(stream => {
			this.tags.mirror.node.srcObject = stream;
			this.tags.mirror.play();

			stream.getTracks().forEach(track => window.client.addTrack(track, stream));

			const onTrack = event => {

				console.log(event);

				if(event.detail.streams && event.detail.streams[0])
				{
					this.tags.remote.node.srcObject = event.detail.streams[0];
				}
				else
				{
					const stream = new MediaStream([event.detail.track]);

					this.tags.remote.node.srcObject = stream;
				}

				this.tags.remote.play();

				console.log('Opened track!');
			};

			window.client.addEventListener('track', onTrack);
			window.client.addEventListener('negotiationneeded', event => console.log(event));

			window.client.addEventListener('open', event => {
				console.log('Opened!');
			});

			return window.client.offer();

		}).then(token => {

			const body = JSON.stringify({
				'@context': 'https://www.w3.org/ns/activitystreams'
				, type: 'Invite'
				, object: {
					to: 'https://localhost/ap/actor/sean'
					, subType: 'rtc-call-invite'
					, content: token
				}
			}, null, 4);

			const mode = 'cors';
			const method = 'POST';

			const options = {method, body, mode, credentials: 'include'};

			// console.log(body);

			return Promise.all([getBackend, getUser])
			.then(([backend, user]) => fetch(backend + `/ap/actor/${user.username}/outbox`, options))

		});
	}

	getRtcServer(refresh = false)
	{
		if(this.server)
		{
			return this.server;
		}

		const rtcConfig = {
			iceServers: [
				// {urls: 'stun:stun1.l.google.com:19302'},
				// {urls: 'stun:stun2.l.google.com:19302'}
			]
		};

		const server = (!refresh && server) || new Server(rtcConfig);

		const onMessage = event => {

		};
		this.listen(server, 'message', onMessage);

		this.server = server;

		return server;
	}

	getRtcClient(refresh = false)
	{
		if(this.client)
		{
			return this.client;
		}

		const rtcConfig = {
			iceServers: [
				// {urls: 'stun:stun1.l.google.com:19302'},
				// {urls: 'stun:stun2.l.google.com:19302'}
			]
		};

		const client = (!refresh && this.client) || new Client(rtcConfig);

		const onOpen = event => console.log('Connection opened!');

		const onMessage = event => {

		};


		this.listen(client, 'open', onOpen, {once:true});
		this.listen(client, 'message', onMessage);

		this.client = client;

		return client;
	}
}
