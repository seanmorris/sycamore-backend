import { Tag } from 'curvature/base/Tag';
import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

import { UserList } from './UserList';
import { Github } from './Github';
import { Access } from './Access';

export class RootView extends View
{
	template = require('./root.html');

	constructor(args)
	{
		super(args);

		this.args.profileName  = 'Sycamore';
		this.args.profileTheme = 0 ? 'red-dots' : 'maple-tree';

		Access.whoAmI().then(user => this.args.loggedIn = !!user);
	}

	matrixLoginClicked(event)
	{
		matrix.initSso(location.origin);
	}

	githubLoginClicked(event)
	{
		Github.login();
	}

	openSettings()
	{
		this.args.settings = this.args.settings ? null : new UserList;
	}
}
