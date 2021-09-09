import { View } from 'curvature/base/View';

import { ChatRecordSet } from './ChatRecordSet';
import { Chat } from './Chat';

export class ChatRoom extends View
{
	template = require('./chat-room.html');

	constructor(args, parent)
	{
		super(args, parent);
		const content = new ChatRecordSet;
		const chat = new Chat;

		chat.args.content = content;

		this.args.chat = chat;

		this.args.chatLen = 25_000;
	}

	onAttach(event)
	{
		this.args.chat.args.content.changed(this.args.chatLen);

		this.args.chat.args.changedScroll = true;
		this.args.chat.args.rowHeight = 25;

		this.onInterval(1000, () => {
			this.args.chatLen+=15;
			this.args.chat.args.content.changed(this.args.chatLen);
		});
	}

	scroll(event)
	{
		const scrollPos = event.target.scrollTop + event.target.offsetHeight;

		if(this.lastScroll > scrollPos)
		{
			this.args.chat.args.changedScroll = false;
		}

		this.maxScroll  = event.target.scrollHeight;
		this.lastScroll = scrollPos;

		if(this.maxScroll === scrollPos)
		{
			this.args.chat.args.changedScroll = true;
		}
	}
}
