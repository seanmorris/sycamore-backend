import { Config } from 'curvature/base/Config';

export class Collection
{
	constructor(url)
	{
		const index = fetch(url).then(r=>r.json());

		Object.defineProperty(this, 'index', {value: index});
	}

	each(callback = record => record, direction = 'prev')
	{
		const pageCallback = page => {

			const items = page.orderedItems || page.items;

			if(!items)
			{
				return [];
			}

			return items.map(callback);
		};

		return this.eachPage(pageCallback, direction);
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

	nextPage(page, callback, accumulator = [])
	{
		accumulator.push(...callback(page));

		if(page.items && page.items.length && page.next)
		{
			return fetch(page.next)
			.then(r=>r.json())
			.then(nextPage => {

				if(nextPage.next === page.id)
				{
					return;
				}

				return this.nextPage(nextPage, callback, accumulator)
			});
		}

		return accumulator;
	}

	eachPage(callback, direction = 'prev')
	{
		this.index.then(index => {
			if(typeof index.first === 'object' && index.first.items)
			{
				index.first.items.map(callback);
			}
		});

		return this.index
		.then(index => fetch(direction === 'prev'
			? (index.last || index.first)
			: (typeof index.first === 'object' ? index.first.id : index.first)
		))
		.then(r=>r.json())
		.then(page => this[direction+'Page'](page, callback));
	}
}

