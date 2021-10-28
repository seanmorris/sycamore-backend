import { View } from 'curvature/base/View';
import { Application } from '../Application';

export class Subscribe extends View
{
	template  = require('./subscribe.html');

	onAttached(event)
	{
		this._onSubmit = () => {};

		this.done = false;

		this.args.next = 'next';
		this.args.plan = null;
		this.args.step = 0;

		this.createBraintree();
	}

	createBraintree()
	{
		Application.outerCross.request(['userId'])
		.then(packet => {
			console.log(packet);
			if(packet.response && packet.response.userId)
			{
				this.args.outerId = packet.response.userId;

				return fetch('/pay/token')
				.then(r => r.text())
				.then(token => this.handlePaymentToken(token));
			}
		});
	}

	handlePaymentToken(token)
	{
		braintree.dropin.create(
			{authorization: token, selector: this.tags.braintreeDropin.node}
			, (err, instance) => this.handleBraintreeResponse(err, instance)
		);
	}

	handleBraintreeResponse(err, instance)
	{
		this.args.next = 'submit';

		this._onSubmit = event => {
			instance.requestPaymentMethod((error, payload) => {
				if(error)
				{
					console.warn(error);
					return;
				}

				const method = 'POST';
				const body   = new FormData;

				body.append('nonce',   payload.nonce);
				body.append('amount',  this.args.donateAmount);
				body.append('eventId', this.args.eventId);

				braintree.dataCollector.create({client:instance._client}, (err, dataCollector) => {

					body.append('device', dataCollector.deviceData)

					fetch(`${location.origin}/pay/subscribe`, {method, body})
					.then(r=>r.text())
					.then(r=>this.success(r));

				});
			});
		};
	}

	success(response)
	{
		this.dispatchEvent('modalSuccess');
		// console.log(response);
		// this.args.step = 2;
		// this.args.next = 'close';
		// this.done      = true;
	}

	submit(event)
	{
		if(this.args.step === 0)
		{
			this._onSubmit(event);
			return;
		}
	}

	cancel(event)
	{
		this.dispatchEvent('modalCancel');
	}

	send(message)
	{
		const sent = Application.matrix.putEvent(
			'!FIoireJEFPfTCUfUrL:matrix.org'
			, 'm.room.message'
			, {
			  msgtype: 'm.text'
			  , body:  this.args.superChatMessage
			}
		);

		return sent;
	}
}
