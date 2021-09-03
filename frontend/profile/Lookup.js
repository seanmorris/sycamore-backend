import { View } from 'curvature/base/View';

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

		this.args.userId = '@seanmorris@mastodon.social';

		this.lookup(event);
	}

	lookup(event)
	{
		event.preventDefault();

		const fetchFinger = this.fetchWebFinger(this.args.userId);

		fetchFinger.then(fingerResult => this.renderFingerResult(fingerResult));

		const fetchActor = fetchFinger
			.then(fingerResult => this.findProfileLink(fingerResult))
			.then(userLink => this.fetchActor(userLink));

		fetchActor.then(actor => this.renderActor(actor));

		fetchActor.then(actor => this.renderProfile(actor));

		fetchActor.then(actor => this.renderProfile(actor));
	}

	fetchWebFinger(userLocator)
	{
		const [, userId, server] = String(userLocator).split('@');

		if(!userId || !server)
		{
			return;
		}

		const url = `https://${server}/.well-known/webfinger?resource=${encodeURIComponent(
			userId + '@' + server
		)}`;

		return fetch(url).then(response => response.json()).then(result => {

			if(!result)
			{
				this.args.error = 'User not found.';
			}

			return result;
		});
	}

	renderFingerResult(result)
	{
		this.args.finger = View.from(require('./web-finger-result.html'), result);
	}

	findProfileLink(fingerResult)
	{
		if(!fingerResult.links)
		{
			return;
		}

		for(const link of fingerResult.links)
		{
			if(link.rel === 'self')
			{
				return link.href;
			}
		}
	}

	fetchActor(userLink)
	{
		const options = {headers: {Accept: 'application/json'}};

		return fetch(userLink, options).then(response => response.json());
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
