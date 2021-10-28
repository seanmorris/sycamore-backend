import { View } from 'curvature/base/View';
import { Tag } from 'curvature/base/Tag';

export class Outer extends View
{
	template = `[[frame]]`;

	constructor(args,parent)
	{
		super(args,parent);

		this.listen(window, 'message', event => this.handleMessage(event));

		this.waiting = new Map();

		const frame = new Tag('<iframe>');

		frame.src = 'web+social://whoami';
		frame.style({display: 'none'});

		this.ready = new Promise(accept => {
			frame.addEventListener(
				'load'
				, (event) => accept(event)
				, {once:true}
			);
		});

		this.args.frame = frame;
	}

	handleMessage(responseEvent)
	{
		const source = responseEvent.source;
		const packet = responseEvent.data;
		const reqId  = packet.reqId;

		if(typeof packet !== 'object' || !('🍁' in packet))
		{
			return;
		}

		if(!this.waiting.has(reqId))
		{
			return;
		}

		const callback = this.waiting.get(reqId);

		this.waiting.delete(reqId);

		callback(packet);
	}

	request(req = [], callback = () => {})
	{
		const subWin = this.args.frame.contentWindow;

		const reqId = Math.random().toString(36);

		return new Promise(accept => {
			this.waiting.set(reqId, packet => accept(packet));
			subWin.postMessage({reqId, req, '🍁':true});
		});
	}

	register()
	{
		navigator.registerProtocolHandler(
			'web+social'
			, 'https://localhost/proto/handle?_=%s'
			, 'Sycamore Protocol'
		);
	}
}
