import { Database } from 'curvature/model/Database';

export class SocialDatabase extends Database
{
	_version_1(database)
	{
		const actors = this.createObjectStore('actors', {keyPath: 'id'});

		actors.createIndex('id', 'id', {unique: true});

		actors.createIndex('preferredUsername', 'preferredUsername', {unique: false});
		actors.createIndex('type', 'type', {unique: false});
		actors.createIndex('name', 'name', {unique: false});

		const objects = this.createObjectStore('objects', {keyPath: 'id'});

		objects.createIndex('id',   'id',   {unique: true});
		objects.createIndex('type', 'type', {unique: false});
	}
}
