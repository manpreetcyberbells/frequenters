export default class ReverbConnector {
    constructor(options) {
        this.options = options;
        this.channels = {};
        this.connect();
    }

    connect() {
        const query = `?protocol=7&client=js&version=8.4.0&flash=false`;
        const appKey = this.options.key;
        const url = `${this.options.scheme || 'ws'}://${this.options.host}:${this.options.port}/app/${appKey}${query}`;
        console.log('Connecting to WebSocket:', url);

        this.socket = new WebSocket(url);

        this.socket.onopen = () => {
            console.log('✅ WebSocket Connected');
        };

        this.socket.onerror = (error) => {
            console.error('❌ WebSocket Error:', error);
        };

        this.socket.onmessage = (event) => {
            console.log('📨 Incoming WebSocket message:', event.data);

            const parsed = JSON.parse(event.data);
            const eventName = parsed.event;
            const channelName = parsed.channel ?? null;
            const eventData = parsed.data;

            console.log('🔔 Event received:', eventName, 'on channel:', channelName);

            if (channelName && this.channels[channelName] && this.channels[channelName][eventName]) {
                this.channels[channelName][eventName](eventData);
            }
        };
    }

    listen(channelName, eventName, callback) {
        console.log(`👂 Listening on channel [${channelName}] for event [${eventName}]`);

        if (!this.channels[channelName]) {
            this.channels[channelName] = {};
        }

        this.channels[channelName][eventName] = callback;

        return this;
    }

    channel(channelName) {
        console.log(`🔗 Subscribed to public channel: ${channelName}`);
        return {
            listen: (eventName, callback) => {
                this.listen(channelName, eventName, callback);
                return this;
            }
        };
    }

    privateChannel(channelName) {
        console.log(`🔒 Subscribed to private channel: ${channelName}`);
        return this.channel(channelName);
    }

    presenceChannel(channelName) {
        console.log(`👥 Subscribed to presence channel: ${channelName}`);
        return this.channel(channelName);
    }

    leave(channelName) {
        if (this.channels[channelName]) {
            delete this.channels[channelName];
            console.log(`🚪 Left channel: ${channelName}`);
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
            console.log('❎ WebSocket Disconnected');
        }
    }
}
