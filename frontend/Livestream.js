import { View } from 'curvature/base/View';

import { ChatRoom } from './matrix/ChatRoom';

import videojs from 'video.js';

export class Livestream extends View
{
	template = require('./livestream.html');

	onAttach(event)
	{
		this.args.chat = new ChatRoom;

		this.livestream = videojs(this.tags.livestream.node);

		const type = 'application/x-mpegURL';
		const src  = '/hls/sean.m3u8';

		const options = {
			autoSetup: false,
			preload: 'metadata',
			autoplay: true,
			responsive: true,
			fluid: true,
			fill: true,
			loadingSpinner: true,
			errorDisplay: false,
			breakpoints: {
				medium: 500
			},
			html5: {
				nativeControlsForTouch: false,
				nativeAudioTracks: false,
				nativeVideoTracks: false,
				hls: {
					limitRenditionByPlayerDimensions: false,
					smoothQualityChange: true,
					bandwidth: 6194304,
					overrideNative: true
				}
			}
		};

		this.livestream.src({src, type}, options);
	}
}
