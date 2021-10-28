import { Bindable } from 'curvature/base/Bindable';

import { RecordSet } from 'cv2-hyperscroll/RecordSet';

// import { EventModel as MatrixEvent } from '../matrix/EventModel';
// import { EventDatabase } from '../matrix/EventDatabase';

const faker = require('faker');

export class ChatRecordSet extends RecordSet
{
	constructor()
	{
		super();

		// this.db = EventDatabase.open('events', 1);

		this.index = 'type+room_id+time';

		this.roomId = '!FIoireJEFPfTCUfUrL:matrix.org';

		this.selectors = [IDBKeyRange.bound(
			['m.room.message', this.roomId, 0]
			, ['m.room.message', this.roomId, Infinity]
		)];

		this.loading = new Map;
	}

	changed(length)
	{
		length = Number(length);

		this.length = length + (this.header() ? 1 : 0);

		this.content && this.content.splice(length);
	}

	header()
	{
		// return {icon: 'icon', username: 'username', message: 'message'};
		return false;
	}

	count()
	{
		return Number(this.length);
	}

	fetch(k)
	{
		if(k >= this.length)
		{
			return;
		}

		const username = '...';
		const message  = '';
		const time     = '...';
		const icon     = ''; //'/x.svg';

		let result;

		if(this.loading.has(k))
		{
			result = this.loading.get(k);
		}
		else
		{
			result = Bindable.make({
				index: k, icon, username, message, time
			})

			this.loading.set(k, result);
		}

		return result;
	}
}
