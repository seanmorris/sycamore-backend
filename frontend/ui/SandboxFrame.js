import { View } from 'curvature/base/View';
import { Tag  } from 'curvature/base/Tag';

export class SandboxFrame extends View
{
	constructor(args, parent)
	{
		super(args, parent);

		this.args.source  = this.args.source ?? '';

		this.messageQueue = [];
		this.template     = '<iframe cv-ref = "sandbox"></iframe>';

		this.args.csp = this.args.csp || {
			'script-src': [`'unsafe-inline'`, `'unsafe-eval'`]
			, 'connect-src': []
		};

		this.listen(window, 'message', event => this.onMessage(event));

		this.frameTag = false;
		this.debind   = false;
		this.cspTag   = false;
	}

	onAttached()
	{
		this.cspTag   && this.cspTag.remove();
		this.frameTag && this.frameTag.remove();
		this.debind   && this.debind();

		const cspTag = new Tag(`<meta http-equiv = "Content-Security-Policy"/>`);

		this.args.csp.bindTo((v,k,t,d) => {
			const content = Object.keys(this.args.csp)
				.map(v => `${v} ${this.args.csp[v].join(' ')}`)
				.join('; ');

			cspTag.attr({content});
		});

		this.tags.sandbox.style({'--realWidth': this.tags.sandbox.clientWidth});

		this.tags.sandbox.node.contentWindow.addEventListener('resize', event => {
			// this.tags.sandbox.style({'--realWidth': this.tags.sandbox.clientWidth});
		});

		const frameDoc = this.tags.sandbox.node.contentDocument;

		frameDoc.head.append(cspTag.node);

		const frameTag = new Tag(`<iframe sandbox = "allow-scripts; encrypted-media;" />`);

		this.debind = this.args.bindTo('source', v => frameTag.attr({'srcdoc': v}));

		frameDoc.body.append(frameTag.node);

		frameDoc.body.style.margin = 0;

		frameTag.style({
			position: 'absolute'
			, width:  '100%'
			, height: '100%'
			, padding: '0'
			, border:  '0'
			, margin:  '0'
			// , top:    0
			// , left:   0
			// , border: 0
		});

		this.tags.sandbox.style({
			border:  '0'
			// position: 'absolute'
			// , width:  '100%'
			// , height: '100%'
			// , top:    0
			// , left:   0
			// , border: 0
		});

		this.frameTag = frameTag;
		this.cspTag   = cspTag;

		this.listen(frameTag, 'load', event => this.onFrameLoaded(event));
	}

	onFrameLoaded(event)
	{
		this.subFrame = event.target.contentWindow;

		while(event = this.messageQueue.shift())
		{
			this.onMessage(event);
		}

		this.dispatchEvent(new CustomEvent('SandboxLoaded', {detail: { view: this }}));
	}

	onMessage(event)
	{
		if(!this.subFrame)
		{
			this.messageQueue.push(event);
			return;
		}

		if(event.source !== this.subFrame)
		{
			return;
		}

		this.dispatchEvent(new CustomEvent('SandboxMessage', {detail: {
			view: this, data: event.data
		}}));
	}
}
