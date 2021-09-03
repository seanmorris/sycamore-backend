import { Config } from 'curvature/base/Config';

export class Access
{
	static whoAmI()
	{
		const credentials = 'include'
		const path = '/access/whoami';
		const mode = 'cors';

		const options = {mode, credentials};

		return Config.get('backend')
			.then(backend  => fetch(backend + path, options))
			.then(response => response.json());
	}
}
