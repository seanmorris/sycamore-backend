import { View } from 'curvature/base/View';

import { Github } from '../Github';

export class Wizard extends View
{
	githubLogin(event)
	{
		this.args.showOverlay = 'show';
		this.args.installRepo = 'sycamore-backend';
		this.args.iconType = 'spinner';
		this.args.showIcon = 'show';

		Github.login().then(() => Github.getUser()).then(user => {
			this.args.installOwner = user.login;
			this.onTimeout(750, () => {
				this.args.showIcon = 'hide';
				this.args.iconType = 'checkmark';
			});
			this.onTimeout(1000, () => {
				this.args.showIcon = 'show';
				this.parent.advance();
			});
			setTimeout(() => this.args.showOverlay = 'hide', 2000);
		});
	}

	githubFork(event)
	{
		const repo = new Github('seanmorris/sycamore-backend');
		const newRepo = `${this.args.installOwner}/${this.args.installRepo}`;

		repo.fork(newRepo).then(response => {
			console.log(response);
			this.parent.advance();
		});
	}

	githubSite(event)
	{
		const repo = new Github(`${this.args.installOwner}/${this.args.installRepo}`);

		repo.enablePages().then(response => {
			console.log(response);
			this.parent.advance();
		});
	}

	deployToHeroku(event)
	{
		window.open(`https://heroku.com/deploy?template=https://github.com/${this.args.installOwner}/${this.args.installRepo}`);
		this.parent.advance();
	}

	registerDyno(event)
	{
		fetch(this.args.installBackendOrigin).finally(() => {
			this.parent.advance();
		});
	}

	generateAdminPassword(event)
	{
		window.open(Config.get('hasher'));
	}
}
