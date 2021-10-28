import { Bindable } from 'curvature/base/Bindable';

import { RecordSet } from 'cv2-hyperscroll/RecordSet';

// import { EventModel as MatrixEvent } from '../matrix/EventModel';
// import { EventDatabase } from '../matrix/EventDatabase';

const faker = require('faker');
const emoji = require('emoji.json/emoji.json');

export class EmojiRecordSet extends RecordSet
{
	header()
	{
		return false;
	}

	count()
	{
		return Number(emoji.length);
	}

	fetch(k)
	{
		if(k > emoji.length)
		{
			return;
		}

		return emoji[k];
	}
}
