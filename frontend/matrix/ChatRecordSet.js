import { RecordSet } from 'cv2-hyperscroll/RecordSet';

const faker = require('faker');

export class ChatRecordSet extends RecordSet
{
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
		if(k > this.length)
		{
			return;
		}

		const icon = '';
		const username = faker.internet.userName();
		const message  = faker.lorem.sentence();

		return {index: k, icon, username, message};
	}
}
