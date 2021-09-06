import { Config } from 'curvature/base/Config';

export class Collection
{
	constructor(path, backend = Config.get('backend'))
	{
		this.path = path;

		if(typeof backend !== 'object')
		{
			backend = Promise.resolve(backend);
		}

		const index = backend.then(backend => backend + path)
		.then(url => fetch(url))
		.then(r=>r.json())

		Object.defineProperty(this, 'index', {value: index});
	}

	each(callback = record => record)
	{
		return this.eachPage(page => {

			if(!page.orderedItems)
			{
				return [];
			}

			return page.orderedItems.map(callback);
		});
	}

	prevPage(page, callback, accumulator = [])
	{
		accumulator.push(...callback(page));

		if(page.prev)
		{
			return fetch(page.prev)
			.then(r=>r.json())
			.then(page => this.prevPage(page, callback, accumulator));
		}

		return accumulator;
	}

	eachPage(callback)
	{
		return this.index
		.then(index => fetch(index.last))
		.then(r=>r.json())
		.then(page => this.prevPage(page, callback));
	}
}
