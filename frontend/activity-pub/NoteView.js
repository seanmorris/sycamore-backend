import { View } from 'curvature/base/View';
import { ActorModel } from './ActorModel';
import { Collection } from './Collection';
import { NoteModel } from './NoteModel';
import { SocialDatabase } from './SocialDatabase';

export class NoteView extends View
{
	template = require('./note.html');

	constructor(args, parent)
	{
		super(args, parent);
		this.args.showComments = false;
		this.args.comments = [];

		this.args.bindTo('published', v => {

			this.args.order = this.args.timestamp - 1630900000000;

			console.log(this.args.order);

			// const date = new Date(v);

			// const locale = 'en-us';

			// const wDay   = date.toLocaleString(locale, {weekday: 'short'});
			// const month  = date.toLocaleString(locale, {month: 'long'});
			// const year   = date.toLocaleString(locale, {year: 'numeric'});
			// const minute = date.toLocaleString(locale, {minute: 'numeric'});
			// const [hour, ap] = date.toLocaleString(locale, {hour: 'numeric', hour12:true}).split(' ');

			// let mDay = date.toLocaleString(locale, {day: 'numeric'});

			// switch(mDay % 10)
			// {
			// 	case 1:  mDay += 'st'; break;
			// 	case 2:  mDay += 'nd'; break;
			// 	case 3:  mDay += 'rd'; break;
			// 	default: mDay += 'th'; break;
			// }

			// // const formatter = (...a) => `${a[0]}, the ${a[1]} of ${a[2]} ${a[3]} at ${a[4]}:${a[5]} ${a[6]}`;
			// // const formatted = formatter(wDay, mDay, month, year, hour, minute, ap);

			// // console.log(formatted);

		});

		this.args.bindTo('attributedTo', v => {
			ActorModel.get(v).then(actor => {
				this.args.nickname = actor.preferredUsername;
				this.args.globalId = actor.globalId;
				if(actor.icon)
				{
					this.args.iconSrc = actor.icon.url;
				}
			});
		});

		this.args.bindTo('replies', v => {
			if(!v)
			{
				return;
			}
			const repliesUrl = new URL(v);
			const collection = new Collection(
				repliesUrl.pathname
				, repliesUrl.protocol + '//' + repliesUrl.host
			);
		})

		SocialDatabase.open('activitypub', 1).then(database => {

			this.listen(database, 'write', event => {

				if(event.detail.subType !== 'insert')
				{
					return;
				}

				if(!event.detail.record || !event.detail.record.inReplyTo)
				{
					return;
				}

				if(this.args.id !== event.detail.record.inReplyTo)
				{
					return;
				}

				const record = event.detail.record;

				ActorModel.get(record.attributedTo).then(actor => {

					record.nickname = actor.preferredUsername;
					record.globalId = actor.globalId;

					if(actor.icon)
					{
						record.iconSrc = actor.icon.url;
					}

				});

				this.args.comments.push(NoteModel.from(record));

			});

			const query  = {
				store: 'objects',
				index: 'inReplyTo',
				range: this.args.__remote_id || this.args.id,
				type:  NoteModel
			};

			return database.select(query).each(record => {
				ActorModel.get(record.attributedTo).then(actor => {

					record.nickname = actor.preferredUsername;
					record.globalId = actor.globalId;

					if(actor.icon)
					{
						record.iconSrc = actor.icon.url;
					}

				});

				this.args.comments.push(record);
			});
		})
	}

	toggleComments(event)
	{
		event.preventDefault();

		this.args.showComments = !this.args.showComments;
	}

	createComment(event)
	{
		event.preventDefault();

		console.log(this.args);

		NoteModel.createPost(
			this.args.commentInput
			, this.args.__remote_id || this.args.id
		).then(response => this.args.showComments = false);
	}
}
