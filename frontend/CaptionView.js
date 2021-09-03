import { View } from 'curvature/base/View';

export class CaptionView extends View
{
	template = require('./caption.html');

	constructor(args)
	{
		super(args);
		this.args.captions = [];

		this.captionSource = new EventSource('http://127.0.0.1:2020/caption');

		this.captionSource.addEventListener('ServerEvent', event => {

			try {
				const frame = JSON.parse(event.data);
				const caption = [];

				let i = 0;

				frame.tokens.forEach(token => caption.push(token.text));

				const line = caption.join('').trim();

				if(!line)
				{
					return;
				}

				this.args.captions.push(line);

				this.onNextFrame(()=>{
					this.tags.captions.scrollTo({
						y: this.tags.captions.scrollHeight
						, behavior: 'smooth'
					});
				});

				this.onTimeout(
					Math.max(60 * frame.tokens.length, 1200)
					, () => this.args.captions.shift()
				);
			}
			catch (error) {
				console.warn(error);
			}


		});
	}
}
