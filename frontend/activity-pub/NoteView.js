import { View } from 'curvature/base/View';
import { ActorModel } from './ActorModel';
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

				this.args.comments.push(NoteMode.from(record));

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
			, this.args.url || this.args.__remote_id || this.args.id
		);
	}
}
