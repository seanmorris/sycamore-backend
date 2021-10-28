import { ModalHost } from './ui/ModalHost';
import { Sycamore } from './Sycamore';

import { Matrix } from './Matrix';
import { EventModel as MatrixEvent } from './matrix/EventModel';

import { EventDatabase } from './matrix/EventDatabase';
import { Outer } from './cross/Outer';

export class Application{}

Application.outerCross = new Outer;
Application.modalHost = new ModalHost;
Application.matrix    = new Matrix;

const getDatabase = EventDatabase.open('events', 1);

Application.matrixSync = () => Promise.all([getDatabase, getToken]).then(([database, access_token]) => {

	// Sycamore.checkFeeds(matrixToken.user_id);

	const matrix = Application.matrix;

	matrix.addEventListener('matrix-event', thrownEvent => {
		const event = MatrixEvent.from(thrownEvent.detail);
		const store = 'events';
		const index = 'event_id';
		const range = event.event_id;
		const type  = MatrixEvent;

		database.select({store, index, range, type}).one().then(res => {
			if(res.index)
			{
				res.record.consume(event);

				database.update('events', res.record);
			}
			else
			{
				database.insert('events', event);
			}
		});
	});

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
							res.result.consume(chunk);

							database.update('events', res.result);
						}
						else
						{
							database.insert('events', event);
						}
					});
				});
			}


			if(!state.timeline.prev_batch)
			{
				return;
			}

			const lowWater = localStorage.getItem('room-lowWater::' + room);

			matrix.syncRoomHistory(
				room
				, lowWater || state.timeline.prev_batch
				, chunk => {

					const event = MatrixEvent.from(chunk);

					const store = 'events';
					const index = 'event_id';
					const range = event.event_id;
					const type  = MatrixEvent;

					const query = {store, index, range, type};

					database.select(query).one().then(res => {
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
				}
			);
		});
	});
});

const matrixToken = JSON.parse(sessionStorage.getItem('matrix:access-token') || 'false');

let getToken = null;

let isGuest = false;

const onLogin = () => {
	isGuest = false;
	Application.matrixSync();
};

if(matrixToken)
{
	isGuest  = matrixToken.isGuest || false;
	getToken = Promise.resolve(matrixToken);

	onLogin();
}
else
{
	isGuest  = true;

	Application.matrix.addEventListener('logged-in', onLogin);
}
