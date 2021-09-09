import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

import { Access } from '../Access';

import { Server } from './Server';
import { Client } from './Client';

import { NoteModel } from '../activity-pub/NoteModel';

export class Call extends View
{
	template = require('./call.html')

	startCall()
	{

		// .then(stream => stream.getTracks().forEach(track => {
		// 	console.log(track);
		// }));
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

			const mode = 'cors';
			const method = 'POST';

			window.client.offer().then(token => {

				const body = JSON.stringify({
					'@context': 'https://www.w3.org/ns/activitystreams'
					, type: 'Invite'
					, object: {
						subType: 'rtc-call-invite'
						, content: token
					}
				}, null, 4);

				const options = {method, body, mode, credentials: 'include'};

				// console.log(body);

				return getUser.then(user => {
					const path   = `/ap/actor/${user.username}/outbox`;
					return Promise.all([getBackend, path])
				}).then(([backend,path]) => fetch(backend + path, options))
				.then(r=>r.json())
				.then(outbox => fetch(outbox.last))
				.then(r=>r.json())
				.then(outbox => outbox.orderedItems.forEach(item => {
					// console.log(item);
					// NoteModel.get(item.object ? item.object.id : item.id);
				}));
			});

			window.client.addEventListener('open', event => {
				console.log('Opened!')
			});
		});

		window.server.addEventListener('track', event => {
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

		const onOpen = event => console.log('Connection opened!');

		const onMessage = event => {

		};

		this.listen(server, 'open', onOpen, {once:true});
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
