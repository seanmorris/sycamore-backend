import { View } from 'curvature/base/View';
import { Bag } from 'curvature/base/Bag';

export class ModalHost extends View
{
	template = require('./modal-host.html');

	constructor(args, parent)
	{
		super(args, parent);

		this.modals = new Bag;

		this.args.modals = this.modals.list;
	}

	add(view)
	{
		const modal = View.from(
			require('./modal.html')
			, {view}
			, this
		);

		this.modals.add(modal);

		view.addEventListener('modalSuccess', event => {
			modal.args.animation = 'modal-success';
			this.onTimeout(500, () => this.modals.remove(modal));
		});

		view.addEventListener('modalError', event => {
			modal.args.animation = 'modal-error';
			this.onTimeout(500, () => modal.args.animation = '');
		});

		view.addEventListener('modalCancel', event => {
			modal.args.animation = 'modal-cancel';
			this.onTimeout(250, () => this.modals.remove(modal));
		});
	}
}
