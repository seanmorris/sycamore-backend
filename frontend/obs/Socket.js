export class Socket
{
	static get(host = 'ws://localhost:4444')
	{
		this.sockets = this.sockets || [];

		if(this.sockets[host])
		{
			return Promise.resolve(this.sockets[host]);
		}

		return new Promise((accept, reject) => {

			const instance = new this(host);

			instance.socket.addEventListener('open', message => {
				accept(instance);
			});
		});
	}

	constructor(host = 'ws://localhost:4444')
	{
		this.respondables = new Map;
		this.socket = new WebSocket(host);

		this.socket.addEventListener('message', message => this.handleMessage(message));
		this.socket.addEventListener('close', message => this.handleClose(message));
		this.socket.addEventListener('open', message => this.handleOpen(message));
	}

	request(type, requestFields = {})
	{
		const messageId = (Math.random() * 1000).toString(36);

		return new Promise(accept => {
			this.respondables.set(messageId, accept);
			this.socket.send(JSON.stringify({
				...requestFields
				, 'request-type': type
				, 'message-id': messageId
			}));
		})
	}

	handleMessage(message)
	{
		const content = JSON.parse(message.data);

		if(this.respondables.has(content['message-id']))
		{
			const respondable = this.respondables.get(content['message-id']);

			respondable(content);
		}
	}

	handleOpen()
	{
		this.request('GetAuthRequired').then(response => console.log(response));
	}

	handleClose()
	{

	}
}
	// socket.request('GetVersion').then(response => {
	// 	const availabileRequests = response['available-requests'];
	// 	// availabileRequests.split(',').forEach(type => console.log(type));
	// })
