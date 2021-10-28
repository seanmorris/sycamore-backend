import { View } from 'curvature/base/View';

import { VideoList } from './VideoList';

export class Video extends View
{
	template = require('./video.html');

	onAttach(event)
	{
		this.args.videoList = new VideoList;

		fetch('/channel-video.json')
		.then(r => r.json())
		.then(r => this.args.videos = r)
	}
}
