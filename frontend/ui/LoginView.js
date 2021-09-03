import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

export class LoginView extends View
{
	template = require('./login.html');

	login(event)
	{
		event.preventDefault();

		const path   = '/access/login';
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
}
