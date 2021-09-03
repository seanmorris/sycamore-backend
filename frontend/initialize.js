import { Config } from 'curvature/base/Config';
import { Bindable } from 'curvature/base/Bindable';
import { Router } from 'curvature/base/Router';

import { RootView } from './RootView';
import { FeedView } from './FeedView';
import { Livestream } from './Livestream';
import { CaptionView } from './CaptionView';

import { UserView } from './UserView';
import { UserModel } from './UserModel';

import { MessageModel } from './MessageModel';

import { WarehouseConsole } from './WarehouseConsole';

import { Matrix } from './Matrix';
import { Installer } from './Installer';
import { EventModel as MatrixEvent } from './matrix/EventModel';
import { EventDatabase } from './matrix/EventDatabase';

import { SettingsView } from './ui/SettingsView';
import { RegisterView } from './ui/RegisterView';
import { LoginView } from './ui/LoginView';
import { Lookup } from './profile/Lookup';

import { Sycamore } from './Sycamore';
import { Socket as ObsSocket } from './obs/Socket';

Object.defineProperty(window, 'matrix',  {value: new Matrix});
Object.defineProperty(window, 'webTorrent', {value: new WebTorrent});
Object.defineProperty(window, 'webTorrentSeed', {value: new WebTorrent});

Config.set('backend', Promise.resolve(
	location.host === 'sycamore.seanmorr.is'
		? 'https://sycamore-backend.seanmorr.is'
		: 'http://10.0.0.1:2020'
));

// Config.set('backend', Promise.resolve('http://127.0.0.1:2020'));
// Config.set('backend', Promise.resolve(''));

// ObsSocket.get('ws://localhost:4444').then(socket => {
// 	socket.request('GetSceneItemList')
// 	.then( ({sceneItems}) => Promise.all(sceneItems.map(
// 		item => socket.request('GetSceneItemProperties', {item:{id: item.itemId}}))
// 	))
// 	.then(responses => responses.map(response =>console.log(response)));
// }).catch(error => {});

const view = new RootView;

const routes = {
	'': args => {
		const feed = new FeedView({...args, showForm: true});
		return feed;
	}

	, settings: SettingsView
	, installer: Installer
	, 'profile-lookup': Lookup

	, live: () => new Livestream

	, 'my-feed': () => {

		Sycamore.getSettings().then(settings => {
			Router.go(`/feed/${settings.privateFeed}`);
		});
	}

	, 'captions': () => {

		view.args.page = 'captions';

		return new CaptionView;
	}

	, 'feed/%room_id': args => {
		const feed = new FeedView(args);
		return feed;
	}

	, 'user/%uid': args => new UserView(args)
	, 'warehouse': args => new WarehouseConsole(args)

	, register: () => {
		return new RegisterView;
	}

	, login: () => {
		return new LoginView;
	}
};

const token = JSON.parse(sessionStorage.getItem('matrix:access-token') || 'false');

let getToken = null;

let isGuest = false;

matrix.addEventListener('login', () => isGuest = false);

if(token)
{
	getToken = Promise.resolve(token);
	isGuest  = token.isGuest;
}
else
{
	getToken = matrix.getGuestToken();
	isGuest  = true;
}

Router.listen(view, routes);

document.addEventListener('cvRouteStart', event => {
	view.args.page = '';
})

if(Router.query.loginToken)
{
	matrix.completeSso(Router.query.loginToken);
}
else
{
	view.listen(
		document
		, 'DOMContentLoaded'
		, event => view.render(document.body)
		, {once: true}
	);
}

const getDatabase = EventDatabase.open('events', 1);

Promise.all([getDatabase, getToken]).then(([database, access_token]) => {

	// Sycamore.checkFeeds(token.user_id);

	// matrix.addEventListener('matrix-event', thrownEvent => {
	// 	const event = MatrixEvent.from(thrownEvent.detail);
	// 	const store = 'events';
	// 	const index = 'event_id';
	// 	const range = event.event_id;
	// 	const type  = MatrixEvent;

	// 	database.select({store, index, range, type}).one().then(res => {
	// 		if(res.index)
	// 		{
	// 			res.record.consume(event);

	// 			database.update('events', res.record);
	// 		}
	// 		else
	// 		{
	// 			database.insert('events', event);
	// 		}
	// 	});
	// });

	if(isGuest)
	{
		return;
	}

	matrix.listenForServerEvents();

	matrix.sync().then(res => {

		if(!res || !res.rooms || !res.rooms.join)
		{
			return;
		}

		Object.entries(res.rooms.join).forEach(([room,state]) => {

			if(!state || !state.timeline)
			{
				return;
			}

			if(state.timeline.events)
			{
				return;
			}

			state.timeline.events.forEach(chunk => {
				chunk.room_id = room;

				const event = MatrixEvent.from(chunk);

				const store = 'events';
				const index = 'event_id';
				const range = event.event_id;
				const type  = MatrixEvent;

				database.select({store, index, range, type}).one().then(res => {
					if(res.index)
					{
						res.record.consume(chunk);

						database.update('events', res.record);
					}
					else
					{
						database.insert('events', event);
					}
				});
			});

			if(!state.timeline.prev_batch)
			{
				return;
			}

			matrix.syncRoomHistory(
				room
				, state.timeline.prev_batch
				, chunk => {
					// const event = MatrixEvent.from(chunk);

					// const store = 'events';
					// const index = 'event_id';
					// const range = event.event_id;

					// database.select({store, index, range}).then(res => {
					// 	if(res.index)
					// 	{
					// 		res.record.consume(chunk);

					// 		database.update('events', res.record);
					// 	}
					// 	else
					// 	{
					// 		database.insert('events', event);
					// 	}
					// });
				}
			);
		});
	});
});

if(window.obsstudio)
{
	window.obsstudio.getCurrentScene(function(scene) {
		console.log(scene)
	});
}
