import { Tag } from 'curvature/base/Tag';
import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

import { ModalHost } from './ui/ModalHost';
import { UserList } from './UserList';
import { Github } from './Github';
import { Access } from './Access';

import { Application } from './Application';

import { LoginView } from './ui/LoginView';
import { RegisterView } from './ui/RegisterView';

Application.modalHost = new ModalHost;

export class RootView extends View
{
	template = require('./root.html');

	constructor(args)
	{
		super(args);

		this.args.profileName  = 'Sycamore';
		this.args.profileTheme = 0 ? 'red-dots' : 'maple-tree';
		this.args.modalHost = Application.modalHost;

		Access.whoAmI().then(user => this.args.loggedIn = !!user);
	}

	localLoginClicked(event)
	{
		Application.modalHost.add(new LoginView)
	}

	registerClicked(event)
	{
		Application.modalHost.add(new RegisterView)
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
