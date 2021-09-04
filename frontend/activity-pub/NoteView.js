import { View } from 'curvature/base/View';
import { ActorModel } from './ActorModel';
import { NoteModel } from './NoteModel';

export class NoteView extends View
{
	template = require('./note.html');

	onAttach()
	{
		this.args.showComments = false;

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
