import { View } from 'curvature/base/View';
import { Tag }  from 'curvature/base/Tag';

import { ChatRecordSet } from './ChatRecordSet';
import { Chat } from './Chat';

import { AlertBar } from '../ui/AlertBar';

import { EventModel as MatrixEvent } from '../matrix/EventModel';
import { EventDatabase } from '../matrix/EventDatabase';

import { Bindable } from 'curvature/base/Bindable';

import { Donate } from '../donate/Donate';

import { EmojiRecordSet } from './EmojiRecordSet';
import { EmojiScroller } from './EmojiScroller';

import { Application } from '../Application';

export class ChatRoom extends View
{
	template = require('./chat-room.html');

	constructor(args, parent)
	{
		super(args, parent);

		const content = new ChatRecordSet;
		const chat    = new Chat;

		chat.args.content = content;

		this.args.chat = chat;

		this.args.chatLen = 0;

		this.args.superchats = [];

		this.args.loggedIn = null;

		Application.matrix.whoAmI().then(response => {
			this.args.loggedIn = true;
		}).catch(error => {
			this.args.alerts = [ new AlertBar ];
			this.args.loggedIn = false;
		});

		this.args.roomId = '!KaJxaqzQsDrINmbMht:matrix.org';
		this.args.roomId = '!FIoireJEFPfTCUfUrL:matrix.org';

		this.index = 'type+room_id+time';

		this.selectors = [
			IDBKeyRange.bound(
				['m.room.message', this.args.roomId, 0]
				, ['m.room.message', this.args.roomId, Infinity]
			)
		];

		this.reactionSelectors = [
			IDBKeyRange.bound(
				['m.reaction', this.args.roomId, 0]
				, ['m.reaction', this.args.roomId, Infinity]
			)
		];

		this.db = EventDatabase.open('events', 1);

		this.superchatColor = {
			0: '#f2f0bb'
			, 25: '#bbf2c7'
			, 50: '#ddf26f'
			, 75: '#ddab6f'
			, 100: '#ef8866'
			, 500: '#d2636f'

		};
	}

	getViewArgs(record, index)
	{
		let result;

		if(this.args.chat.args.content.loading.has(-1+index))
		{
			result = this.args.chat.args.content.loading.get(-1+index);
		}
		else
		{
			let icon, username, message, time;

			result = Bindable.make({
				index, icon, username, message, time
			});

			this.args.chat.args.content.loading.set(-1+index, result);
		}

		const username  = record.sender || record.user_id;
		const message   = record.content.body || '';

		result.message  = message.trim();
		result.username = username.substring(1);
		result.time     = String(new Date(record.received));
		result.icon     = '';

		return result;
	}

	onAttach(event)
	{
		this.args.chat.args.content.changed(this.args.chatLen);

		this.args.chat.args.changedScroll = true;
		this.args.chat.args.rowHeight = 28;

		this.db.then(database => {
			const direction = 'next';
			const ranges = this.selectors;
			const index  = this.index;
			const store  = 'events';

			const messageQuery = {store, index, ranges, direction};

			database.select(messageQuery)
			.each((record,index) => this.getViewArgs(record,index))
			.then(result => {
				this.args.chatLen = result.index;

				const chat = this.args.chat;

				chat.args.content.changed(this.args.chatLen);
			});

			const reactionQuery = {
				store
				, index
				, ranges: this.reactionSelectors
				, direction: 'prev'
			};

			database.select(reactionQuery).each((record) => {

				if(!record.content || !record.content['m.relates_to'])
				{
					return;
				}

				const modBot = 'seanbot:matrix.org';

				if(!record.sender === modBot)
				{
					return;
				}

				if(record.content['m.relates_to'].key !== '🍁')
				{
					return;
				}

				if(!record.content['m.relates_to'].paid)
				{
					return;
				}

				const relatedId = record.content['m.relates_to'].event_id;

				const ranges = [IDBKeyRange.only(relatedId)];
				const index  = 'event_id';
				const store  = 'events';

				const messageQuery = {store, index, ranges, direction};

				const paid = record.content['m.relates_to'].paid;

				let color = 'white';

				for(const amount of Object.keys(this.superchatColor))
				{
					if(Number(amount) > Number(paid))
					{
						break;
					}

					color = this.superchatColor[amount];
				}

				database.select(messageQuery)
				.one(related => {
					this.args.superchats.push({
						sender:   related.sender
						, amount: paid
						, color:  color
						, time:   new Date(record.received)
						, sort:   record.received - 1600000000000
						, body:   related.content.body
					});
				})
				.then(result => {

					if(result.index)
					{
						return;
					}

					Application.matrix
					.getEvent(this.args.roomId, relatedId)
					.then(related => related && related.content && this.args.superchats.push({
						sender:   related.sender
						, amount: paid
						, color:  color
						, time:   new Date(record.received)
						, sort:   record.received - 1600000000000
						, body:   related.content.body
					}))
					.catch(error => console.warn(error));

				});
			});

			this.listen(database, 'write', dbEvent => this.handleDbWrite(dbEvent));
		});
	}

	handleDbWrite(dbEvent)
	{
		if(dbEvent.detail.subType !== 'insert')
		{
			return;
		}

		const record = dbEvent.detail.record;

		if(record.type === 'm.room.message')
		{
			this.handleRoomMessage(dbEvent);
			return;
		}

		if(record.type === 'm.reaction')
		{
			this.handleReaction(dbEvent);
			return;
		}
	}

	handleRoomMessage(dbEvent)
	{
		const record = dbEvent.detail.record;

		const eventKey = [record.type, record.room_id, record.origin_server_ts];

		const matchesFilter = this.selectors
			.map(selector => selector.includes(eventKey));

		if(!matchesFilter.includes(true))
		{
			return;
		}

		this.args.chatLen++

		this.getViewArgs(dbEvent.detail.record, this.args.chatLen);

		const chat = this.args.chat;

		chat.args.content.changed(this.args.chatLen);
	}

	handleReaction(dbEvent)
	{
		const record = dbEvent.detail.record;

		const eventKey = [record.type, record.room_id, record.origin_server_ts];

		const matchesFilter = this.reactionSelectors
			.map(selector => selector.includes(eventKey));

		if(!matchesFilter.includes(true))
		{
			return;
		}

		if(!record.content['m.relates_to'] || !record.content['m.relates_to'].event_id)
		{
			return;
		}

		const relatedId = record.content['m.relates_to'].event_id;

		const paid = record.content['m.relates_to'].paid;

		let color = 'white';

		for(const amount of Object.keys(this.superchatColor))
		{
			if(Number(amount) > Number(paid))
			{
				break;
			}

			color = this.superchatColor[amount];
		}

		Application.matrix
		.getEvent(this.args.roomId, relatedId)
		.then(related => {
			this.args.superchats.push({
				sender:   related.sender
				, amount: paid
				, color:  color
				, time:   new Date(record.received)
				, sort:   record.received - 1600000000000
				, body:   related.content.body
			})
		});
	}

	superExpand(event, superchat)
	{
		this.args.superSender = superchat.sender;
		this.args.superAmount = superchat.amount;
		this.args.superColor  = superchat.color;
		this.args.superBody   = superchat.body;
		this.args.superTime   = superchat.time;

		this.args.superExpanded = true;
	}

	matrixLogin(event)
	{
		console.log(event);

		Application.matrix.initSso(location.origin);
	}

	superClose(event)
	{
		this.args.superExpanded = false;
	}

	scroll(event)
	{
		const scrollPos = event.target.scrollTop + event.target.offsetHeight;

		if(this.lastScroll > scrollPos)
		{
			this.args.chat.args.changedScroll = false;
			this.args.changedScroll = false;
		}

		this.maxScroll  = event.target.scrollHeight;
		this.lastScroll = scrollPos;

		if(this.maxScroll === scrollPos)
		{
			this.args.chat.args.changedScroll = true;
			this.args.changedScroll = true;
		}
	}

	scrollToEnd()
	{
		this.args.chat.scroller.scrollTo(0, this.args.chat.scroller.scrollHeight);

		this.args.chat.args.changedScroll = true;
	}

	send(event)
	{
		event.preventDefault();

		const sent = Application.matrix.putEvent(
			this.args.roomId
			, 'm.room.message'
			, {
			  msgtype: 'm.text'
			  , body:  this.args.chatInput
			}
		).then(response => {

			if(!response || !response.event_id)
			{
				return;
			}

			this.args.chatInput = '';

			return Application.matrix.getEvent(
				this.args.roomId, response.event_id
			);
		});
	}

	superChat()
	{
		const donate = new Donate;

		Application.modalHost.add(donate);

		donate.addEventListener('modalSuccess', () => console.log(event));
	}

	emoji()
	{
		const emojiList = new EmojiScroller;
		const emojiSet  = new EmojiRecordSet;

		emojiList.args.content = emojiSet;

		emojiList.args.rowHeight = 26;

		Application.modalHost.add(emojiList);

		emojiList.addEventListener('modalSuccess', () => {
			if(!event.detail.emoji)
			{
				return;
			}

			const emoji = event.detail.emoji;

			this.args.chatInput = this.args.chatInput || '';

			this.args.chatInput += emoji.char;
		});
	}

	popOut(event)
	{
		const main = this.tags.chatPage.node;
		const rect = main.getBoundingClientRect();
		const orig = main.parentNode;

		const trimSize = {
			x: window.outerWidth - window.innerWidth
			, y: window.outerHeight - window.innerHeight
		};

		console.log(trimSize);

		const features = `screenX=${Math.floor(rect.x) + window.screenX}`
			+ `,screenY=${Math.floor(rect.y) + trimSize.y + -(trimSize.x/2) + window.screenY}`
			+ `,width=${Math.floor(rect.width)}`
			+ `,height=${Math.floor(rect.height)}`
			+ `,location=${location.origin}`;

		const chat = this.args.chat;

		if(this.chatWindow)
		{
			this.tags.chatFrame.appendChild(this.tags.chatPage.node);

			this.chatWindow && this.chatWindow.close();
			this.chatWindow = null;

			return;
		}

		this.chatWindow = window.open(
			''
			, 'chat-window-' + this.args.id
			, features
		);

		this.onRemove(() => {
			this.chatWindow && this.chatWindow.close()
			this.chatWindow = null;
		});

		this.listen(window, 'unload', () => this.chatWindow.close());

		this.chatWindow.addEventListener('unload', event => {
			this.tags.chatFrame.appendChild(this.tags.chatPage.node);
			this.args.chat.scroller.scrollTop = this.args.chat.scroller.scrollHeight;
			this.chatWindow = null;
		});

		const doc = this.chatWindow.document;

		doc.head.appendChild(new Tag(
			`<link rel="stylesheet" href = "${location.origin}/app.css">`
		).node);


		doc.title = 'Sycamore Stream Chat ' + this._id;

		doc.body.appendChild(this.tags.chatPage.node);

		this.chatWindow.addEventListener(
			'resize'
			, () => {
				this.args.chat.args.changedScroll = true;
				this.args.chat.scroller.scrollTop = this.args.chat.scroller.scrollHeight;
			}
		);
	}
}
