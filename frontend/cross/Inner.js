import { View } from 'curvature/base/View';
import { Tag } from 'curvature/base/Tag';

import { AccessPrompt } from './AccessPrompt';

export class Inner extends View
{
	constructor(args,parent)
	{
		super(args,parent);

		console.trace(this, document);

		this.listen(window, 'message', event => this.handleMessage(event));

		this.gated = {
			userId: () => fetch('/access/whoami')
				.then(r => r.json())
				.then(({username}) => `https://localhost/ap/actor/${username}`)
			, some: 'sample data...'
		};
	}

	handleMessage(requestEvent)
	{
		const source = requestEvent.source;
		const packet = requestEvent.data;

		if(typeof packet !== 'object' || !('🍁' in packet))
		{
			return;
		}

		if(!packet)
		{
			console.warn(requestEvent);
			throw new Error('Invalid request. No Data sent.', requestEvent);
		}

		if(!packet.reqId)
		{
			console.warn(requestEvent);
			throw new Error('Invalid request. No ID.');
		}

		this.prompt(event);
	}

	prompt(requestEvent)
	{
		const packet = requestEvent.data;

		const width  = 400;
		const height = 600;

		const left = window.screenX + (window.outerWidth / 2)  + (width / 2);
		const top  = window.screenY + (window.outerHeight / 2) - (height / 2);

		const options = `width=${width},height=${height},screenX=${left},screenY=${top}`;

		const popup = window.open(
			``
			, 'social-whoami'
			, options
		);

		while(popup.document.body.firstChild)
		{
			popup.document.body.firstChild.remove();
		}

		popup.document.head.appendChild(new Tag(
			`<link rel="stylesheet" href = "${location.origin}/app.css">`
		).node);

		const prompt = new AccessPrompt;

		prompt.args.host = origin;
		prompt.args.details = packet.req;

		prompt.render(popup.document.body);

		prompt
		.catch(rejectEvent => this.handleReject(requestEvent, rejectEvent))
		.then(acceptEvent => this.handleAccept(requestEvent, acceptEvent))
		.finally(() => popup.close());
	}

	handleAccept(requestEvent, acceptEvent)
	{
		const source = requestEvent.source;
		const packet = requestEvent.data;

		const response = {};
		const promises = [];

		packet.req.forEach(detail => {

			if(!(detail in this.gated))
			{
				return;
			}

			const promise = this.gated[detail]();

			promise.then(value => response[detail] = value);

			promises.push(promise);
		});

		Promise.all(promises)
		.then(() => source.postMessage({response, ...packet}));
	}

	handleReject(requestEvent, rejectEvent)
	{
		const source = requestEvent.source;
		const packet = requestEvent.data;

		const reject = 'User clicked deny.';

		source.postMessage({reject, ...packet});
	}
}
