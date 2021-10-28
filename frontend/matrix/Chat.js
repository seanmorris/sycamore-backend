import { HyperScroller } from 'cv2-hyperscroll/HyperScroller';

export class Chat extends HyperScroller
{
	constructor()
	{
		super();

		this.template = require('./chat.html');

		this.selected = false;
	}

	select(event)
	{
		if(this.selected === event.currentTarget)
		{
			return;
		}

		if(this.selected)
		{
			this.selected.classList.remove('selected');
			this.selected = null;
		}

		this.selected = event.currentTarget;

		event.currentTarget.classList.add('selected');
	}
}
