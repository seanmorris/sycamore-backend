import { HyperScroller } from 'cv2-hyperscroll/HyperScroller';

export class Chat extends HyperScroller
{
	constructor()
	{
		super();

		this.template = require('./chat.html');
	}
}
