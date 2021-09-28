import { Mixin } from 'curvature/base/Mixin';
import { EventTargetMixin } from 'curvature/mixin/EventTargetMixin';

export class Server extends Mixin.with(EventTargetMixin)
{
	constructor(rtcConfig)
	{
		super();

		this.peerServer = new RTCPeerConnection(rtcConfig);

		this.peerServer.addEventListener('track', event => {
			const trackEvent = new CustomEvent('track', {detail: {
				streams: event.streams, track: event.track
			}});
			trackEvent.originalEvent = event;
			this.dispatchEvent(trackEvent);
		});

		this.peerServer.addEventListener('datachannel', event => {

			this.peerServerChannel = event.channel;

			this.peerServerChannel.addEventListener('open', event => {
				const openEvent = new CustomEvent('open', {detail: event.data });
				openEvent.originalEvent = event;
				this.dispatchEvent(openEvent);
				this.connected = true;
			});

			this.peerServerChannel.addEventListener('close', event => {
				const closeEvent = new CustomEvent('close', {detail: event.data });
				closeEvent.originalEvent = event;
				this.dispatchEvent(closeEvent);
				this.connected = false;
			});

			this.peerServerChannel.addEventListener('message', event => {
				const messageEvent = new CustomEvent('message', {detail: event.data });
				messageEvent.originalEvent = event;
				this.dispatchEvent(messageEvent);
			});

			this.peerServerChannel.addEventListener('negotiationneeded', event => {
				const negotiationNeededEvent = new CustomEvent('negotiationneeded', {detail: event.data });
				negotiationNeededEvent.originalEvent = event;
				this.dispatchEvent(negotiationNeededEvent);
			});
		});
	}

	send(input)
	{
		this.peerServerChannel && this.peerServerChannel.send(input);
	}

	close()
	{
		this.peerServerChannel && this.peerServerChannel.close()
	}

	answer(offer)
	{
		return new Promise(accept => {
			this.peerServer.setRemoteDescription(offer);

			this.peerServer.createAnswer(
				answer => this.peerServer.setLocalDescription(answer)
				, error => console.error(error)
			);

			const candidates = new Set;

			this.peerServer.addEventListener('icecandidate', event => {

				if(!event.candidate)
				{
					return;
				}
				else
				{
					candidates.add(event.candidate);
				}

				accept(this.peerServer.localDescription);

			}, {once: true});
		});
	}

	addTrack(track)
	{
		this.peerServer.addTrack(track);
	}
}
