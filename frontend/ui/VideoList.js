import { View } from 'curvature/base/View';

export class VideoList extends View
{
	template = require('./video-list.html');

	onAttach(event)
	{
		this.args.videos = [];

		fetch('/channel-video.json')
		.then(r => r.json())
		.then(r => this.args.videos = r)
	}
}
