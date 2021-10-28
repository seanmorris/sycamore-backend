import { View } from 'curvature/base/View';
import { Application } from '../Application';

export class Donate extends View
{
	template  = require('./donate.html');

	onAttached(event)
	{
		this._onSubmit = () => {};

		this.done = false;

		this.args.next = 'next';
		this.args.plan = null;
		this.args.step = 0;
	}

	createBraintree()
	{
		fetch('/pay/token')
		.then(r => r.text())
		.then(token => this.handlePaymentToken(token));
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
			instance.requestPaymentMethod((err, payload) => {

				const method = 'POST';
				const body   = new FormData;

				body.append('nonce',   payload.nonce);
				body.append('amount',  this.args.donateAmount);
				body.append('eventId', this.args.eventId);

				braintree.dataCollector.create({client:instance._client}, (err, dataCollector) => {

					body.append('device', dataCollector.deviceData)

					fetch(`${location.origin}/pay/process`, {method, body})
					.then(r=>r.text())
					.then(r=>this.success(r));

				});
			});
		};
	}

	success(response)
	{
		this.args.step = 2;
		this.args.next = 'close';
		this.done      = true;
	}

	submit(event)
	{
		if(this.args.step === 0)
		{
			this.send().then(response => {
				this.args.eventId = response.event_id;
				this.createBraintree();
				this.args.step = 1;
			});
			return;
		}

		if(this.args.step === 1)
		{
			this._onSubmit(event);
			return;
		}

		if(this.args.step === 2)
		{
			this.dispatchEvent('modalSuccess');
			this.args.step = 3;
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
