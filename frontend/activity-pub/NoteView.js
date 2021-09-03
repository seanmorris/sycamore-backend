import { View } from 'curvature/base/View';
import { ActorModel } from './ActorModel';

export class NoteView extends View
{
	template = require('./note.html');

	onAttach()
	{
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
}
