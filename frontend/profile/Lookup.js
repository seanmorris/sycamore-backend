import { View } from 'curvature/base/View';
import { ActorModel } from '../activity-pub/ActorModel';

import { Profile } from './Profile';

export class Lookup extends View
{
	template = require('./lookup.html');

	onAttach()
	{
		this.args.bindTo('userId', v => {

			if(!v || v[0] === '@')
			{
				return;
			}

			this.args.userId = '@' + v;
		});

		this.args.userId = this.args.userId || '@seanmorris@mastodon.social';

		this.lookup(event);
	}

	lookup(event)
	{
		event.preventDefault();

		const fetchFinger = ActorModel.fetchWebFinger(this.args.userId);

		fetchFinger.then(fingerResult => this.renderFingerResult(fingerResult));

		const fetchActor = fetchFinger
			.then(fingerResult => ActorModel.findProfileLink(fingerResult))
			.then(userLink => ActorModel.fetchActor(userLink));

		fetchActor.then(actor => this.renderProfile(actor));
		fetchActor.then(actor => this.renderActor(actor));
	}

	renderFingerResult(result)
	{
		this.args.finger = View.from(require('./web-finger-result.html'), result);
	}

	renderActor(result)
	{
		this.args.actPub = View.from(require('./actor.html'), result)
	}

	renderProfile(result)
	{
		this.args.profile = new Profile(result);
	}
}
