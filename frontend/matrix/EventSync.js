import { Database } from 'curvature/model/Database';
import { EventDatabase } from '../matrix/EventDatabase';

export class EventSync
{
	syncHistory(roomId)
	{
		EventDatabase.open('events', 1).then(database => {
			matrix.syncRoomHistory(roomId, '', event => {
				const store = 'events';
				const index = 'event_id';
				const range = event.event_id;
				const type  = MatrixEvent;

				database.select({store, index, range, type}).one().then(res => {
					if(res.index)
					{
						database.update('events', res.record);
					}
					else
					{
						database.insert('events', MatrixEvent.from(event));
					}
				});
			});
		})
	}
}
