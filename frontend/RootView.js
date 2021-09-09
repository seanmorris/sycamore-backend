import { Tag } from 'curvature/base/Tag';
import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

import { NoteModel } from './activity-pub/NoteModel';

import { ModalHost } from './ui/ModalHost';
import { UserList } from './UserList';
import { Github } from './Github';
import { Access } from './Access';

import { Application } from './Application';

import { LoginView } from './ui/LoginView';
import { RegisterView } from './ui/RegisterView';

import { Server as RtcServer } from './rtc/Server';

Application.modalHost = new ModalHost;

export class RootView extends View
{
	template = require('./root.html');

	constructor(args)
	{
		super(args);

		this.args.profileName  = 'Sycamore';
		this.args.profileTheme = 0 ? 'red-dots' : 'maple-tree';
		this.args.modalHost = Application.modalHost;

		this.args.anyLive = false;
		this.args.live = {};


		this.getNotifier = Config.get('backend')
			.then(backend => new EventSource(backend + '/notify/events'));

		this.getNotifier.then(notifier => notifier.addEventListener('ServerEvent', event => {

			const notification = JSON.parse(event.data);

			if(!notification)
			{
				return;
			}

			if(notification.action === 'live-started')
			{
				this.args.live[notification.stream] = true;

				this.args.anyLive = !!Object.values(this.args.live).length;
			}

			if(notification.action === 'live-completed')
			{
				delete this.args.live[notification.stream];

				this.args.anyLive = !!Object.values(this.args.live).length;
			}
		}));

		window.server = new RtcServer;

		this.onTimeout(500, () => {
			this.getNotifier.then(notifier => notifier.addEventListener('ServerEvent', event => {

				const notification = JSON.parse(event.data);

				if(notification.action === 'accept' && window.client)
				{
					const invitation = JSON.parse(notification.invitation);

					console.log(invitation);

					window.client.accept(invitation.object.content);
				}

				if(notification.action === 'invite')
				{
					const video = new Tag('<video>');

					const invitation = JSON.parse(notification.invitation);

					console.log(invitation);

					window.server.answer(invitation.object.content).then(token => {
						const mode = 'cors';
						const method = 'POST';
						const body = JSON.stringify({
							'@context': 'https://www.w3.org/ns/activitystreams'
							, type: 'Accept'
							, object: {
								subType: 'rtc-call-accept'
								, content: token
								, inReplyTo: invitation.id
							}
						}, null, 4);

						const options = {method, body, mode, credentials: 'include'};

						const getUser = Access.whoAmI();
						const getBackend = Config.get('backend');

						console.log(body);

						return getUser.then(user => {
							const path   = `/ap/actor/${user.username}/outbox`;
							return Promise.all([getBackend, path])
						}).then(([backend,path]) => fetch(backend + path, options))
						.then(r=>r.json())
						.then(outbox => fetch(outbox.last))
						.then(r=>r.json())
						.then(outbox => outbox.orderedItems.forEach(item => {
							// NoteModel.get(item.object ? item.object.id : item.id);
						}))
					});

					window.server.addEventListener('open', event => console.log('Opened!'));
				}

			}));
		});

		Access.whoAmI().then(user => this.args.loggedIn = !!user);
	}

	localLoginClicked(event)
	{
		const login = new LoginView;

		Application.modalHost.add(login);

		login.addEventListener('modalSuccess', () => {
			this.args.loggedIn = true;
		});
	}

	registerClicked(event)
	{
		const register = new RegisterView;

		Application.modalHost.add(register)

		register.addEventListener('modalSuccess', () => {
			this.args.loggedIn = true;
		});
	}

	matrixLoginClicked(event)
	{
		matrix.initSso(location.origin);
	}

	githubLoginClicked(event)
	{
		Github.login();
	}

	passwordHasherClicked(event)
	{
		window.open(Config.get('hasher'));
	}

	openSettings()
	{
		this.args.settings = this.args.settings ? null : new UserList;
	}
}
