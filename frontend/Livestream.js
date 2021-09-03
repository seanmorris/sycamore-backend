import { View } from 'curvature/base/View';

import videojs from 'video.js';

export class Livestream extends View
{
	template = require('./livestream.html');

	onAttach(event)
	{
		this.livestream = videojs(this.tags.livestream.node);

		const type = 'application/x-mpegURL';
		const src  = 'http://127.0.0.1:8080/hls/stream.m3u8';

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
