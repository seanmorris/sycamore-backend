import { HyperScroller } from 'cv2-hyperscroll/HyperScroller';

export class EmojiScroller extends HyperScroller
{
	constructor()
	{
		super();

		this.template = require('./emoji-scroller.html');
	}

	select(event, emoji)
	{
		const success = new CustomEvent('modalSuccess', {detail: {emoji}});

		this.dispatchEvent(success);
	}

	cancel(event)
	{
		this.dispatchEvent('modalCancel');
	}
}
