import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

export class RegisterView extends View
{
	template = require('./register.html');

	register(event)
	{
		event.preventDefault();

		const path   = '/access/register';
		const method = 'POST';
		const body   = new FormData(event.target);
		const mode   = 'cors';

		const options = {method, body, mode, credentials: 'include'};

		Config.get('backend')
		.then(backend => fetch(backend + path, options))
		.then(r=>r.json())
		.then(outbox => {
			console.log(outbox);
		});
	}

	success(event)
	{
		this.dispatchEvent('modalSuccess');
	}

	cancel(event)
	{
		this.dispatchEvent('modalCancel');
	}
}
