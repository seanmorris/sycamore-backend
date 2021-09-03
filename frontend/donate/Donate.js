import { View } from 'curvature/base/View';

export class Donate
{
	template  = require('feed.html');

	onAttached(event)
	{
		var button = this.tags.submitPayment;

		fetch('//127.0.0.1:2020/pay/token').then(r => r.text()).then(token => {
			braintree.dropin.create({
				authorization: token,
				selector: this.tags.dropin
			}, (err, instance) => {

				button && button.node.addEventListener('click', event => {

					instance.requestPaymentMethod((err, payload) => {

						const method = 'POST';
						const body   = new FormData;

						body.append('nonce', payload.nonce);
						// body.append('nonce', payload.nonce);
						body.append('matrixUsername', 'xxyyzz');
						body.append('amount', this.args.donateAmount);

						braintree.dataCollector.create({client:instance._client}, (err, dataCollector) => {

							body.append('device', dataCollector.deviceData)

							fetch('//127.0.0.1:2020/pay/process', {method, body})
							.then(r=>r.text())
							.then(r=>console.log(r));

						});
					});
				})
			});
		});
	}
}
