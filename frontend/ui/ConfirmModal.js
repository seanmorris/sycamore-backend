import { View } from 'curvature/base/View';
import { Config } from 'curvature/base/Config';

export class ConfirmModal extends View
{
	template = require('./confirm.html');

	accept(event)
	{
		this.dispatchEvent('modalAccept');
	}

	reject(event)
	{
		this.dispatchEvent('modalReject');
	}
}
