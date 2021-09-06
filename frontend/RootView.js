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
		const login = new LoginView;

		Application.modalHost.add(login);

		login.addEventListener('modalSuccess', () => {
			this.args.loggedIn = true;
		});
	}

	registerClicked(event)
	{
		const register = new RegisterView;

		Application.modalHost.add(register)

		register.addEventListener('modalSuccess', () => {
			this.args.loggedIn = true;
		});
	}

	matrixLoginClicked(event)
	{
		matrix.initSso(location.origin);
	}

	githubLoginClicked(event)
	{
		Github.login();
	}

	passwordHasherClicked(event)
	{
		window.open(Config.get('hasher'));
	}

	openSettings()
	{
		this.args.settings = this.args.settings ? null : new UserList;
	}
}
