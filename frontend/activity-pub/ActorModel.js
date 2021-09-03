import { Model } from 'curvature/model/Model';
import { SocialDatabase } from './SocialDatabase';

export class ActorModel extends Model
{
	static get keyProps() { return ['id'] }

	id;
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

		const url = new URL(instance.id);

		instance.globalId = `${instance.preferredUsername}@${url.host}`;

		return instance;
	}

	static get(id)
	{
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
		const fetchRemote = fetch(id)
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
				result.index
					? database.update('actors', actor)
					: database.insert('actors', actor);
			});
		});

		return fetchRemote;
	}
}
