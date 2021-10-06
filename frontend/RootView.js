import { Tag } from 'curvature/base/Tag';
import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';
import { Router } from 'curvature/base/Router';

import { NoteModel } from './activity-pub/NoteModel';

import { ModalHost } from './ui/ModalHost';
import { UserList } from './UserList';
import { Github } from './Github';
import { Access } from './Access';

import { Application } from './Application';

import { LoginView } from './ui/LoginView';
import { RegisterView } from './ui/RegisterView';
import { ConfirmModal } from './ui/ConfirmModal';

import { ArcType } from './ui/ArcType';

import { Server as RtcServer } from './rtc/Server';

Application.modalHost = new ModalHost;

export class RootView extends View
{
	template = require('./root.html');

	constructor(args)
	{
		super(args);

		// this.args.arc = new ArcType;

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

					if(window.client && !window.client.connected)
					{
						window.client.accept(invitation.object.content);
					}
				}

				if(notification.action === 'invite')
				{
					const video = new Tag('<video>');

					const invitation = JSON.parse(notification.invitation);

					const modal = new ConfirmModal;

					modal.args.question = 'Accept call?';

					modal.addEventListener('modalAccept', event => {

						this.acceptCall(invitation);

						Router.go('call?pickup');

					});

					modal.addEventListener('modalAccept', event => Application.modalHost.remove(modal));
					modal.addEventListener('modalReject', event => Application.modalHost.remove(modal));

					Application.modalHost.add(modal);

					window.server.addEventListener('open', event => console.log('Opened!'));
				}

			}));
		});

		Access.whoAmI().then(user => this.args.loggedIn = !!user);
	}

	onAttach()
	{
		document.addEventListener('focus', event => {

			const target = event.target;

			if(target.selectionStart === null)
			{
				return;
			}

			// this.args.arc.activate(target);

		}, {capture: true});

		// document.addEventListener('blur', event => this.args.arc.deactivate(), {capture: true});
	}

	acceptCall(invitation)
	{
		window.server.answer(invitation.object.content).then(token => {
			const mode = 'cors';
			const method = 'POST';
			const body = JSON.stringify({
				'@context': 'https://www.w3.org/ns/activitystreams'
				, type: 'Accept'
				, object: {
					to: invitation.actor
					, subType: 'rtc-call-accept'
					, content: token
					, inReplyTo: invitation.id
				}
			}, null, 4);

			const options = {method, body, mode, credentials: 'include'};

			const getUser = Access.whoAmI();
			const getBackend = Config.get('backend');

			return Promise.all([getBackend, getUser])
			.then(([backend, user]) => fetch(backend + `/ap/actor/${user.username}/outbox`, options));
		});
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
