import { useEffect, useRef } from 'react';
import type { Channel, PresenceChannel } from 'laravel-echo';

/**
 * Hook to access Laravel Echo and subscribe to channels
 *
 * @example
 * const channel = useEcho<{ message: string }>('my-channel', (channel) => {
 *   channel.listen('.my-event', (event) => {
 *     console.log(event.message);
 *   });
 * });
 */
export function useEcho<T = any>(channelName: string, callback: (channel: Channel | PresenceChannel) => void): Channel | PresenceChannel | null {
    const channelRef = useRef<Channel | PresenceChannel | null>(null);

    useEffect(() => {
        if (!window.Echo) {
            console.warn('Echo is not initialized');
            return;
        }

        // Determine channel type based on name prefix
        let channel: Channel | PresenceChannel;

        if (channelName.startsWith('private-')) {
            channel = window.Echo.private(channelName.replace('private-', ''));
        } else if (channelName.startsWith('presence-')) {
            channel = window.Echo.join(channelName.replace('presence-', ''));
        } else {
            channel = window.Echo.channel(channelName);
        }

        channelRef.current = channel;

        // Execute the callback to set up listeners
        callback(channel);

        // Cleanup: leave the channel when component unmounts
        return () => {
            if (channelRef.current) {
                window.Echo.leave(channelName);
                channelRef.current = null;
            }
        };
    }, [channelName]);

    return channelRef.current;
}

/**
 * Hook to listen to a private channel
 */
export function usePrivateChannel<T = any>(channelName: string, callback: (channel: Channel) => void): Channel | null {
    return useEcho(`private-${channelName}`, callback) as Channel | null;
}

/**
 * Hook to listen to a presence channel
 */
export function usePresenceChannel<T = any>(channelName: string, callback: (channel: PresenceChannel) => void): PresenceChannel | null {
    return useEcho(`presence-${channelName}`, callback) as PresenceChannel | null;
}
