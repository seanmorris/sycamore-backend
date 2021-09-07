import { Model } from 'curvature/model/Model';
import { SocialDatabase } from './SocialDatabase';

export class ActorModel extends Model
{
	static get keyProps() { return ['id'] }

	id;
	__remote_id;
	type;
	globalId
	published;
	preferredUsername;
	manuallyApprovesFollowers;
	name;
	discoverable;
	following;
	followers;
	inbox;
	outbox;
	endpoints;
	publicKey;
	icon;
	image;

	static from(skeleton)
	{
		const instance = super.from(skeleton);

		const url = new URL(instance.__remote_id || instance.id);

		instance.globalId = `${instance.preferredUsername}@${url.host}`;

		return instance;
	}

	static get(id)
	{
		if(!id)
		{
			return Promise.resolve();
		}

		const range = IDBKeyRange.only(id);
		const index = 'id';
		const store = 'actors';
		const type  = this;
		const limit = 1;

		return SocialDatabase.open('activitypub', 1).then(database => database
			.select({store, index, range, type}).one()
			.then(results => results.index
				? this.from(results.record)
				: this.getRemote(id)
			)
		);
	}

	static getRemote(id)
	{
		const options = {headers:{Accept: 'application/json'}}
		// const options = {headers:{Accept: 'application/json'}}

		const fetchRemote = fetch(id, options)
			.then(r => r.json())
			.then(response => this.from(response));

		const range = IDBKeyRange.only(id);
		const index = 'id';
		const store = 'actors';
		const limit = 1;
		const type  = this;

		const prereq = Promise.all([SocialDatabase.open('activitypub', 1), fetchRemote.then()])

		prereq.then(([database, actor]) => {
			database.select({store, index, range, type}).one().then(result => {
				console.log(result);
				result.index
					? database.update('actors', actor)
					: database.insert('actors', actor);
			});
		});

		return fetchRemote;
	}

	static fetchWebFinger(userLocator)
	{
		const [, userId, server] = String(userLocator).split('@');

		if(!userId || !server)
		{
			return Promise.reject('Invalid user locator.');
		}

		const url = `https://${server}/.well-known/webfinger?resource=acct:${encodeURIComponent(
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

	static fetchActor(userLink)
	{
		const options = {headers: {Accept: 'application/json'}};

		return fetch(userLink, options)
			.then(response => response.json())
			.then(actor => ActorModel.from(actor));
	}

	static findProfileLink(fingerResult)
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
}
